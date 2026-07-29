<?php
/**
 * CSV Exporter V2
 */

if (!defined('ABSPATH')) {
    exit;
}

class AALS_CSV_Exporter {
    
    private $available_fields = array(
        'appointment_id' => 'Appointment ID',
        'appointment_status' => 'Appointment Status',
        'start_datetime' => 'Start Date/Time',
        'end_datetime' => 'End Date/Time',
        'service_name' => 'Service Name',
        'provider_name' => 'Provider Name',
        'customer_first_name' => 'Customer First Name',
        'customer_last_name' => 'Customer Last Name',
        'customer_email' => 'Customer Email',
        'customer_phone' => 'Customer Phone',
        'notes' => 'Notes',
        'location' => 'Location'
    );
    
    public function get_available_fields() {
        return $this->available_fields;
    }
    
    /**
     * Convert UTC datetime to WordPress site timezone
     */
    private function convert_to_site_timezone($datetime_string) {
        if (empty($datetime_string)) {
            return '';
        }
        
        try {
            // Get WordPress timezone setting
            $wp_timezone = wp_timezone();
            
            // Create DateTime object from the UTC datetime
            $datetime = new DateTime($datetime_string, new DateTimeZone('UTC'));
            
            // Convert to site timezone
            $datetime->setTimezone($wp_timezone);
            
            // Return formatted datetime
            return $datetime->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            // If conversion fails, return original value
            return $datetime_string;
        }
    }
    
    /**
     * Export appointments to CSV file
     */
    public function export($appointments, $selected_fields, $service_id) {
        if (empty($appointments)) {
            return array(
                'success' => false,
                'error' => 'No appointments to export'
            );
        }
        
        $csv_content = $this->generate_csv_string($appointments, $selected_fields);
        
        // Generate filename
        $filename = 'appointments_' . $service_id . '_' . date('Ymd-His') . '.csv';
        $filepath = $this->save_csv_file($csv_content, $filename);
        
        if (!$filepath) {
            return array(
                'success' => false,
                'error' => 'Failed to save CSV file'
            );
        }
        
        return array(
            'success' => true,
            'filepath' => $filepath,
            'filename' => $filename,
            'row_count' => count($appointments)
        );
    }
    
    /**
     * Generate CSV content as string (for preview)
     */
    public function generate_csv_string($appointments, $selected_fields) {
        if (is_string($selected_fields)) {
            $selected_fields = json_decode($selected_fields, true);
        }
        
        if (empty($selected_fields)) {
            $selected_fields = array_keys($this->available_fields);
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Header row
        $header = array();
        foreach ($selected_fields as $field) {
            if (isset($this->available_fields[$field])) {
                $header[] = $this->available_fields[$field];
            }
        }
        fputcsv($output, $header);
        
        // Data rows
        foreach ($appointments as $appointment) {
            $row = array();
            foreach ($selected_fields as $field) {
                $row[] = $this->get_field_value($appointment, $field);
            }
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);
        
        return $csv_content;
    }
    
    private function get_field_value($appointment, $field) {
        $value = '';
        
        switch ($field) {
            case 'appointment_id':
                $value = isset($appointment['id']) ? $appointment['id'] : '';
                break;
                
            case 'appointment_status':
                $value = isset($appointment['status']) ? $appointment['status'] : '';
                break;
                
            case 'start_datetime':
                if (isset($appointment['bookingStart'])) {
                    $value = $this->convert_to_site_timezone($appointment['bookingStart']);
                } elseif (isset($appointment['start'])) {
                    $value = $this->convert_to_site_timezone($appointment['start']);
                }
                break;
                
            case 'end_datetime':
                if (isset($appointment['bookingEnd'])) {
                    $value = $this->convert_to_site_timezone($appointment['bookingEnd']);
                } elseif (isset($appointment['end'])) {
                    $value = $this->convert_to_site_timezone($appointment['end']);
                }
                break;
                
            case 'service_name':
                $value = isset($appointment['service_name']) ? $appointment['service_name'] : '';
                break;
                
            case 'provider_name':
                if (isset($appointment['provider_first_name']) || isset($appointment['provider_last_name'])) {
                    $value = trim(($appointment['provider_first_name'] ?? '') . ' ' . ($appointment['provider_last_name'] ?? ''));
                }
                break;
                
            case 'customer_first_name':
                $value = isset($appointment['customer_first_name']) ? $appointment['customer_first_name'] : '';
                break;
                
            case 'customer_last_name':
                $value = isset($appointment['customer_last_name']) ? $appointment['customer_last_name'] : '';
                break;
                
            case 'customer_email':
                $value = isset($appointment['customer_email']) ? $appointment['customer_email'] : '';
                break;
                
            case 'customer_phone':
                $value = isset($appointment['customer_phone']) ? $appointment['customer_phone'] : '';
                break;
                
            case 'notes':
                $value = isset($appointment['internalNotes']) ? $appointment['internalNotes'] : '';
                break;
                
            case 'location':
                $value = isset($appointment['location']) ? $appointment['location'] : '';
                break;
        }
        
        return (string) $value;
    }
    
    private function save_csv_file($content, $filename) {
        $upload_dir = wp_upload_dir();
        $csv_dir = $upload_dir['basedir'] . '/amelia-schedules';
        
        if (!file_exists($csv_dir)) {
            wp_mkdir_p($csv_dir);
        }
        
        $filepath = $csv_dir . '/' . $filename;
        
        if (file_put_contents($filepath, $content) === false) {
            return false;
        }
        
        return $filepath;
    }
}
