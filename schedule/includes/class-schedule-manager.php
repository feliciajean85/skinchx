<?php
/**
 * Schedule Manager V3 - Multi-Service Support
 * 
 * Core logic:
 * - Schedules can include multiple services
 * - Finds the FIRST upcoming appointment across ALL selected services
 * - Calculates trigger_time = first_appointment - relative_time
 * - At execution, fetches ALL future appointments from ALL selected services
 */

if (!defined('ABSPATH')) {
    exit;
}

class AALS_Schedule_Manager {
    
    private $provider;
    
    public function __construct() {
        $this->init_provider();
    }
    
    private function init_provider() {
        $api_provider = new AALS_API_Provider();
        $test_services = $api_provider->get_services();
        
        if ($test_services['success']) {
            $this->provider = $api_provider;
        } else {
            $this->provider = new AALS_DB_Fallback_Provider();
        }
    }
    
    public function get_provider() {
        return $this->provider;
    }
    
    /**
     * Get service IDs from schedule (handles both single and multi-service)
     */
    private function get_service_ids_from_schedule($schedule) {
        $service_ids = array();
        
        if (!empty($schedule->service_ids_json)) {
            $decoded = json_decode($schedule->service_ids_json, true);
            if (is_array($decoded)) {
                $service_ids = $decoded;
            }
        }
        
        // Fallback to single service_id
        if (empty($service_ids) && !empty($schedule->service_id)) {
            $service_ids = array($schedule->service_id);
        }
        
        return array_map('intval', $service_ids);
    }
    
    /**
     * Validate schedule data
     */
    public function validate_schedule_data($data) {
        $errors = array();
        
        // Get schedule type
        $schedule_type = isset($data['schedule_type']) ? $data['schedule_type'] : 'attendee_list';
        
        // Validate recipients
        if (empty($data['recipients'])) {
            $errors[] = 'Recipients are required';
        } else {
            $emails = array_map('trim', explode(',', $data['recipients']));
            foreach ($emails as $email) {
                if (!is_email($email)) {
                    $errors[] = 'Invalid email address: ' . esc_html($email);
                }
            }
        }
        
        // Validate services (multi-service support)
        $has_services = false;
        if (!empty($data['service_ids_json'])) {
            $service_ids = json_decode($data['service_ids_json'], true);
            $has_services = is_array($service_ids) && !empty($service_ids);
        }
        if (!$has_services && !empty($data['service_id'])) {
            $has_services = true;
        }
        
        if (!$has_services) {
            $errors[] = 'At least one service is required';
        }
        
        // Validate time_value
        if (empty($data['time_value']) || intval($data['time_value']) < 1) {
            $errors[] = 'Time value must be at least 1';
        }
        
        // Validate time_unit
        $valid_units = array('minutes', 'hours', 'days', 'weeks');
        if (empty($data['time_unit']) || !in_array($data['time_unit'], $valid_units)) {
            $errors[] = 'Invalid time unit';
        }
        
        // Validate time_direction
        $valid_directions = array('before', 'after');
        if (!empty($data['time_direction']) && !in_array($data['time_direction'], $valid_directions)) {
            $errors[] = 'Invalid time direction';
        }
        
        // CSV fields only required for attendee_list type
        if ($schedule_type === 'attendee_list' && empty($data['csv_fields_json'])) {
            $errors[] = 'At least one CSV field must be selected';
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $data
        );
    }
    
    /**
     * Create a new schedule
     */
    public function create_schedule($data) {
        $validation = $this->validate_schedule_data($data);
        
        if (!$validation['valid']) {
            return array(
                'success' => false,
                'errors' => $validation['errors']
            );
        }
        
        $data = $validation['data'];
        
        // Insert into database
        $schedule_id = AALS_Database::insert_schedule($data);
        
        if (!$schedule_id) {
            return array(
                'success' => false,
                'errors' => array('Failed to create schedule')
            );
        }
        
        // Calculate and set the next trigger time
        $this->recalculate_trigger_time($schedule_id);
        
        return array(
            'success' => true,
            'schedule_id' => $schedule_id
        );
    }
    
    /**
     * Update an existing schedule
     */
    public function update_schedule($schedule_id, $data) {
        $existing = AALS_Database::get_schedule($schedule_id);
        if (!$existing) {
            return array(
                'success' => false,
                'errors' => array('Schedule not found')
            );
        }
        
        $validation = $this->validate_schedule_data($data);
        
        if (!$validation['valid']) {
            return array(
                'success' => false,
                'errors' => $validation['errors']
            );
        }
        
        $data = $validation['data'];
        
        // Cancel any existing cron event
        $this->cancel_cron_event($schedule_id);
        
        // Reset status if it was Sent
        if ($existing->status === 'Sent' || $existing->status === 'Canceled') {
            $data['status'] = 'Active';
            $data['completed_utc'] = null;
        }
        
        // Update in database
        AALS_Database::update_schedule($schedule_id, $data);
        
        // Recalculate trigger time
        $this->recalculate_trigger_time($schedule_id);
        
        return array(
            'success' => true,
            'schedule_id' => $schedule_id
        );
    }
    
    /**
     * Delete (cancel) a schedule
     */
    public function delete_schedule($schedule_id) {
        $this->cancel_cron_event($schedule_id);
        
        AALS_Database::update_schedule($schedule_id, array(
            'status' => 'Canceled',
            'enabled' => 0,
            'next_trigger_utc' => null,
            'next_appointment_id' => null
        ));
        
        return array('success' => true);
    }
    
    /**
     * Permanently delete a schedule
     */
    public function permanently_delete($schedule_id) {
        $this->cancel_cron_event($schedule_id);
        AALS_Database::delete_schedule($schedule_id);
        return array('success' => true);
    }
    
    /**
     * Core V3 logic: Calculate when the schedule should trigger
     * 
     * For attendee_list: finds FIRST upcoming appointment, triggers BEFORE it
     * For group_report_reminder: uses the user-picked specific date/time from next_trigger_utc
     *                            (set via handle_save in class-admin-menu.php)
     */
    public function recalculate_trigger_time($schedule_id) {
        $schedule = AALS_Database::get_schedule($schedule_id);
        
        if (!$schedule || !$schedule->enabled) {
            return false;
        }
        
        // Get all service IDs for this schedule
        $service_ids = $this->get_service_ids_from_schedule($schedule);
        
        if (empty($service_ids)) {
            AALS_Database::update_schedule($schedule_id, array(
                'status' => 'Waiting',
                'status_message' => 'No services configured',
                'next_trigger_utc' => null,
                'next_appointment_id' => null,
                'last_check_utc' => current_time('mysql', true)
            ));
            $this->cancel_cron_event($schedule_id);
            return false;
        }
        
        // Determine schedule type and direction
        $schedule_type = isset($schedule->schedule_type) ? $schedule->schedule_type : 'attendee_list';
        $time_direction = isset($schedule->time_direction) ? $schedule->time_direction : 'before';
        
        // ===== Group Report Reminder: use the user's manually picked date/time =====
        // The user picks a specific datetime in the UI (saved as next_trigger_utc by handle_save).
        // We respect that and simply schedule the cron event for it — do NOT overwrite with
        // appointment-based recalculation.
        if ($schedule_type === 'group_report_reminder') {
            if (empty($schedule->next_trigger_utc)) {
                AALS_Database::update_schedule($schedule_id, array(
                    'status' => 'Waiting',
                    'status_message' => 'No reminder date/time selected',
                    'last_check_utc' => current_time('mysql', true)
                ));
                $this->cancel_cron_event($schedule_id);
                return false;
            }
            
            $trigger_time = strtotime($schedule->next_trigger_utc);
            $now = current_time('timestamp', true);
            
            // Format display datetime in site timezone for the status message
            try {
                $dt = new DateTime($schedule->next_trigger_utc, new DateTimeZone('UTC'));
                $dt->setTimezone(wp_timezone());
                $display_local = $dt->format('M j, Y g:i A');
            } catch (Exception $e) {
                $display_local = $schedule->next_trigger_utc . ' UTC';
            }
            
            if ($trigger_time <= $now) {
                // Already past - ready to run immediately
                AALS_Database::update_schedule($schedule_id, array(
                    'status' => 'Ready',
                    'status_message' => 'Scheduled for ' . $display_local . ' (trigger time reached)',
                    'last_check_utc' => current_time('mysql', true)
                ));
            } else {
                AALS_Database::update_schedule($schedule_id, array(
                    'status' => 'Scheduled',
                    'status_message' => 'Scheduled for ' . $display_local,
                    'last_check_utc' => current_time('mysql', true)
                ));
                $this->schedule_cron_event($schedule_id, $trigger_time);
            }
            
            return true;
        }
        
        // ===== Attendee list flow =====
        // For time_direction === 'after', find LAST appointment; otherwise find FIRST
        if ($time_direction === 'after') {
            $target_appointment = $this->get_last_appointment_of_day_multi($service_ids, $schedule->appointment_status);
            $direction_label = 'after last appointment';
        } else {
            $target_appointment = $this->get_first_upcoming_appointment_multi($service_ids, $schedule->appointment_status);
            $direction_label = 'before first appointment';
        }
        
        if (!$target_appointment) {
            // No appointments found
            $service_count = count($service_ids);
            AALS_Database::update_schedule($schedule_id, array(
                'status' => 'Waiting',
                'status_message' => 'No appointments found for ' . $service_count . ' service(s)',
                'next_trigger_utc' => null,
                'next_appointment_id' => null,
                'last_check_utc' => current_time('mysql', true)
            ));
            $this->cancel_cron_event($schedule_id);
            return false;
        }
        
        // Calculate trigger time based on direction
        $offset_seconds = $this->get_offset_seconds($schedule->time_value, $schedule->time_unit);
        
        if ($time_direction === 'after') {
            // Use end time for "after" triggers
            $appointment_time = strtotime($target_appointment['end']);
            $trigger_time = $appointment_time + $offset_seconds;
        } else {
            // Use start time for "before" triggers
            $appointment_time = strtotime($target_appointment['start']);
            $trigger_time = $appointment_time - $offset_seconds;
        }
        
        $now = current_time('timestamp', true);
        
        // If trigger time is in the past, the schedule should run now
        if ($trigger_time <= $now) {
            // Set as ready to run
            AALS_Database::update_schedule($schedule_id, array(
                'status' => 'Ready',
                'status_message' => 'Trigger time reached - ready to send',
                'next_trigger_utc' => date('Y-m-d H:i:s', $trigger_time),
                'next_appointment_id' => $target_appointment['id'],
                'last_check_utc' => current_time('mysql', true)
            ));
        } else {
            // Schedule for future
            $service_count = count($service_ids);
            AALS_Database::update_schedule($schedule_id, array(
                'status' => 'Scheduled',
                'status_message' => 'Scheduled: ' . $schedule->time_value . ' ' . $schedule->time_unit . ' ' . $direction_label . ' (' . $service_count . ' service(s))',
                'next_trigger_utc' => date('Y-m-d H:i:s', $trigger_time),
                'next_appointment_id' => $target_appointment['id'],
                'last_check_utc' => current_time('mysql', true)
            ));
            
            // Schedule the WP-Cron event
            $this->schedule_cron_event($schedule_id, $trigger_time);
        }
        
        return true;
    }
    
    /**
     * Get the LAST appointment of the current day across multiple services
     * Used for group report reminders
     */
    public function get_last_appointment_of_day_multi($service_ids, $status = 'approved') {
        $now_utc = current_time('mysql', true);
        $today_start = date('Y-m-d 00:00:00', strtotime($now_utc));
        $today_end = date('Y-m-d 23:59:59', strtotime($now_utc));
        
        $latest_appointment = null;
        
        foreach ($service_ids as $service_id) {
            $args = array(
                'service_id' => $service_id,
                'appointment_status' => $status,
                'range_start_utc' => $today_start,
                'range_end_utc' => $today_end
            );
            
            $result = $this->provider->get_appointments($args);
            
            if ($result['success'] && !empty($result['appointments'])) {
                foreach ($result['appointments'] as $appt) {
                    $end_time = isset($appt['bookingEnd']) ? $appt['bookingEnd'] : (isset($appt['end']) ? $appt['end'] : '');
                    $start_time = isset($appt['bookingStart']) ? $appt['bookingStart'] : (isset($appt['start']) ? $appt['start'] : '');
                    
                    if ($end_time || $start_time) {
                        $appt_end = $end_time ?: $start_time;
                        
                        if (!$latest_appointment || strtotime($appt_end) > strtotime($latest_appointment['end'])) {
                            $latest_appointment = array(
                                'id' => isset($appt['id']) ? $appt['id'] : 0,
                                'start' => $start_time,
                                'end' => $appt_end,
                                'service_id' => $service_id
                            );
                        }
                    }
                }
            }
        }
        
        return $latest_appointment;
    }
    
    /**
     * Get the first upcoming appointment across multiple services
     */
    public function get_first_upcoming_appointment_multi($service_ids, $status = 'approved') {
        $now_utc = current_time('mysql', true);
        $earliest_appointment = null;
        
        foreach ($service_ids as $service_id) {
            $args = array(
                'service_id' => $service_id,
                'appointment_status' => $status,
                'range_start_utc' => $now_utc,
                'limit' => 1,
                'order' => 'ASC'
            );
            
            $result = $this->provider->get_appointments($args);
            
            if ($result['success'] && !empty($result['appointments'])) {
                $appt = $result['appointments'][0];
                $start_time = isset($appt['bookingStart']) ? $appt['bookingStart'] : (isset($appt['start']) ? $appt['start'] : '');
                
                if ($start_time) {
                    if (!$earliest_appointment || strtotime($start_time) < strtotime($earliest_appointment['start'])) {
                        $earliest_appointment = array(
                            'id' => isset($appt['id']) ? $appt['id'] : 0,
                            'start' => $start_time,
                            'service_id' => $service_id
                        );
                    }
                }
            }
        }
        
        return $earliest_appointment;
    }
    
    /**
     * Get the first upcoming appointment for a single service (backwards compat)
     */
    public function get_first_upcoming_appointment($service_id, $status = 'approved') {
        return $this->get_first_upcoming_appointment_multi(array($service_id), $status);
    }
    
    /**
     * Get ALL future appointments for multiple services (for export)
     */
    public function get_all_future_appointments_multi($service_ids, $status = 'approved') {
        $now_utc = current_time('mysql', true);
        $all_appointments = array();
        $data_source = 'unknown';
        
        foreach ($service_ids as $service_id) {
            $args = array(
                'service_id' => $service_id,
                'appointment_status' => $status,
                'range_start_utc' => $now_utc
            );
            
            $result = $this->provider->get_appointments($args);
            $data_source = isset($result['data_source']) ? $result['data_source'] : 'unknown';
            
            if ($result['success'] && !empty($result['appointments'])) {
                $all_appointments = array_merge($all_appointments, $result['appointments']);
            }
        }
        
        // Sort by start time
        usort($all_appointments, function($a, $b) {
            $time_a = isset($a['bookingStart']) ? $a['bookingStart'] : (isset($a['start']) ? $a['start'] : '');
            $time_b = isset($b['bookingStart']) ? $b['bookingStart'] : (isset($b['start']) ? $b['start'] : '');
            return strtotime($time_a) - strtotime($time_b);
        });
        
        return array(
            'success' => true,
            'appointments' => $all_appointments,
            'data_source' => $data_source,
            'error' => null
        );
    }
    
    /**
     * Get ALL future appointments for a single service (backwards compat)
     */
    public function get_all_future_appointments($service_id, $status = 'approved') {
        return $this->get_all_future_appointments_multi(array($service_id), $status);
    }
    
    /**
     * Convert relative time to seconds
     */
    private function get_offset_seconds($value, $unit) {
        $value = intval($value);
        
        switch ($unit) {
            case 'minutes':
                return $value * 60;
            case 'hours':
                return $value * 3600;
            case 'days':
                return $value * 86400;
            case 'weeks':
                return $value * 604800;
            default:
                return $value * 3600; // default to hours
        }
    }
    
    /**
     * Schedule a WP-Cron event
     */
    private function schedule_cron_event($schedule_id, $timestamp) {
        $hook = 'aals_send_schedule_' . $schedule_id;
        
        // Clear any existing schedule
        wp_clear_scheduled_hook($hook);
        
        // Schedule the event
        wp_schedule_single_event($timestamp, $hook, array($schedule_id));
    }
    
    /**
     * Cancel a WP-Cron event
     */
    private function cancel_cron_event($schedule_id) {
        $hook = 'aals_send_schedule_' . $schedule_id;
        wp_clear_scheduled_hook($hook);
    }
    
    /**
     * Run schedule immediately
     * 
     * @param int $schedule_id
     * @param bool $mark_complete If true, marks as Sent. If false, keeps status as Scheduled.
     */
    public function run_schedule_now($schedule_id, $mark_complete = true) {
        $schedule = AALS_Database::get_schedule($schedule_id);
        
        if (!$schedule) {
            return array(
                'success' => false,
                'error' => 'Schedule not found'
            );
        }
        
        // Determine trigger source based on mark_complete
        $trigger_source = $mark_complete ? 'manual_send_now' : 'manual_keep_active';
        
        // Execute the schedule with proper trigger source
        $cron_handler = new AALS_Cron_Handler();
        $result = $cron_handler->execute_schedule($schedule_id, $mark_complete, $trigger_source);
        
        // If not marking complete, recalculate next trigger
        if (!$mark_complete && $result['success']) {
            $this->recalculate_trigger_time($schedule_id);
        }
        
        return $result;
    }
    
    /**
     * Generate preview data without sending
     */
    public function generate_preview($schedule_id) {
        $schedule = AALS_Database::get_schedule($schedule_id);
        
        if (!$schedule) {
            return array(
                'success' => false,
                'error' => 'Schedule not found'
            );
        }
        
        // Get all service IDs
        $service_ids = $this->get_service_ids_from_schedule($schedule);
        
        if (empty($service_ids)) {
            return array(
                'success' => false,
                'error' => 'No services configured for this schedule'
            );
        }
        
        $schedule_type = isset($schedule->schedule_type) ? $schedule->schedule_type : 'attendee_list';
        
        // === Group Report Reminder preview: no CSV, just the resolved email ===
        if ($schedule_type === 'group_report_reminder') {
            $cron_handler = new AALS_Cron_Handler();
            $built = $cron_handler->build_reminder_email_content($schedule);
            
            // Format scheduled trigger in site timezone for display
            $scheduled_local = '';
            if (!empty($schedule->next_trigger_utc)) {
                try {
                    $dt = new DateTime($schedule->next_trigger_utc, new DateTimeZone('UTC'));
                    $dt->setTimezone(wp_timezone());
                    $scheduled_local = $dt->format('F j, Y \a\t g:i A');
                } catch (Exception $e) {
                    $scheduled_local = $schedule->next_trigger_utc . ' UTC';
                }
            }
            
            return array(
                'success' => true,
                'schedule_type' => 'group_report_reminder',
                'service_count' => count($service_ids),
                'service_list' => $built['service_list'],
                'scheduled_local' => $scheduled_local,
                'last_appointment_datetime' => $built['context']['last_appointment_datetime'],
                'customer_name' => $built['context']['customer_name'],
                'hours_since_appointment' => $built['context']['hours_since_appointment'],
                'email_subject' => $built['subject'],
                'email_body' => $built['body'],
                'recipients' => $schedule->recipients
            );
        }
        
        // === Attendee list preview (original flow with CSV) ===
        // Get fresh appointment data from all services
        $result = $this->get_all_future_appointments_multi($service_ids, $schedule->appointment_status);
        
        if (!$result['success']) {
            return array(
                'success' => false,
                'error' => 'Failed to fetch appointments: ' . $result['error']
            );
        }
        
        $appointments = $result['appointments'];
        
        if (empty($appointments)) {
            return array(
                'success' => false,
                'error' => 'No future appointments found for ' . count($service_ids) . ' service(s)'
            );
        }
        
        // Generate CSV preview
        $csv_exporter = new AALS_CSV_Exporter();
        $csv_content = $csv_exporter->generate_csv_string(
            $appointments,
            $schedule->csv_fields_json
        );
        
        // Generate email preview
        $email_sender = new AALS_Email_Sender();
        $email_preview = $email_sender->generate_preview(
            $schedule,
            count($appointments),
            $result['data_source']
        );
        
        return array(
            'success' => true,
            'schedule_type' => 'attendee_list',
            'appointment_count' => count($appointments),
            'service_count' => count($service_ids),
            'data_source' => $result['data_source'],
            'csv_content' => $csv_content,
            'email_subject' => $email_preview['subject'],
            'email_body' => $email_preview['body'],
            'recipients' => $schedule->recipients
        );
    }
}
