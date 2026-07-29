<?php
/**
 * Schedule List Table V3 - WP_List_Table with bulk actions
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class AALS_Schedule_List_Table extends WP_List_Table {
    
    public function __construct() {
        parent::__construct(array(
            'singular' => 'schedule',
            'plural' => 'schedules',
            'ajax' => false
        ));
    }
    
    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'service' => 'Service(s)',
            'timing' => 'Timing',
            'recipients' => 'Recipients',
            'status' => 'Status',
            'next_trigger' => 'Next Trigger',
            'actions' => 'Actions'
        );
    }
    
    public function get_sortable_columns() {
        return array(
            'service' => array('service_name_cache', false),
            'status' => array('status', false),
            'next_trigger' => array('next_trigger_utc', true)
        );
    }
    
    /**
     * Define bulk actions
     */
    public function get_bulk_actions() {
        return array(
            'bulk_enable' => 'Enable',
            'bulk_disable' => 'Disable',
            'bulk_cancel' => 'Cancel',
            'bulk_delete' => 'Delete Permanently'
        );
    }
    
    /**
     * Process bulk actions
     */
    public function process_bulk_action() {
        $action = $this->current_action();
        
        if (!$action) {
            return;
        }
        
        // Verify nonce
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-schedules')) {
            return;
        }
        
        $schedule_ids = isset($_REQUEST['schedule']) ? array_map('intval', $_REQUEST['schedule']) : array();
        
        if (empty($schedule_ids)) {
            return;
        }
        
        $manager = new AALS_Schedule_Manager();
        $count = 0;
        
        foreach ($schedule_ids as $id) {
            switch ($action) {
                case 'bulk_enable':
                    AALS_Database::update_schedule($id, array('enabled' => 1, 'status' => 'Active'));
                    $manager->recalculate_trigger_time($id);
                    $count++;
                    break;
                    
                case 'bulk_disable':
                    AALS_Database::update_schedule($id, array('enabled' => 0));
                    $count++;
                    break;
                    
                case 'bulk_cancel':
                    $manager->delete_schedule($id);
                    $count++;
                    break;
                    
                case 'bulk_delete':
                    $manager->permanently_delete($id);
                    $count++;
                    break;
            }
        }
        
        // Redirect with message
        $message = '';
        switch ($action) {
            case 'bulk_enable':
                $message = $count . ' schedule(s) enabled';
                break;
            case 'bulk_disable':
                $message = $count . ' schedule(s) disabled';
                break;
            case 'bulk_cancel':
                $message = $count . ' schedule(s) canceled';
                break;
            case 'bulk_delete':
                $message = $count . ' schedule(s) deleted';
                break;
        }
        
        wp_redirect(admin_url('admin.php?page=amelia-appt-list-schedule&bulk_message=' . urlencode($message)));
        exit;
    }
    
    public function prepare_items() {
        // Process bulk actions first
        $this->process_bulk_action();
        
        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns()
        );
        
        $this->items = AALS_Database::get_all_schedules();
    }
    
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="schedule[]" value="%d" />', $item->id);
    }
    
    public function column_service($item) {
        // Handle multi-service (service_ids is JSON array or single service_id)
        $service_ids = array();
        $service_names = array();
        
        if (!empty($item->service_ids_json)) {
            $service_ids = json_decode($item->service_ids_json, true);
            $service_names = !empty($item->service_names_cache) ? json_decode($item->service_names_cache, true) : array();
        } elseif (!empty($item->service_id)) {
            $service_ids = array($item->service_id);
            $service_names = array($item->service_name_cache ?: 'Service #' . $item->service_id);
        }
        
        $edit_url = admin_url('admin.php?page=amelia-appt-list-schedule&action=edit&id=' . $item->id);
        
        if (count($service_names) === 1) {
            $display_name = $service_names[0] ?: 'Service #' . $service_ids[0];
            return sprintf(
                '<strong><a href="%s">%s</a></strong><br><small>Schedule #%d</small>',
                esc_url($edit_url),
                esc_html($display_name),
                $item->id
            );
        } elseif (count($service_names) > 1) {
            return sprintf(
                '<strong><a href="%s">%d Services</a></strong><br><small>%s, ...</small>',
                esc_url($edit_url),
                count($service_names),
                esc_html($service_names[0])
            );
        } else {
            return sprintf(
                '<strong><a href="%s">No Service</a></strong><br><small>Schedule #%d</small>',
                esc_url($edit_url),
                $item->id
            );
        }
    }
    
    public function column_timing($item) {
        $schedule_type = isset($item->schedule_type) ? $item->schedule_type : 'attendee_list';
        
        // Group Report Reminder uses a specific date/time (not a relative offset)
        if ($schedule_type === 'group_report_reminder') {
            if (!empty($item->next_trigger_utc)) {
                try {
                    $dt = new DateTime($item->next_trigger_utc, new DateTimeZone('UTC'));
                    $dt->setTimezone(wp_timezone());
                    return '<strong>Reminder</strong><br><small>' . esc_html($dt->format('M j, Y g:i A')) . '</small>';
                } catch (Exception $e) {
                    // fall through
                }
            }
            return '<strong>Reminder</strong><br><small><em>No date selected</em></small>';
        }
        
        // Attendee list (relative timing)
        $value = $item->time_value ?: 24;
        $unit = $item->time_unit ?: 'hours';
        $direction = isset($item->time_direction) ? $item->time_direction : 'before';
        $anchor = $direction === 'after' ? 'last appointment' : 'first appointment';
        
        return sprintf(
            '<strong>%d %s</strong> %s %s',
            $value,
            esc_html($unit),
            esc_html($direction),
            esc_html($anchor)
        );
    }
    
    public function column_recipients($item) {
        $emails = array_map('trim', explode(',', $item->recipients));
        $count = count($emails);
        
        if ($count === 1) {
            return esc_html($emails[0]);
        }
        
        return sprintf('%s <small>(+%d more)</small>', esc_html($emails[0]), $count - 1);
    }
    
    public function column_status($item) {
        $status_colors = array(
            'Active' => '#0073aa',
            'Scheduled' => '#00a32a',
            'Ready' => '#d54e21',
            'Sending' => '#f0b849',
            'Sent' => '#46b450',
            'Failed' => '#dc3232',
            'Waiting' => '#999',
            'Canceled' => '#666'
        );
        
        $color = isset($status_colors[$item->status]) ? $status_colors[$item->status] : '#666';
        $enabled = $item->enabled ? '' : ' (Disabled)';
        
        $html = sprintf(
            '<span style="color: %s; font-weight: bold;">%s</span>%s',
            $color,
            esc_html($item->status),
            $enabled
        );
        
        if ($item->status_message) {
            $html .= '<br><small title="' . esc_attr($item->status_message) . '">' . esc_html(substr($item->status_message, 0, 50)) . '</small>';
        }
        
        return $html;
    }
    
    public function column_next_trigger($item) {
        // If schedule is completed/sent/canceled, show "None"
        if (in_array($item->status, array('Sent', 'Canceled', 'Failed'))) {
            return '<em style="color: #999;">None</em>';
        }
        
        if (!$item->next_trigger_utc) {
            return '<em>Not scheduled</em>';
        }
        
        $trigger_time = strtotime($item->next_trigger_utc);
        $now = current_time('timestamp', true);
        
        $timezone_string = get_option('timezone_string');
        if (empty($timezone_string)) {
            $timezone_string = 'UTC';
        }
        
        $dt = new DateTime($item->next_trigger_utc, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone($timezone_string));
        
        $formatted = $dt->format('Y-m-d H:i');
        
        if ($trigger_time <= $now) {
            return sprintf('<strong style="color: #d54e21;">%s</strong><br><small>(ready to send)</small>', $formatted);
        }
        
        $diff = $trigger_time - $now;
        $hours = round($diff / 3600);
        
        if ($hours < 24) {
            $time_left = $hours . ' hours';
        } else {
            $time_left = round($hours / 24) . ' days';
        }
        
        return sprintf('%s<br><small>(in %s)</small>', $formatted, $time_left);
    }
    
    public function column_actions($item) {
        $edit_url = admin_url('admin.php?page=amelia-appt-list-schedule&action=edit&id=' . $item->id);
        $delete_url = wp_nonce_url(
            admin_url('admin.php?page=amelia-appt-list-schedule&action=delete&id=' . $item->id),
            'delete_schedule_' . $item->id
        );
        $logs_url = admin_url('admin.php?page=amelia-appt-list-schedule&action=logs&id=' . $item->id);
        
        $actions = array();
        
        // Preview button
        $actions[] = sprintf(
            '<button type="button" class="button button-small aals-preview-btn" data-id="%d">Preview</button>',
            $item->id
        );
        
        // Run Now button (only for active schedules)
        if ($item->enabled && !in_array($item->status, array('Sent', 'Canceled', 'Sending'))) {
            $actions[] = sprintf(
                '<button type="button" class="button button-small button-primary aals-run-now-btn" data-id="%d">Run Now</button>',
                $item->id
            );
        }
        
        // Edit link
        $actions[] = sprintf('<a href="%s" class="button button-small">Edit</a>', esc_url($edit_url));
        
        // Logs link
        $actions[] = sprintf('<a href="%s" class="button button-small">Logs</a>', esc_url($logs_url));
        
        // Delete link
        if ($item->status !== 'Canceled') {
            $actions[] = sprintf(
                '<a href="%s" class="button button-small" onclick="return confirm(\'Cancel this schedule?\');">Cancel</a>',
                esc_url($delete_url)
            );
        }
        
        return '<div class="aals-actions">' . implode(' ', $actions) . '</div>';
    }
    
    public function column_default($item, $column_name) {
        return isset($item->$column_name) ? esc_html($item->$column_name) : '';
    }
    
    public function no_items() {
        echo 'No schedules found. <a href="' . admin_url('admin.php?page=amelia-appt-list-schedule&action=new') . '">Create one now</a>.';
    }
}
