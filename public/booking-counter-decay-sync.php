<?php
/**
 * Booking Slot Counter — Decay Date Auto-Reminder Sync (v9.7.2)
 *
 * When the admin sets a "Slots valid through" date for a service in the
 * Booking Slot Counter settings, this sync helper auto-creates (or updates,
 * or deletes) a corresponding entry in the existing Scheduled Lists
 * feature so the doctor gets an email a configurable number of days before
 * the slots run out.
 *
 * Schedules created by this module are tagged with
 *   source_marker = 'bc:decay:<service_id>'
 * so they can be safely updated / deleted on subsequent saves without
 * touching schedules the user created manually.
 *
 * @since 9.7.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class Amelia_BC_Decay_Sync {

    /**
     * Look up the per-service report email saved on the body-chart admin
     * panel (stored in `wp_amelia_service_chart.data` JSON, key `email`).
     * Returns '' when no per-service email is set.
     */
    public static function get_service_report_email($service_id) {
        global $wpdb;
        $service_id = intval($service_id);
        if ($service_id <= 0) return '';
        $table = $wpdb->prefix . 'amelia_service_chart';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) return '';
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT data FROM $table WHERE service_id = %d",
            $service_id
        ));
        if (!$row || empty($row->data)) return '';
        $decoded = json_decode($row->data, true);
        if (!is_array($decoded) || empty($decoded['email'])) return '';
        return (string) $decoded['email'];
    }

    /**
     * Merge two comma-separated email lists, dedupe (case-insensitive),
     * drop invalid entries, and return a clean comma-separated string.
     */
    public static function merge_recipients($global, $service_email) {
        $combined = array();
        foreach (array($global, $service_email) as $list) {
            if (!is_string($list) || $list === '') continue;
            foreach (preg_split('/[,;\s]+/', $list) as $part) {
                $part = trim($part);
                if ($part === '') continue;
                if (!is_email($part)) continue;
                $combined[strtolower($part)] = $part; // dedupe case-insensitive
            }
        }
        return implode(', ', array_values($combined));
    }

    /**
     * Reconcile the auto-reminder schedule for a single service.
     *
     * @param int    $service_id        Amelia service ID.
     * @param string $decay_date        YYYY-MM-DD or '' to clear.
     * @param int    $reminder_days     Number of days before decay_date to send.
     * @param string $recipients        Comma-separated email list (global default).
     * @param string $reminder_time     HH:MM (site timezone) — when to send.
     * @return array{action:string, schedule_id:?int, message:string}
     */
    public static function sync_service($service_id, $decay_date, $reminder_days, $recipients, $reminder_time = '09:00') {
        global $wpdb;
        $service_id = intval($service_id);
        $reminder_days = max(0, intval($reminder_days));
        $global_recipients = trim((string) $recipients);
        $decay_date = trim((string) $decay_date);

        if ($service_id <= 0) {
            return array('action' => 'skip', 'schedule_id' => null, 'message' => 'Invalid service id');
        }

        // Per-service report-to email saved on the body-chart admin panel.
        $service_email = self::get_service_report_email($service_id);
        $merged_recipients = self::merge_recipients($global_recipients, $service_email);

        $marker = 'bc:decay:' . $service_id;
        $table = $wpdb->prefix . 'aals_schedules';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return array('action' => 'skip', 'schedule_id' => null, 'message' => 'Schedules table missing — install Scheduled Lists first');
        }

        // Find any existing auto-managed schedule for this service.
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table WHERE source_marker = %s ORDER BY id DESC LIMIT 1",
            $marker
        ));

        // No decay date OR no recipients (neither global nor service) → delete any existing managed schedule.
        if ($decay_date === '' || $merged_recipients === '') {
            if ($existing) {
                $wpdb->delete($table, array('id' => intval($existing->id)));
                return array('action' => 'deleted', 'schedule_id' => intval($existing->id),
                             'message' => 'Cleared (no decay date or no recipients)');
            }
            return array('action' => 'skip', 'schedule_id' => null, 'message' => 'Nothing to do');
        }

        // Compute the trigger datetime in UTC: decay_date - reminder_days at reminder_time site-tz.
        try {
            $site_tz = wp_timezone();
            $trigger_local = new DateTime($decay_date . ' ' . $reminder_time . ':00', $site_tz);
            $trigger_local->modify('-' . $reminder_days . ' days');
            $trigger_utc = clone $trigger_local;
            $trigger_utc->setTimezone(new DateTimeZone('UTC'));
            $trigger_utc_str = $trigger_utc->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return array('action' => 'error', 'schedule_id' => null, 'message' => 'Bad date: ' . $e->getMessage());
        }

        // Look up the service name for the cache fields and email subject.
        $services_table = $wpdb->prefix . 'amelia_services';
        $service_name = $wpdb->get_var($wpdb->prepare(
            "SELECT name FROM $services_table WHERE id = %d",
            $service_id
        ));
        $service_name = $service_name ?: ('Service #' . $service_id);

        // Default email subject + body — plain English. Admin can still edit
        // these from the Scheduled Lists UI; we only set them if they are
        // currently empty so we don't overwrite manual edits on every save.
        $default_subject = sprintf('[Slot Reminder] %s — release more slots before %s', $service_name, $decay_date);
        $default_body = sprintf(
            "Heads up: the booking slot counter for <strong>%s</strong> reaches its decay date in %d day(s) (on %s).\n\nIf you haven't already, please open up more upcoming slots so customers don't see the fully-booked message.\n\n— Booking Slot Counter (auto-reminder)",
            esc_html($service_name),
            $reminder_days,
            esc_html($decay_date)
        );

        $payload = array(
            'schedule_type'      => 'group_report_reminder',
            'source_marker'      => $marker,
            'service_id'         => $service_id,
            'service_name_cache' => $service_name,
            'service_ids_json'   => json_encode(array($service_id)),
            'service_names_cache'=> json_encode(array($service_name)),
            'recipients'         => $merged_recipients,
            'time_value'         => $reminder_days,
            'time_unit'          => 'days',
            'time_direction'     => 'before',
            'enabled'            => 1,
            'status'             => 'Scheduled',
            'status_message'     => 'Auto-managed by Booking Slot Counter — fires ' . $reminder_days . ' day(s) before decay (' . $decay_date . ')'
                . ($service_email !== '' ? ' — incl. service email ' . $service_email : ''),
            'appointment_status' => 'approved',
            'csv_fields_json'    => json_encode(array()),
            'next_trigger_utc'   => $trigger_utc_str,
            'last_updated_utc'   => current_time('mysql', true),
        );

        if ($existing) {
            // Preserve user-edited subject/body if they exist; otherwise set defaults.
            $current = $wpdb->get_row($wpdb->prepare(
                "SELECT email_subject, email_body FROM $table WHERE id = %d",
                intval($existing->id)
            ));
            if (empty($current->email_subject)) $payload['email_subject'] = $default_subject;
            if (empty($current->email_body))    $payload['email_body']    = $default_body;

            $wpdb->update($table, $payload, array('id' => intval($existing->id)));
            return array('action' => 'updated', 'schedule_id' => intval($existing->id),
                         'message' => 'Updated — fires at ' . $trigger_utc_str . ' UTC');
        }

        // Insert fresh
        $payload['email_subject'] = $default_subject;
        $payload['email_body']    = $default_body;
        $payload['created_utc']   = current_time('mysql', true);
        $wpdb->insert($table, $payload);
        $new_id = (int) $wpdb->insert_id;
        if (!$new_id) {
            return array('action' => 'error', 'schedule_id' => null, 'message' => 'Insert failed: ' . $wpdb->last_error);
        }
        return array('action' => 'created', 'schedule_id' => $new_id,
                     'message' => 'Created — fires at ' . $trigger_utc_str . ' UTC');
    }

    /**
     * Reconcile every per-service decay date in one call.
     *
     * @param array<int,string> $decay_dates    service_id => YYYY-MM-DD
     * @param int    $reminder_days
     * @param string $recipients
     * @param string $reminder_time HH:MM
     * @return array<int,array> service_id => sync result
     */
    public static function sync_all($decay_dates, $reminder_days, $recipients, $reminder_time = '09:00') {
        $results = array();
        if (!is_array($decay_dates)) $decay_dates = array();

        // Also handle services that PREVIOUSLY had a decay date but have
        // now been cleared — find every managed schedule and ensure it's
        // either still wanted or deleted.
        global $wpdb;
        $table = $wpdb->prefix . 'aals_schedules';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            $managed = $wpdb->get_results(
                "SELECT id, source_marker FROM $table WHERE source_marker LIKE 'bc:decay:%'"
            );
            foreach ($managed as $row) {
                $sid = intval(substr($row->source_marker, strlen('bc:decay:')));
                if ($sid > 0 && empty($decay_dates[$sid])) {
                    // Cleared — delete
                    $wpdb->delete($table, array('id' => intval($row->id)));
                    $results[$sid] = array(
                        'action' => 'deleted', 'schedule_id' => intval($row->id),
                        'message' => 'Cleared because decay date was removed'
                    );
                }
            }
        }

        foreach ($decay_dates as $sid => $decay_date) {
            $sid = intval($sid);
            if ($sid <= 0) continue;
            $results[$sid] = self::sync_service($sid, (string) $decay_date, $reminder_days, $recipients, $reminder_time);
        }
        return $results;
    }
}
