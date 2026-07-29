<?php
/**
 * AJAX Handler for V2 - Preview and Run Now features
 */

if (!defined('ABSPATH')) {
    exit;
}

class AALS_Ajax_Handler {
    
    public function __construct() {
        add_action('wp_ajax_aals_preview', array($this, 'handle_preview'));
        add_action('wp_ajax_aals_run_now', array($this, 'handle_run_now'));
        add_action('wp_ajax_aals_get_logs', array($this, 'handle_get_logs'));
        add_action('wp_ajax_aals_get_last_appointment', array($this, 'handle_get_last_appointment'));
    }
    
    /**
     * Handle last-appointment lookup for a set of service IDs (for edit-schedule UI)
     */
    public function handle_get_last_appointment() {
        check_ajax_referer('aals_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $service_ids = isset($_POST['service_ids']) ? (array) $_POST['service_ids'] : array();
        $service_ids = array_values(array_filter(array_map('intval', $service_ids)));
        
        if (empty($service_ids)) {
            wp_send_json_success(array('found' => false, 'message' => 'Select services to see the last appointment'));
        }
        
        global $wpdb;
        $appointments_table = $wpdb->prefix . 'amelia_appointments';
        $services_table = $wpdb->prefix . 'amelia_services';
        
        // Guard: tables may not exist on fresh Amelia installs
        $appt_exists = $wpdb->get_var("SHOW TABLES LIKE '$appointments_table'") === $appointments_table;
        $svc_exists = $wpdb->get_var("SHOW TABLES LIKE '$services_table'") === $services_table;
        if (!$appt_exists || !$svc_exists) {
            wp_send_json_success(array('found' => false, 'message' => 'Amelia tables not available'));
        }
        
        $placeholders = implode(',', array_fill(0, count($service_ids), '%d'));
        $query = $wpdb->prepare(
            "SELECT a.bookingStart, a.bookingEnd, s.name AS service_name
             FROM $appointments_table a
             JOIN $services_table s ON a.serviceId = s.id
             WHERE a.serviceId IN ($placeholders)
             AND a.status = 'approved'
             ORDER BY a.bookingEnd DESC
             LIMIT 1",
            ...$service_ids
        );
        $row = $wpdb->get_row($query);
        
        if (!$row) {
            wp_send_json_success(array('found' => false, 'message' => 'No approved appointments found for the selected services'));
        }
        
        // Format in site timezone
        try {
            $dt = new DateTime($row->bookingEnd, new DateTimeZone('UTC'));
            $dt->setTimezone(wp_timezone());
            $formatted = $dt->format('F j, Y \a\t g:i A');
            $date_only = $dt->format('Y-m-d');
            $time_only = $dt->format('H:i');
        } catch (Exception $e) {
            $formatted = $row->bookingEnd;
            $date_only = '';
            $time_only = '';
        }
        
        wp_send_json_success(array(
            'found' => true,
            'service_name' => $row->service_name,
            'formatted' => $formatted,
            'date' => $date_only,
            'time' => $time_only
        ));
    }
    
    /**
     * Handle preview request - generates CSV and email preview without sending
     */
    public function handle_preview() {
        check_ajax_referer('aals_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
        
        if (!$schedule_id) {
            wp_send_json_error(array('message' => 'Invalid schedule ID'));
        }
        
        $manager = new AALS_Schedule_Manager();
        $result = $manager->generate_preview($schedule_id);
        
        if ($result['success']) {
            // Forward all result fields (excluding success) for the frontend to render
            unset($result['success']);
            wp_send_json_success($result);
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }
    
    /**
     * Handle Run Now request
     */
    public function handle_run_now() {
        check_ajax_referer('aals_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
        $mark_complete = isset($_POST['mark_complete']) ? ($_POST['mark_complete'] === 'true' || $_POST['mark_complete'] === '1') : true;
        
        if (!$schedule_id) {
            wp_send_json_error(array('message' => 'Invalid schedule ID'));
        }
        
        $manager = new AALS_Schedule_Manager();
        $result = $manager->run_schedule_now($schedule_id, $mark_complete);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => 'Email sent successfully',
                'rows_exported' => $result['rows_exported'],
                'data_source' => isset($result['data_source']) ? $result['data_source'] : 'unknown',
                'marked_complete' => $mark_complete
            ));
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }
    
    /**
     * Get logs for a schedule
     */
    public function handle_get_logs() {
        check_ajax_referer('aals_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
        
        if (!$schedule_id) {
            wp_send_json_error(array('message' => 'Invalid schedule ID'));
        }
        
        $logs = AALS_Database::get_logs($schedule_id, 20);
        
        $formatted_logs = array();
        foreach ($logs as $log) {
            $formatted_logs[] = array(
                'id' => $log->id,
                'time' => $log->attempt_utc,
                'action' => $log->action,
                'result' => $log->result,
                'message' => $log->error_message,
                'data_source' => $log->data_source,
                'rows' => $log->rows_exported
            );
        }
        
        wp_send_json_success(array('logs' => $formatted_logs));
    }
}
