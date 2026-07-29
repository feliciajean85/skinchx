<?php
/**
 * Cron Handler V3 - Executes scheduled exports with proper logging
 */

if (!defined('ABSPATH')) {
    exit;
}

class AALS_Cron_Handler {
    
    public function __construct() {
        // Periodic checker for recalculating trigger times
        add_action('aals_check_schedules', array($this, 'check_and_recalculate'));
    }
    
    /**
     * Periodically check all active schedules and recalculate trigger times
     * This catches any changes in appointment data
     */
    public function check_and_recalculate() {
        $schedules = AALS_Database::get_active_schedules();
        $manager = new AALS_Schedule_Manager();
        
        foreach ($schedules as $schedule) {
            $manager->recalculate_trigger_time($schedule->id);
        }
        
        // Also check for any "Ready" schedules that should run
        $this->run_ready_schedules();
    }
    
    /**
     * Run any schedules that are in "Ready" status
     */
    private function run_ready_schedules() {
        global $wpdb;
        $table = $wpdb->prefix . 'aals_schedules';
        
        $ready_schedules = $wpdb->get_results(
            "SELECT id FROM $table WHERE status = 'Ready' AND enabled = 1"
        );
        
        foreach ($ready_schedules as $schedule) {
            $this->execute_schedule($schedule->id, true, 'scheduled_cron');
        }
    }
    
    /**
     * Execute a schedule - fetch fresh data and send email
     * 
     * @param int $schedule_id
     * @param bool $mark_complete If true, sets status to Sent
     * @param string $trigger_source What triggered this execution: 'manual_send_now', 'manual_keep_active', 'scheduled_cron', 'scheduled_trigger'
     */
    public function execute_schedule($schedule_id, $mark_complete = true, $trigger_source = 'unknown') {
        $schedule = AALS_Database::get_schedule($schedule_id);
        
        if (!$schedule) {
            return array(
                'success' => false,
                'error' => 'Schedule not found'
            );
        }
        
        // Check if already sent (idempotency)
        if ($schedule->status === 'Sent') {
            return array(
                'success' => false,
                'error' => 'Schedule already sent'
            );
        }
        
        // Check if disabled
        if (!$schedule->enabled) {
            return array(
                'success' => false,
                'error' => 'Schedule is disabled'
            );
        }
        
        // Determine schedule type
        $schedule_type = isset($schedule->schedule_type) ? $schedule->schedule_type : 'attendee_list';
        
        // For group report reminders, use simplified flow
        if ($schedule_type === 'group_report_reminder') {
            return $this->execute_group_report_reminder($schedule, $mark_complete, $trigger_source);
        }
        
        // Original attendee list flow continues below...
        
        // Atomic status transition
        $old_status = $schedule->status;
        $transitioned = AALS_Database::update_status_atomic($schedule_id, $old_status, 'Sending');
        
        if (!$transitioned) {
            return array(
                'success' => false,
                'error' => 'Concurrent execution prevented'
            );
        }
        
        // Determine action name based on trigger source
        $action_name = $this->get_action_name($trigger_source, $mark_complete);
        
        // Log start with trigger source
        AALS_Database::insert_log(array(
            'schedule_id' => $schedule_id,
            'action' => $action_name . '_start',
            'result' => 'started',
            'error_message' => 'Execution started via ' . $this->get_trigger_label($trigger_source)
        ));
        
        // Update last_run timestamp
        AALS_Database::update_schedule($schedule_id, array(
            'last_run_utc' => current_time('mysql', true)
        ));
        
        // Initialize providers
        $manager = new AALS_Schedule_Manager();
        
        // Get service IDs (multi-service support)
        $service_ids = array();
        if (!empty($schedule->service_ids_json)) {
            $decoded = json_decode($schedule->service_ids_json, true);
            if (is_array($decoded)) {
                $service_ids = $decoded;
            }
        }
        if (empty($service_ids) && !empty($schedule->service_id)) {
            $service_ids = array($schedule->service_id);
        }
        
        // Fetch ALL future appointments from ALL services (fresh data)
        $result = $manager->get_all_future_appointments_multi($service_ids, $schedule->appointment_status);
        $data_source = $result['data_source'];
        
        if (!$result['success']) {
            $this->handle_failure($schedule_id, $old_status, 'Failed to fetch appointments: ' . $result['error'], $data_source, $action_name);
            return array(
                'success' => false,
                'error' => $result['error']
            );
        }
        
        $appointments = $result['appointments'];
        
        // Log data fetch
        AALS_Database::insert_log(array(
            'schedule_id' => $schedule_id,
            'action' => $action_name . '_fetch',
            'result' => 'success',
            'error_message' => 'Found ' . count($appointments) . ' appointments from ' . count($service_ids) . ' service(s)',
            'data_source' => $data_source,
            'rows_exported' => count($appointments)
        ));
        
        if (empty($appointments)) {
            AALS_Database::insert_log(array(
                'schedule_id' => $schedule_id,
                'action' => $action_name,
                'result' => 'skipped',
                'error_message' => 'No appointments to export - ' . $this->get_trigger_label($trigger_source),
                'data_source' => $data_source
            ));
            
            // Restore old status or complete based on mark_complete
            if ($mark_complete) {
                AALS_Database::update_schedule($schedule_id, array(
                    'status' => 'Sent',
                    'status_message' => 'Completed with no appointments',
                    'completed_utc' => current_time('mysql', true),
                    'next_trigger_utc' => null,
                    'next_appointment_id' => null
                ));
            } else {
                AALS_Database::update_schedule($schedule_id, array(
                    'status' => $old_status,
                    'status_message' => 'No appointments found'
                ));
            }
            
            return array(
                'success' => true,
                'rows_exported' => 0,
                'message' => 'No appointments to export'
            );
        }
        
        // Generate CSV
        $csv_exporter = new AALS_CSV_Exporter();
        $csv_result = $csv_exporter->export(
            $appointments,
            $schedule->csv_fields_json,
            $schedule->service_id
        );
        
        if (!$csv_result['success']) {
            $this->handle_failure($schedule_id, $old_status, 'CSV export failed: ' . $csv_result['error'], $data_source, $action_name);
            return array(
                'success' => false,
                'error' => $csv_result['error']
            );
        }
        
        // Log CSV success
        AALS_Database::insert_log(array(
            'schedule_id' => $schedule_id,
            'action' => $action_name . '_csv',
            'result' => 'success',
            'error_message' => 'CSV created: ' . $csv_result['filename'],
            'data_source' => $data_source,
            'rows_exported' => $csv_result['row_count'],
            'attachment_name' => $csv_result['filename']
        ));
        
        // Send email
        $email_sender = new AALS_Email_Sender();
        $email_result = $email_sender->send(
            $schedule,
            $csv_result['filepath'],
            $csv_result['filename'],
            $csv_result['row_count'],
            $data_source
        );
        
        if (!$email_result['success']) {
            $this->handle_failure($schedule_id, $old_status, 'Email failed: ' . $email_result['error'], $data_source, $action_name);
            return array(
                'success' => false,
                'error' => $email_result['error']
            );
        }
        
        // Success!
        if ($mark_complete) {
            AALS_Database::update_schedule($schedule_id, array(
                'status' => 'Sent',
                'status_message' => 'Successfully sent to ' . $schedule->recipients . ' via ' . $this->get_trigger_label($trigger_source),
                'completed_utc' => current_time('mysql', true),
                'next_trigger_utc' => null,
                'next_appointment_id' => null
            ));
            
            // Clear cron event
            wp_clear_scheduled_hook('aals_send_schedule_' . $schedule_id);
        } else {
            // Keep as scheduled - will recalculate on next check
            AALS_Database::update_schedule($schedule_id, array(
                'status' => 'Active',
                'status_message' => 'Sent manually, still active'
            ));
        }
        
        // Log final success with clear trigger info
        AALS_Database::insert_log(array(
            'schedule_id' => $schedule_id,
            'action' => $action_name,
            'result' => 'success',
            'error_message' => 'Email sent successfully via ' . $this->get_trigger_label($trigger_source),
            'data_source' => $data_source,
            'rows_exported' => $csv_result['row_count'],
            'recipients' => $schedule->recipients,
            'subject' => $email_result['subject'],
            'attachment_name' => $csv_result['filename']
        ));
        
        return array(
            'success' => true,
            'rows_exported' => $csv_result['row_count'],
            'data_source' => $data_source
        );
    }
    
    /**
     * Get human-readable trigger label
     */
    private function get_trigger_label($trigger_source) {
        $labels = array(
            'manual_send_now' => 'Manual Send Now (Mark Complete)',
            'manual_keep_active' => 'Manual Send Now (Keep Active)',
            'scheduled_cron' => 'Scheduled Automation',
            'scheduled_trigger' => 'Scheduled Trigger Time',
            'unknown' => 'Unknown Trigger'
        );
        
        return isset($labels[$trigger_source]) ? $labels[$trigger_source] : $trigger_source;
    }
    
    /**
     * Get action name for logging
     */
    private function get_action_name($trigger_source, $mark_complete) {
        if ($trigger_source === 'manual_send_now' || $trigger_source === 'manual_keep_active') {
            return $mark_complete ? 'manual_complete' : 'manual_active';
        }
        return 'auto_scheduled';
    }
    
    /**
     * Handle failure - log and restore status
     */
    private function handle_failure($schedule_id, $old_status, $error, $data_source = null, $action_name = 'send') {
        AALS_Database::update_schedule($schedule_id, array(
            'status' => 'Failed',
            'status_message' => $error
        ));
        
        AALS_Database::insert_log(array(
            'schedule_id' => $schedule_id,
            'action' => $action_name,
            'result' => 'failed',
            'error_message' => $error,
            'data_source' => $data_source
        ));
    }
    
    /**
     * Execute a Group Report Reminder - sends notification email (and optionally WhatsApp)
     */
    private function execute_group_report_reminder($schedule, $mark_complete, $trigger_source) {
        $schedule_id = $schedule->id;
        $old_status = $schedule->status;
        
        // Atomic status transition
        $transitioned = AALS_Database::update_status_atomic($schedule_id, $old_status, 'Sending');
        
        if (!$transitioned) {
            return array(
                'success' => false,
                'error' => 'Concurrent execution prevented'
            );
        }
        
        $action_name = 'group_report_reminder';
        
        // Log start
        AALS_Database::insert_log(array(
            'schedule_id' => $schedule_id,
            'action' => $action_name . '_start',
            'result' => 'started',
            'error_message' => 'Group report reminder triggered via ' . $this->get_trigger_label($trigger_source)
        ));
        
        // Update last_run timestamp
        AALS_Database::update_schedule($schedule_id, array(
            'last_run_utc' => current_time('mysql', true)
        ));
        
        // Build email content (reusable — same logic is used by preview)
        $built = $this->build_reminder_email_content($schedule);
        $subject = $built['subject'];
        $body = $built['body'];
        
        // Recipients
        $recipients = array_map('trim', explode(',', $schedule->recipients));
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8'
        );
        
        $sent = wp_mail($recipients, $subject, $body, $headers);
        
        if (!$sent) {
            $this->handle_failure($schedule_id, $old_status, 'wp_mail() failed for reminder', null, $action_name);
            return array(
                'success' => false,
                'error' => 'Failed to send reminder email'
            );
        }
        
        // Success!
        if ($mark_complete) {
            AALS_Database::update_schedule($schedule_id, array(
                'status' => 'Sent',
                'status_message' => 'Reminder sent to ' . $schedule->recipients,
                'completed_utc' => current_time('mysql', true),
                'next_trigger_utc' => null,
                'next_appointment_id' => null
            ));
            
            // Clear cron event
            wp_clear_scheduled_hook('aals_send_schedule_' . $schedule_id);
        } else {
            // Keep active for recurring reminders
            AALS_Database::update_schedule($schedule_id, array(
                'status' => 'Active',
                'status_message' => 'Reminder sent, still active'
            ));
        }
        
        // Log success
        AALS_Database::insert_log(array(
            'schedule_id' => $schedule_id,
            'action' => $action_name,
            'result' => 'success',
            'error_message' => 'Reminder email sent via ' . $this->get_trigger_label($trigger_source),
            'recipients' => $schedule->recipients,
            'subject' => $subject
        ));
        
        return array(
            'success' => true,
            'message' => 'Reminder sent successfully'
        );
    }
    
    /**
     * Build the resolved reminder email (subject + body) for a given schedule.
     * Used by both execute_group_report_reminder() and the preview handler.
     *
     * @return array{subject:string, body:string, subject_raw:string, body_raw:string, service_list:string, context:array}
     */
    public function build_reminder_email_content($schedule) {
        $schedule_id = isset($schedule->id) ? $schedule->id : 0;
        
        // Get service names for the email
        $service_names = array();
        if (!empty($schedule->service_names_cache)) {
            $service_names = json_decode($schedule->service_names_cache, true);
        }
        if (empty($service_names) && !empty($schedule->service_name_cache)) {
            $service_names = array($schedule->service_name_cache);
        }
        $service_list = !empty($service_names) ? implode(', ', $service_names) : 'Selected Services';
        
        // Get last appointment info for placeholders
        $last_appt_context = $this->get_last_appointment_context($schedule);
        
        // Resolve defaults from settings (fall back to hardcoded if option not set)
        $default_subject = get_option('aals_default_reminder_email_subject', 'Group Report Reminder: {services}');
        $default_body = get_option('aals_default_reminder_email_body', "Hi {customer_name},\n\nThe appointments for {services} have completed.\n\nLast appointment: {last_appointment_date} at {last_appointment_time}\nTime since last appointment: {hours_since_appointment} hours\n\nPlease review and send the group report.\n\n---\nThis is an automated reminder from the SkinChX Addon plugin.\nSchedule ID: #{schedule_id}\nGenerated: {date} at {time}");
        
        // Use custom subject/body if set, otherwise use the configured default
        $raw_subject = !empty($schedule->email_subject) ? $schedule->email_subject : $default_subject;
        $subject = $this->replace_reminder_variables($raw_subject, $service_list, $schedule_id, $last_appt_context);
        
        $raw_body = !empty($schedule->email_body) ? $schedule->email_body : $default_body;
        $body = $this->replace_reminder_variables($raw_body, $service_list, $schedule_id, $last_appt_context);
        
        // If the body is plain text (no HTML tags), convert newlines to <br>.
        // Wrap in a styled HTML template if it doesn't already have <html>/<body>.
        if (stripos($body, '<html') === false && stripos($body, '<body') === false) {
            // Only apply nl2br if body doesn't already contain HTML block tags
            if (!preg_match('/<(p|div|br|table|h[1-6])/i', $body)) {
                $body = nl2br($body);
            }
            $body = $this->wrap_simple_reminder_email($body);
        }
        
        return array(
            'subject' => $subject,
            'body' => $body,
            'subject_raw' => $raw_subject,
            'body_raw' => $raw_body,
            'service_list' => $service_list,
            'context' => $last_appt_context
        );
    }
    
    /**
     * Fetch last appointment context (date, time, customer name, hours since) for placeholder replacement.
     * Uses the schedule's linked services; falls back to empty strings if no appointment found.
     * Public for reuse in preview generation.
     */
    public function get_last_appointment_context($schedule) {
        global $wpdb;
        
        $context = array(
            'last_appointment_date' => '',
            'last_appointment_time' => '',
            'last_appointment_datetime' => '',
            'customer_name' => 'there',
            'hours_since_appointment' => ''
        );
        
        // Collect service IDs from the schedule
        $service_ids = array();
        if (!empty($schedule->service_ids_json)) {
            $decoded = json_decode($schedule->service_ids_json, true);
            if (is_array($decoded)) {
                $service_ids = array_map('intval', $decoded);
            }
        }
        if (empty($service_ids) && !empty($schedule->service_id)) {
            $service_ids = array(intval($schedule->service_id));
        }
        
        if (empty($service_ids)) {
            return $context;
        }
        
        $service_ids_str = implode(',', $service_ids);
        
        // Look up the most recent (already ended) appointment for these services
        $appointments_table = $wpdb->prefix . 'amelia_appointments';
        $customers_bookings_table = $wpdb->prefix . 'amelia_customer_bookings';
        $users_table = $wpdb->prefix . 'amelia_users';
        
        // Check tables exist
        $appointments_exists = $wpdb->get_var("SHOW TABLES LIKE '$appointments_table'") === $appointments_table;
        if (!$appointments_exists) {
            return $context;
        }
        
        $last = $wpdb->get_row(
            "SELECT a.bookingStart, a.bookingEnd, a.id as appointment_id
             FROM $appointments_table a
             WHERE a.serviceId IN ($service_ids_str)
             ORDER BY a.bookingEnd DESC
             LIMIT 1"
        );
        
        if (!$last || empty($last->bookingEnd)) {
            return $context;
        }
        
        // Format in site timezone
        try {
            $end_utc = new DateTime($last->bookingEnd, new DateTimeZone('UTC'));
            $end_local = clone $end_utc;
            $end_local->setTimezone(wp_timezone());
            
            $context['last_appointment_date'] = $end_local->format('F j, Y');
            $context['last_appointment_time'] = $end_local->format('g:i A');
            $context['last_appointment_datetime'] = $end_local->format('F j, Y \a\t g:i A');
            
            // Hours since (based on UTC)
            $now_ts = current_time('timestamp', true);
            $end_ts = $end_utc->getTimestamp();
            $hours = max(0, intval(round(($now_ts - $end_ts) / 3600)));
            $context['hours_since_appointment'] = (string) $hours;
        } catch (Exception $e) {
            // keep defaults
        }
        
        // Try to fetch the first customer name for this appointment (if available)
        $customer_bookings_exists = $wpdb->get_var("SHOW TABLES LIKE '$customers_bookings_table'") === $customers_bookings_table;
        $users_exists = $wpdb->get_var("SHOW TABLES LIKE '$users_table'") === $users_table;
        
        if ($customer_bookings_exists && $users_exists && !empty($last->appointment_id)) {
            $customer = $wpdb->get_row($wpdb->prepare(
                "SELECT u.firstName, u.lastName
                 FROM $customers_bookings_table cb
                 JOIN $users_table u ON cb.customerId = u.id
                 WHERE cb.appointmentId = %d
                 ORDER BY cb.id ASC
                 LIMIT 1",
                intval($last->appointment_id)
            ));
            
            if ($customer) {
                $name = trim(trim($customer->firstName) . ' ' . trim($customer->lastName));
                if (!empty($name)) {
                    $context['customer_name'] = $name;
                }
            }
        }
        
        return $context;
    }
    
    /**
     * Replace variables in reminder templates (public for reuse in preview)
     */
    public function replace_reminder_variables($text, $service_list, $schedule_id, $last_appt_context = array()) {
        $wp_timezone = wp_timezone();
        $now = new DateTime('now', $wp_timezone);
        
        $defaults = array(
            'last_appointment_date' => '',
            'last_appointment_time' => '',
            'last_appointment_datetime' => '',
            'customer_name' => 'there',
            'hours_since_appointment' => ''
        );
        $ctx = array_merge($defaults, is_array($last_appt_context) ? $last_appt_context : array());
        
        $replacements = array(
            '{service_name}' => $service_list,
            '{services}' => $service_list,
            '{schedule_id}' => $schedule_id,
            '{date}' => $now->format('Y-m-d'),
            '{time}' => $now->format('H:i'),
            '{customer_name}' => $ctx['customer_name'],
            '{last_appointment_date}' => $ctx['last_appointment_date'],
            '{last_appointment_time}' => $ctx['last_appointment_time'],
            '{last_appointment_datetime}' => $ctx['last_appointment_datetime'],
            '{hours_since_appointment}' => $ctx['hours_since_appointment']
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
    
    /**
     * Wrap custom body in simple HTML email template (public for preview reuse).
     */
    public function wrap_simple_reminder_email($body) {
        $html = "<html><body style='font-family: Arial, sans-serif; line-height: 1.6;'>";
        $html .= "<div style='max-width: 600px; margin: 0 auto; padding: 20px;'>";
        $html .= $body;
        $html .= "</div></body></html>";
        return $html;
    }
    
    /**
     * Simple reminder email body (no link)
     */
    private function get_simple_reminder_body($service_list, $schedule_id) {
        $wp_timezone = wp_timezone();
        $now = new DateTime('now', $wp_timezone);
        
        $html = "<html><body style='font-family: Arial, sans-serif; line-height: 1.6;'>";
        $html .= "<div style='max-width: 600px; margin: 0 auto; padding: 20px;'>";
        $html .= "<h2 style='color: #5A4A5A; border-bottom: 2px solid #98D9C2; padding-bottom: 10px;'>Group Report Reminder</h2>";
        $html .= "<p style='color: #5A4A5A; font-size: 16px;'>The appointments have completed.</p>";
        $html .= "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
        $html .= "<tr><td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Services:</strong></td><td style='padding: 8px; border-bottom: 1px solid #eee;'>" . esc_html($service_list) . "</td></tr>";
        $html .= "<tr><td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Time:</strong></td><td style='padding: 8px; border-bottom: 1px solid #eee;'>" . $now->format('F j, Y g:i A') . "</td></tr>";
        $html .= "</table>";
        $html .= "<p style='color: #5A4A5A; font-size: 16px;'><strong>Please review and send the group report.</strong></p>";
        $html .= "<hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>";
        $html .= "<p style='color: #999; font-size: 12px;'>This is an automated reminder from the SkinChX Addon plugin.</p>";
        $html .= "</div></body></html>";
        
        return $html;
    }
    
    /**
     * Register dynamic cron hook for a specific schedule
     */
    public static function register_schedule_hook($schedule_id) {
        $hook = 'aals_send_schedule_' . $schedule_id;
        
        add_action($hook, function($sid) {
            $handler = new AALS_Cron_Handler();
            $handler->execute_schedule($sid, true, 'scheduled_trigger');
        });
    }
}

// Register dynamic hooks on init
add_action('init', function() {
    global $wpdb;
    $table = $wpdb->prefix . 'aals_schedules';
    
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    
    if ($table_exists) {
        $schedules = $wpdb->get_results(
            "SELECT id FROM $table WHERE status NOT IN ('Sent', 'Canceled') AND enabled = 1"
        );
        
        foreach ($schedules as $schedule) {
            AALS_Cron_Handler::register_schedule_hook($schedule->id);
        }
    }
});
