<?php
/**
 * Admin Menu V3 - Integrated as submenu under Amelia Addon
 * Modified to work as part of the combined plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class AALS_Admin_Menu {
    
    public function __construct() {
        // Menu registration is now handled by Wpamelia_Addon_Admin class
        // We only need to handle assets and actions here
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_init', array($this, 'handle_actions'));
    }
    
    public function register_menu() {
        // Register as submenu under Amelia Addon (amelia-report is the parent slug)
        add_submenu_page(
            'amelia-report',  // Parent menu slug (from Amelia Addon)
            'Scheduled Lists',  // Page title
            'Scheduled Lists',  // Menu title
            'manage_options',
            'amelia-appt-list-schedule',
            array($this, 'render_page')
        );
    }
    
    public function enqueue_assets($hook) {
        if (strpos($hook, 'amelia-appt-list-schedule') === false) {
            return;
        }
        
        wp_enqueue_style(
            'aals-admin-style',
            AALS_PLUGIN_URL . 'admin/assets/css/admin.css',
            array(),
            AALS_VERSION
        );
        
        // Enqueue Kawaii Admin styles
        wp_enqueue_style(
            'kawaii-admin-css',
            WPAMELIA_ADDON_PLUGIN_URL . 'admin/css/kawaii-admin.css',
            array(),
            WPAMELIA_ADDON_VERSION
        );
        
        wp_enqueue_script(
            'aals-admin-script',
            AALS_PLUGIN_URL . 'admin/assets/js/admin.js',
            array('jquery'),
            AALS_VERSION,
            true
        );
        
        wp_localize_script('aals-admin-script', 'aals_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aals_ajax_nonce')
        ));
    }
    
    public function handle_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'amelia-appt-list-schedule') {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        
        // Handle delete
        if ($action === 'delete' && isset($_GET['id']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_schedule_' . $_GET['id'])) {
                $manager = new AALS_Schedule_Manager();
                $manager->delete_schedule(intval($_GET['id']));
                
                wp_redirect(admin_url('admin.php?page=amelia-appt-list-schedule&message=deleted'));
                exit;
            }
        }
        
        // Handle permanent delete
        if ($action === 'permanent_delete' && isset($_GET['id']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'perm_delete_schedule_' . $_GET['id'])) {
                $manager = new AALS_Schedule_Manager();
                $manager->permanently_delete(intval($_GET['id']));
                
                wp_redirect(admin_url('admin.php?page=amelia-appt-list-schedule&message=deleted'));
                exit;
            }
        }
        
        // Handle export logs to CSV
        if ($action === 'export_logs' && isset($_GET['id']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'export_logs_' . $_GET['id'])) {
                $this->export_logs_csv(intval($_GET['id']));
            }
        }
        
        // Handle save
        if ($action === 'save' && isset($_POST['aals_nonce'])) {
            if (wp_verify_nonce($_POST['aals_nonce'], 'aals_save_schedule')) {
                $this->handle_save();
            }
        }
    }
    
    /**
     * Export logs to CSV
     */
    private function export_logs_csv($schedule_id) {
        $logs = AALS_Database::get_logs($schedule_id, 1000);
        
        if (empty($logs)) {
            wp_redirect(admin_url('admin.php?page=amelia-appt-list-schedule&action=logs&id=' . $schedule_id . '&message=no_logs'));
            exit;
        }
        
        $filename = 'schedule_' . $schedule_id . '_logs_' . date('Ymd-His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, array(
            'Log ID',
            'Schedule ID',
            'Time (UTC)',
            'Action',
            'Result',
            'Message',
            'Data Source',
            'Rows Exported',
            'Recipients',
            'Subject',
            'Attachment'
        ));
        
        // Data rows
        foreach ($logs as $log) {
            fputcsv($output, array(
                $log->id,
                $log->schedule_id,
                $log->attempt_utc,
                $log->action,
                $log->result,
                $log->error_message,
                $log->data_source,
                $log->rows_exported,
                $log->recipients,
                $log->subject,
                $log->attachment_name
            ));
        }
        
        fclose($output);
        exit;
    }
    
    private function handle_save() {
        $manager = new AALS_Schedule_Manager();
        
        // Get schedule type
        $schedule_type = isset($_POST['schedule_type']) ? sanitize_text_field($_POST['schedule_type']) : 'attendee_list';
        
        // Handle multi-service (array of service IDs)
        $service_ids = isset($_POST['service_ids']) ? array_map('intval', $_POST['service_ids']) : array();
        
        // Fallback for single service (backwards compatibility)
        if (empty($service_ids) && isset($_POST['service_id'])) {
            $service_ids = array(intval($_POST['service_id']));
        }
        
        // Get service names for cache
        $services_result = $manager->get_provider()->get_services();
        $service_names = array();
        
        if ($services_result['success']) {
            foreach ($services_result['services'] as $service) {
                if (in_array($service['id'], $service_ids)) {
                    $service_names[$service['id']] = $service['name'];
                }
            }
        }
        
        // Handle scheduled datetime for group report reminders
        $next_trigger_utc = null;
        if ($schedule_type === 'group_report_reminder') {
            $scheduled_date = isset($_POST['scheduled_date']) ? sanitize_text_field($_POST['scheduled_date']) : '';
            $scheduled_time = isset($_POST['scheduled_time']) ? sanitize_text_field($_POST['scheduled_time']) : '17:00';
            
            if ($scheduled_date) {
                // Convert local time to UTC
                $local_datetime = $scheduled_date . ' ' . $scheduled_time . ':00';
                $dt = new DateTime($local_datetime, wp_timezone());
                $dt->setTimezone(new DateTimeZone('UTC'));
                $next_trigger_utc = $dt->format('Y-m-d H:i:s');
            }
        }
        
        // Prepare data
        $data = array(
            'schedule_type' => $schedule_type,
            'service_ids_json' => json_encode($service_ids),
            'service_names_cache' => json_encode(array_values($service_names)),
            // Keep single service_id for backwards compat (use first one)
            'service_id' => !empty($service_ids) ? $service_ids[0] : 0,
            'service_name_cache' => !empty($service_names) ? reset($service_names) : '',
            'recipients' => sanitize_textarea_field($_POST['recipients']),
            'time_value' => intval($_POST['time_value'] ?? 24),
            'time_unit' => sanitize_text_field($_POST['time_unit'] ?? 'hours'),
            'time_direction' => isset($_POST['time_direction']) ? sanitize_text_field($_POST['time_direction']) : 'before',
            'enabled' => isset($_POST['enabled']) ? 1 : 0,
            'appointment_status' => !empty($_POST['appointment_status']) ? sanitize_text_field($_POST['appointment_status']) : 'approved',
            'csv_fields_json' => $schedule_type === 'attendee_list' ? json_encode($_POST['csv_fields'] ?? array('start_datetime')) : '["none"]',
            'email_subject' => !empty($_POST['email_subject']) ? sanitize_text_field($_POST['email_subject']) : '',
            'email_body' => !empty($_POST['email_body']) ? wp_kses_post($_POST['email_body']) : '',
        );
        
        // For group report reminders, set the specific trigger time
        if ($schedule_type === 'group_report_reminder' && $next_trigger_utc) {
            $data['next_trigger_utc'] = $next_trigger_utc;
            $data['status'] = 'Scheduled';
            $data['status_message'] = 'Scheduled for ' . $scheduled_date . ' at ' . $scheduled_time;
        }
        
        // Create or update
        $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
        
        if ($schedule_id) {
            $result = $manager->update_schedule($schedule_id, $data);
        } else {
            $result = $manager->create_schedule($data);
            $schedule_id = $result['schedule_id'] ?? 0;
        }
        
        // For group report reminders, also schedule the cron event
        if ($result['success'] && $schedule_type === 'group_report_reminder' && $next_trigger_utc) {
            $trigger_timestamp = strtotime($next_trigger_utc);
            wp_clear_scheduled_hook('aals_send_schedule_' . $schedule_id);
            wp_schedule_single_event($trigger_timestamp, 'aals_send_schedule_' . $schedule_id, array($schedule_id));
        }
        
        if ($result['success']) {
            wp_redirect(admin_url('admin.php?page=amelia-appt-list-schedule&message=saved'));
        } else {
            $errors = implode(', ', $result['errors']);
            wp_redirect(admin_url('admin.php?page=amelia-appt-list-schedule&action=edit&id=' . $schedule_id . '&message=error&error=' . urlencode($errors)));
        }
        exit;
    }
    
    public function render_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        echo '<div class="wrap">';
        echo '<div class="kawaii-wrap">';
        echo '<h1 class="kawaii-page-title">Scheduled Attendee List</h1>';
        
        $this->display_messages();
        
        switch ($action) {
            case 'edit':
            case 'new':
                $this->render_edit_page();
                break;
                
            case 'logs':
                $this->render_logs_page();
                break;
                
            default:
                $this->render_list_page();
                break;
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    private function display_messages() {
        if (!isset($_GET['message'])) {
            return;
        }
        
        $message = sanitize_text_field($_GET['message']);
        
        $messages = array(
            'saved' => array('type' => 'success', 'text' => 'Schedule saved successfully. Trigger time has been calculated.'),
            'deleted' => array('type' => 'success', 'text' => 'Schedule canceled/deleted successfully'),
            'sent' => array('type' => 'success', 'text' => 'Schedule executed successfully'),
            'no_logs' => array('type' => 'warning', 'text' => 'No logs to export'),
            'error' => array('type' => 'error', 'text' => isset($_GET['error']) ? sanitize_text_field($_GET['error']) : 'An error occurred')
        );
        
        if (isset($messages[$message])) {
            $msg = $messages[$message];
            echo '<div class="notice notice-' . esc_attr($msg['type']) . ' is-dismissible"><p>' . esc_html($msg['text']) . '</p></div>';
        }
    }
    
    private function render_list_page() {
        echo '<hr class="wp-header-end">';
        
        require_once AALS_PLUGIN_DIR . 'admin/views/list-schedules.php';
    }
    
    private function render_edit_page() {
        require_once AALS_PLUGIN_DIR . 'admin/views/edit-schedule.php';
    }
    
    private function render_logs_page() {
        $schedule_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $schedule = AALS_Database::get_schedule($schedule_id);
        
        if (!$schedule) {
            echo '<div class="notice notice-error"><p>Schedule not found.</p></div>';
            return;
        }
        
        $logs = AALS_Database::get_logs($schedule_id, 50);
        
        // Export URL
        $export_url = wp_nonce_url(
            admin_url('admin.php?page=amelia-appt-list-schedule&action=export_logs&id=' . $schedule_id),
            'export_logs_' . $schedule_id
        );
        
        echo '<h2>Logs for Schedule #' . $schedule_id . '</h2>';
        echo '<p>';
        echo '<a href="' . admin_url('admin.php?page=amelia-appt-list-schedule') . '">&larr; Back to List</a>';
        echo ' &nbsp;|&nbsp; ';
        echo '<a href="' . esc_url($export_url) . '" class="button button-secondary">Export Logs to CSV</a>';
        echo '</p>';
        
        if (empty($logs)) {
            echo '<p>No logs found for this schedule.</p>';
            return;
        }
        
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Time (UTC)</th><th>Trigger</th><th>Result</th><th>Details</th><th>Data Source</th><th>Rows</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($logs as $log) {
            $result_class = $log->result === 'success' ? 'color: green;' : ($log->result === 'failed' ? 'color: red;' : '');
            
            // Format trigger/action nicely
            $trigger_display = $this->format_trigger_action($log->action);
            
            echo '<tr>';
            echo '<td>' . esc_html($log->attempt_utc) . '</td>';
            echo '<td>' . $trigger_display . '</td>';
            echo '<td style="' . $result_class . '">' . esc_html($log->result) . '</td>';
            echo '<td>' . esc_html($log->error_message) . '</td>';
            echo '<td>' . esc_html($log->data_source) . '</td>';
            echo '<td>' . intval($log->rows_exported) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Format trigger action for display
     */
    private function format_trigger_action($action) {
        $icons = array(
            'auto_scheduled' => '<span style="color: #0073aa;">&#9200; Automated</span>',
            'auto_scheduled_start' => '<span style="color: #999;">&#9658; Auto Start</span>',
            'auto_scheduled_fetch' => '<span style="color: #999;">&#128269; Auto Fetch</span>',
            'auto_scheduled_csv' => '<span style="color: #999;">&#128196; Auto CSV</span>',
            'manual_complete' => '<span style="color: #46b450;">&#9889; Manual (Complete)</span>',
            'manual_complete_start' => '<span style="color: #999;">&#9658; Manual Start</span>',
            'manual_complete_fetch' => '<span style="color: #999;">&#128269; Manual Fetch</span>',
            'manual_complete_csv' => '<span style="color: #999;">&#128196; Manual CSV</span>',
            'manual_active' => '<span style="color: #f0b849;">&#9889; Manual (Keep Active)</span>',
            'manual_active_start' => '<span style="color: #999;">&#9658; Manual Start</span>',
            'manual_active_fetch' => '<span style="color: #999;">&#128269; Manual Fetch</span>',
            'manual_active_csv' => '<span style="color: #999;">&#128196; Manual CSV</span>',
        );
        
        return isset($icons[$action]) ? $icons[$action] : esc_html($action);
    }
}
