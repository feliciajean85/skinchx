<?php
/**
 * Booking Counter Settings (v9.7.2 — manual total mode)
 *
 * Admin page lets the doctor enter how many slots they have planned for
 * each service over the upcoming period. The plugin counts how many
 * appointment rows already exist for that service in the future and
 * displays the difference as "X of Y booked — Z available". When booked
 * reaches the total, the configurable waitlist message is shown instead.
 */
if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_options')) {
    wp_die('Permission denied');
}

global $wpdb;
$services_table = $wpdb->prefix . 'amelia_services';
$services = array();
if ($wpdb->get_var("SHOW TABLES LIKE '$services_table'") === $services_table) {
    $services = $wpdb->get_results("SELECT id, name, status FROM $services_table WHERE status != 'disabled' ORDER BY name ASC");
}

// Manual "Purge page cache now" button — handy for testing without saving.
if (isset($_POST['amelia_bc_purge_now']) && check_admin_referer('amelia_bc_save', 'amelia_bc_nonce')) {
    $purged = function_exists('amelia_bc_purge_caches') ? amelia_bc_purge_caches() : array();
    $msg = empty($purged)
        ? 'No page-cache plugin detected. Generic WP object cache flushed.'
        : 'Page cache purged: ' . esc_html(implode(', ', $purged));
    echo '<div class="notice notice-success is-dismissible" style="margin: 16px 0;"><p><strong>' . $msg . '</strong></p></div>';
}

// Save handler
if (isset($_POST['amelia_bc_nonce']) && wp_verify_nonce($_POST['amelia_bc_nonce'], 'amelia_bc_save') && !isset($_POST['amelia_bc_purge_now'])) {
    update_option('amelia_bc_enabled', isset($_POST['amelia_bc_enabled']) ? 1 : 0);
    update_option('amelia_bc_counter_label_default', wp_kses_post($_POST['amelia_bc_counter_label_default'] ?? ''));
    update_option('amelia_bc_full_message_default', wp_kses_post($_POST['amelia_bc_full_message_default'] ?? ''));

    // Decay-reminder global settings
    update_option('amelia_bc_reminder_days',     max(0, intval($_POST['amelia_bc_reminder_days'] ?? 0)));
    update_option('amelia_bc_reminder_time',     sanitize_text_field($_POST['amelia_bc_reminder_time'] ?? '09:00'));
    update_option('amelia_bc_reminder_recipients', sanitize_text_field($_POST['amelia_bc_reminder_recipients'] ?? ''));

    $per_full       = array();
    $per_toggle     = array();
    $per_total      = array();
    $per_clabel     = array();
    $per_decay      = array();

    if (!empty($_POST['amelia_bc_full_messages']) && is_array($_POST['amelia_bc_full_messages'])) {
        foreach ($_POST['amelia_bc_full_messages'] as $sid => $val) {
            $sid = intval($sid);
            $val = trim(wp_kses_post($val));
            if ($sid > 0 && $val !== '') $per_full[$sid] = $val;
        }
    }
    if (!empty($_POST['amelia_bc_full_toggles']) && is_array($_POST['amelia_bc_full_toggles'])) {
        foreach ($_POST['amelia_bc_full_toggles'] as $sid => $val) {
            $sid = intval($sid);
            if ($sid > 0 && (string) $val === '1') $per_toggle[$sid] = 1;
        }
    }
    if (!empty($_POST['amelia_bc_total_slots']) && is_array($_POST['amelia_bc_total_slots'])) {
        foreach ($_POST['amelia_bc_total_slots'] as $sid => $val) {
            $sid = intval($sid);
            $val = intval($val);
            if ($sid > 0 && $val > 0) $per_total[$sid] = $val;
        }
    }
    if (!empty($_POST['amelia_bc_counter_labels']) && is_array($_POST['amelia_bc_counter_labels'])) {
        foreach ($_POST['amelia_bc_counter_labels'] as $sid => $val) {
            $sid = intval($sid);
            $val = trim(wp_kses_post($val));
            if ($sid > 0 && $val !== '') $per_clabel[$sid] = $val;
        }
    }
    if (!empty($_POST['amelia_bc_decay_dates']) && is_array($_POST['amelia_bc_decay_dates'])) {
        foreach ($_POST['amelia_bc_decay_dates'] as $sid => $val) {
            $sid = intval($sid);
            $val = trim((string) $val);
            // Loose YYYY-MM-DD validation
            if ($sid > 0 && $val !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                $per_decay[$sid] = $val;
            }
        }
    }
    update_option('amelia_bc_full_messages', $per_full);
    update_option('amelia_bc_full_toggles', $per_toggle);
    update_option('amelia_bc_total_slots', $per_total);
    update_option('amelia_bc_counter_labels', $per_clabel);
    update_option('amelia_bc_decay_dates', $per_decay);

    // Purge page caches so the new totals show up immediately on the
    // public booking page (LiteSpeed, WP Rocket, W3TC, etc.).
    $purged_caches = function_exists('amelia_bc_purge_caches') ? amelia_bc_purge_caches() : array();

    // Sync auto-managed Scheduled List reminders for decay dates.
    $sync_msg = '';
    if (class_exists('Amelia_BC_Decay_Sync')) {
        $rdays = (int) get_option('amelia_bc_reminder_days', 0);
        $rtime = (string) get_option('amelia_bc_reminder_time', '09:00');
        $rrcpt = (string) get_option('amelia_bc_reminder_recipients', '');
        // Reminders can run on either the global recipients OR the per-service
        // email saved on the body-chart admin panel (wp_amelia_service_chart).
        // So we only need a positive lead time to enable the sync — the
        // per-service merge happens inside sync_service() itself.
        if ($rdays > 0) {
            $sync = Amelia_BC_Decay_Sync::sync_all($per_decay, $rdays, $rrcpt, $rtime);
            $created = 0; $updated = 0; $deleted = 0; $errors = 0; $skipped = 0;
            foreach ($sync as $r) {
                if ($r['action'] === 'created') $created++;
                elseif ($r['action'] === 'updated') $updated++;
                elseif ($r['action'] === 'deleted') $deleted++;
                elseif ($r['action'] === 'error') $errors++;
                elseif ($r['action'] === 'skip') $skipped++;
            }
            $sync_msg = sprintf(
                ' Auto-reminders synced: %d created, %d updated, %d deleted%s%s.',
                $created, $updated, $deleted,
                $skipped > 0 ? ", {$skipped} skipped (no recipients)" : '',
                $errors > 0 ? ", {$errors} errors" : ''
            );
        } else {
            // Lead time is zero → wipe any auto-managed schedules.
            $sync = Amelia_BC_Decay_Sync::sync_all(array(), 0, '', $rtime);
            $cleaned = 0;
            foreach ($sync as $r) if ($r['action'] === 'deleted') $cleaned++;
            if ($cleaned) $sync_msg = sprintf(' Cleaned %d stale auto-reminder(s).', $cleaned);
            $sync_msg .= ' (Set Reminder lead time > 0 to enable new auto-reminders.)';
        }
    }

    $purge_note = empty($purged_caches)
        ? ' (No page-cache plugin detected.)'
        : ' Page cache purged: ' . esc_html(implode(', ', $purged_caches)) . '.';
    echo '<div class="notice notice-success is-dismissible" style="margin: 16px 0;"><p><strong>Settings saved.</strong>' . esc_html($sync_msg) . $purge_note . '</p></div>';
}

$enabled = (int) get_option('amelia_bc_enabled', 1);
$counter_label_default = get_option(
    'amelia_bc_counter_label_default',
    '{booked} of {total} slots booked — {remaining} available'
);
$full_default = get_option(
    'amelia_bc_full_message_default',
    'Sorry — every upcoming session is fully booked right now. Please check back soon or contact us to be added to the waitlist.'
);
$full_messages   = (array) get_option('amelia_bc_full_messages', array());
$full_toggles    = (array) get_option('amelia_bc_full_toggles', array());
$total_slots_map = (array) get_option('amelia_bc_total_slots', array());
$counter_labels  = (array) get_option('amelia_bc_counter_labels', array());
$decay_dates     = (array) get_option('amelia_bc_decay_dates', array());
$reminder_days   = (int) get_option('amelia_bc_reminder_days', 0);
$reminder_time   = (string) get_option('amelia_bc_reminder_time', '09:00');
$reminder_recipients = (string) get_option('amelia_bc_reminder_recipients', '');

// Helper: count current bookings per service for the live preview column
$now_utc = gmdate('Y-m-d H:i:s');
$booked_counts = array();
$appts_table = $wpdb->prefix . 'amelia_appointments';
if ($wpdb->get_var("SHOW TABLES LIKE '$appts_table'") === $appts_table && !empty($services)) {
    $svc_ids = array_map(function($s) { return intval($s->id); }, $services);
    if (!empty($svc_ids)) {
        $placeholders = implode(',', array_fill(0, count($svc_ids), '%d'));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT serviceId, COUNT(*) AS cnt
             FROM $appts_table
             WHERE bookingStart >= %s
               AND serviceId IN ($placeholders)
               AND (status IS NULL OR status NOT IN ('canceled', 'rejected'))
             GROUP BY serviceId",
            ...array_merge(array($now_utc), $svc_ids)
        ));
        foreach ($rows as $r) $booked_counts[intval($r->serviceId)] = intval($r->cnt);
    }
}
?>
<style>
.bc-wrap { background: #D4F1E8; padding: 30px; border-radius: 16px; font-family: 'Quicksand', sans-serif; max-width: 1200px; margin-top: 20px; }
.bc-wrap h1 { font-family: 'Quicksand', sans-serif; font-weight: 700; font-size: 28px; color: #5A4A5A; margin-bottom: 24px; }
.bc-card { background: white; padding: 24px; border-radius: 16px; box-shadow: 0 4px 16px rgba(255, 181, 197, 0.12); margin-bottom: 20px; }
.bc-card h2 { font-family: 'Quicksand', sans-serif; font-weight: 700; font-size: 18px; color: #5A4A5A; margin: 0 0 16px 0; display: flex; align-items: center; gap: 10px; }
.bc-card h2 .dot { width: 10px; height: 10px; background: linear-gradient(135deg, #FFB5C5 0%, #E8829A 100%); border-radius: 50%; }
.bc-info { background: #FFF9F5; border: 2px solid #FFE4EC; border-radius: 12px; padding: 14px 18px; color: #5A4A5A; font-size: 13.5px; line-height: 1.6; margin-bottom: 18px; }
.bc-info code { background: #fff; padding: 1px 6px; border-radius: 4px; color: #E8829A; }
.bc-info strong { color: #5A4A5A; }
.bc-row { margin-bottom: 14px; }
.bc-row label { display: block; font-weight: 700; color: #5A4A5A; margin-bottom: 6px; font-size: 14px; }
.bc-row textarea, .bc-row input[type="text"] { width: 100%; padding: 10px 12px; border: 2px solid #FFE4EC; border-radius: 8px; font-family: 'Quicksand', sans-serif; font-size: 14px; color: #5A4A5A; background: #FFF9F5; }
.bc-row textarea:focus, .bc-row input[type="text"]:focus { outline: none; border-color: #98D9C2; background: #fff; }
.bc-toggle { display: inline-flex; align-items: center; gap: 10px; font-weight: 600; color: #5A4A5A; }
.bc-svc-table { width: 100%; border-collapse: collapse; }
.bc-svc-table th, .bc-svc-table td { padding: 10px 10px; vertical-align: middle; border-bottom: 1px solid #FFE4EC; font-size: 13.5px; color: #5A4A5A; }
.bc-svc-table thead th { background: #FFF9F5; font-weight: 700; }
.bc-svc-table textarea { width: 100%; min-height: 50px; padding: 8px 10px; border: 2px solid #FFE4EC; border-radius: 6px; font-family: 'Quicksand', sans-serif; font-size: 13px; }
.bc-svc-table textarea:focus { outline: none; border-color: #98D9C2; }
.bc-svc-table input[type="number"] { width: 84px; padding: 8px 10px; border: 2px solid #FFE4EC; border-radius: 6px; font-family: 'Quicksand', sans-serif; font-size: 14px; font-weight: 600; text-align: center; }
.bc-svc-table input[type="number"]:focus { outline: none; border-color: #98D9C2; }
.bc-svc-table .full-toggle { text-align: center; }
.bc-svc-table .full-toggle input { transform: scale(1.4); cursor: pointer; }
.bc-save { display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; background: linear-gradient(135deg, #98D9C2 0%, #5FBDA0 100%); color: white; font-family: 'Quicksand', sans-serif; font-weight: 700; font-size: 14px; border-radius: 25px; border: none; cursor: pointer; box-shadow: 0 4px 16px rgba(152, 217, 194, 0.35); }
.bc-save:hover { transform: translateY(-2px); }
.bc-svc-name { font-weight: 700; color: #5A4A5A; }
.bc-svc-id { color: #8A7A8A; font-size: 11.5px; }
.bc-live { display: inline-block; padding: 4px 10px; border-radius: 14px; font-weight: 700; font-size: 13px; }
.bc-live.ok { background: #D4F1E8; color: #2C6B58; }
.bc-live.warn { background: #FFE9D6; color: #8A4A1F; }
.bc-live.full { background: #FFD6DD; color: #842029; }
.bc-live.muted { background: #f1f1f1; color: #8A7A8A; font-weight: 500; }
</style>

<div class="wrap">
    <div class="bc-wrap">
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#5FBDA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
                <path d="M8 14h.01M12 14h.01M16 14h.01"></path>
            </svg>
            Booking Slot Counter
        </h1>

        <form method="post">
            <?php wp_nonce_field('amelia_bc_save', 'amelia_bc_nonce'); ?>

            <div class="bc-card">
                <h2><span class="dot"></span>How It Works</h2>
                <div class="bc-info">
                    <strong>Step 1.</strong> For each service, enter the <strong>total upcoming slots you've planned</strong>
                    in the table further down. (e.g. "I've opened 50 slots for the next 30 days.") Leave it at 0 to skip
                    auto-counting for that service.<br>
                    <strong>Step 2.</strong> Above your <code>[ameliabooking]</code> / <code>[ameliastepbooking]</code> shortcodes,
                    a panel will appear showing <code>X of Y slots booked — Z available</code>. The "booked" count comes
                    straight from <code>wp_amelia_appointments</code> (every approved/pending appointment in the future
                    for that service).<br>
                    <strong>Step 3.</strong> When booked reaches the total — or you tick the <strong>Mark as Fully Booked</strong>
                    box — the panel switches to your configurable waitlist message.<br><br>
                    <strong>Variables for the counter label:</strong> <code>{booked}</code> <code>{total}</code> <code>{remaining}</code> <code>{service}</code><br>
                    <strong>Per-page opt-out:</strong> <code>hide_counter="1"</code> on the Amelia booking shortcode<br>
                    <strong>Manual placement:</strong> <code>[amelia_booking_counter service_ids="1,2"]</code> or <code>[amelia_booking_full_message force="1"]</code>
                </div>

                <label class="bc-toggle">
                    <input type="checkbox" name="amelia_bc_enabled" value="1" <?php checked($enabled, 1); ?>>
                    <span>Enable this feature (master on/off)</span>
                </label>
            </div>

            <div class="bc-card">
                <h2><span class="dot"></span>Default Messages</h2>
                <div class="bc-row">
                    <label for="amelia_bc_counter_label_default">Default counter label</label>
                    <input type="text" id="amelia_bc_counter_label_default" name="amelia_bc_counter_label_default" value="<?php echo esc_attr($counter_label_default); ?>">
                    <small style="color: #8A7A8A;">Shown when slots are still available. Variables: <code>{booked}</code>, <code>{total}</code>, <code>{remaining}</code>, <code>{service}</code></small>
                </div>
                <div class="bc-row">
                    <label for="amelia_bc_full_message_default">Default fully-booked / waitlist message</label>
                    <textarea id="amelia_bc_full_message_default" name="amelia_bc_full_message_default" rows="3"><?php echo esc_textarea($full_default); ?></textarea>
                    <small style="color: #8A7A8A;">Shown when booked &ge; total OR a service's <em>Mark as Fully Booked</em> box is ticked.</small>
                </div>
            </div>

            <div class="bc-card">
                <h2><span class="dot"></span>Decay-Date Auto-Reminder</h2>
                <div class="bc-info">
                    Tell the addon how far in advance you'd like an email reminder when a service's slot pool is about to run out.
                    For each service that has a <strong>"Slots valid through"</strong> date set in the table below, this addon will
                    automatically create (or update, or delete) a matching entry in the <strong>Scheduled Lists</strong> feature
                    so an email is sent <em>{lead time}</em> days before that date.<br><br>
                    Auto-managed schedules are tagged so they won't conflict with anything you've set up manually in
                    <em>Amelia Addon → Scheduled Lists</em>.
                </div>
                <div class="bc-row">
                    <label for="amelia_bc_reminder_days">Reminder lead time (days before decay date)</label>
                    <input type="number" id="amelia_bc_reminder_days" name="amelia_bc_reminder_days" min="0" step="1" value="<?php echo intval($reminder_days); ?>" style="width: 100px;">
                    <small style="color: #8A7A8A; display: block; margin-top: 4px;">e.g. 7 = email arrives 7 days before the decay date. Set to 0 to disable auto-reminders entirely.</small>
                </div>
                <div class="bc-row">
                    <label for="amelia_bc_reminder_time">Reminder time of day (site timezone, HH:MM)</label>
                    <input type="text" id="amelia_bc_reminder_time" name="amelia_bc_reminder_time" value="<?php echo esc_attr($reminder_time); ?>" placeholder="09:00" pattern="[0-9]{1,2}:[0-9]{2}" style="width: 100px;">
                </div>
                <div class="bc-row">
                    <label for="amelia_bc_reminder_recipients">Default reminder recipient email(s) (comma-separated, optional)</label>
                    <input type="text" id="amelia_bc_reminder_recipients" name="amelia_bc_reminder_recipients" value="<?php echo esc_attr($reminder_recipients); ?>" placeholder="doctor@example.com, frontdesk@example.com">
                    <small style="color: #8A7A8A; display: block; margin-top: 4px;">Optional — the addon also pulls each service's own report email (set on the service's body-chart admin panel) and merges it into the recipient list automatically.</small>
                </div>
            </div>

            <div class="bc-card">
                <h2><span class="dot"></span>Per-Service Settings</h2>
                <div class="bc-info">
                    <strong>Total upcoming slots</strong> — Enter the number of slots you've made bookable for the upcoming
                    period. The counter compares this against the live count of future appointment rows in Amelia.
                    Leave at 0 to disable auto-counting for that service.<br>
                    <strong>Mark as Fully Booked</strong> — Manual override that always shows the waitlist message
                    (regardless of the total/booked numbers).<br>
                    <strong>Counter label / message overrides</strong> — Optional per-service text. Leave blank to use the defaults above.
                </div>
                <?php if (empty($services)): ?>
                    <p style="color: #8A7A8A;"><em>No Amelia services found yet.</em></p>
                <?php else: ?>
                    <table class="bc-svc-table">
                        <thead>
                            <tr>
                                <th style="width: 18%;">Service</th>
                                <th style="width: 9%;">Total upcoming slots</th>
                                <th style="width: 12%;">Slots valid through</th>
                                <th style="width: 12%; text-align: center;">Live status</th>
                                <th style="width: 9%; text-align: center;">Mark as<br>Fully Booked</th>
                                <th style="width: 19%;">Counter label override</th>
                                <th style="width: 21%;">Fully-booked message override</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $svc):
                                $sid     = intval($svc->id);
                                $fm_val  = isset($full_messages[$sid]) ? $full_messages[$sid] : '';
                                $cl_val  = isset($counter_labels[$sid]) ? $counter_labels[$sid] : '';
                                $tg_on   = !empty($full_toggles[$sid]);
                                $total   = isset($total_slots_map[$sid]) ? intval($total_slots_map[$sid]) : 0;
                                $booked  = isset($booked_counts[$sid]) ? intval($booked_counts[$sid]) : 0;
                                $decay   = isset($decay_dates[$sid]) ? $decay_dates[$sid] : '';

                                // Detect expired decay date for the live chip
                                $decay_passed = false;
                                if ($decay !== '') {
                                    try {
                                        $dt = new DateTime($decay . ' 23:59:59', wp_timezone());
                                        $today = new DateTime('now', wp_timezone());
                                        $today->setTime(0, 0, 0);
                                        if ($dt < $today) $decay_passed = true;
                                    } catch (Exception $e) {}
                                }

                                if ($tg_on) {
                                    $live_html = '<span class="bc-live full">Marked Full</span>';
                                } elseif ($decay_passed) {
                                    $live_html = '<span class="bc-live full">Decay passed</span>';
                                } elseif ($total <= 0) {
                                    $live_html = '<span class="bc-live muted">No total set</span>';
                                } elseif ($booked >= $total) {
                                    $live_html = '<span class="bc-live full">' . $booked . ' / ' . $total . ' Full</span>';
                                } elseif ($booked >= ($total * 0.8)) {
                                    $live_html = '<span class="bc-live warn">' . $booked . ' / ' . $total . '</span>';
                                } else {
                                    $live_html = '<span class="bc-live ok">' . $booked . ' / ' . $total . '</span>';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div class="bc-svc-name"><?php echo esc_html($svc->name); ?></div>
                                        <div class="bc-svc-id">#ID <?php echo $sid; ?></div>
                                    </td>
                                    <td>
                                        <input type="number" min="0" step="1" name="amelia_bc_total_slots[<?php echo $sid; ?>]" value="<?php echo $total > 0 ? $total : ''; ?>" placeholder="0">
                                    </td>
                                    <td>
                                        <input type="date" name="amelia_bc_decay_dates[<?php echo $sid; ?>]" value="<?php echo esc_attr($decay); ?>" style="width: 100%; padding: 8px 10px; border: 2px solid #FFE4EC; border-radius: 6px; font-family: 'Quicksand', sans-serif; font-size: 13px;">
                                    </td>
                                    <td style="text-align: center;">
                                        <?php echo $live_html; ?>
                                        <div class="bc-svc-id" style="margin-top: 4px;"><?php echo $booked; ?> upcoming booked</div>
                                    </td>
                                    <td class="full-toggle">
                                        <input type="checkbox" name="amelia_bc_full_toggles[<?php echo $sid; ?>]" value="1" <?php checked($tg_on, true); ?>>
                                    </td>
                                    <td>
                                        <textarea name="amelia_bc_counter_labels[<?php echo $sid; ?>]" placeholder="(use default)"><?php echo esc_textarea($cl_val); ?></textarea>
                                    </td>
                                    <td>
                                        <textarea name="amelia_bc_full_messages[<?php echo $sid; ?>]" placeholder="(use default)"><?php echo esc_textarea($fm_val); ?></textarea>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <p style="text-align: center; display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
                <button type="submit" class="bc-save">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Save Settings
                </button>
                <button type="submit" name="amelia_bc_purge_now" value="1" class="bc-save" style="background: linear-gradient(135deg, #FFB5C5 0%, #E8829A 100%); box-shadow: 0 4px 16px rgba(232, 130, 154, 0.35);" formnovalidate>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"></path>
                    </svg>
                    Purge page cache now
                </button>
            </p>
        </form>
    </div>
</div>
