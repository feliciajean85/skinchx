<?php
/**
 * Email Sender V2 - with preview support
 */

if (!defined('ABSPATH')) {
    exit;
}

class AALS_Email_Sender {
    
    /**
     * Send the email with CSV attachment
     */
    public function send($schedule, $csv_filepath, $csv_filename, $row_count, $data_source) {
        if (!file_exists($csv_filepath)) {
            return array(
                'success' => false,
                'error' => 'CSV file not found: ' . $csv_filepath
            );
        }
        
        $recipients = array_map('trim', explode(',', $schedule->recipients));
        
        $preview = $this->generate_preview($schedule, $row_count, $data_source);
        $subject = $preview['subject'];
        $message = $preview['body'];
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8'
        );
        
        $sent = wp_mail($recipients, $subject, $message, $headers, array($csv_filepath));
        
        if (!$sent) {
            return array(
                'success' => false,
                'error' => 'wp_mail() returned false. Check SMTP configuration or FluentSMTP logs.'
            );
        }
        
        return array(
            'success' => true,
            'recipients' => $recipients,
            'subject' => $subject,
            'attachment' => $csv_filename
        );
    }
    
    /**
     * Get default subject from settings
     */
    private function get_default_subject() {
        $default = 'Amelia Appointments: {service_name} ({count} appointments)';
        return get_option('aals_default_email_subject', $default);
    }
    
    /**
     * Get default body text from settings
     */
    private function get_default_body_text() {
        $default = 'This is an automated email containing your scheduled appointment export.

Service: {service_name}
Total Appointments: {count}
Schedule ID: #{schedule_id}
Generated: {date} at {time}

Please find the attached CSV file with the complete appointment details.

---
This email was sent automatically by the Amelia Appointment List Schedule plugin.';
        return get_option('aals_default_email_body', $default);
    }
    
    /**
     * Generate preview of email (without sending)
     */
    public function generate_preview($schedule, $row_count, $data_source) {
        $service_name = $schedule->service_name_cache ?: 'Service #' . $schedule->service_id;
        
        // Subject
        if (!empty($schedule->email_subject)) {
            $subject = $this->replace_variables($schedule->email_subject, $service_name, $row_count, $data_source, $schedule->id);
        } else {
            $subject = $this->replace_variables($this->get_default_subject(), $service_name, $row_count, $data_source, $schedule->id);
        }
        
        // Body
        if (!empty($schedule->email_body)) {
            $body = $this->replace_variables($schedule->email_body, $service_name, $row_count, $data_source, $schedule->id);
            $body = nl2br($body);
            
            if (strpos($body, '<html>') === false) {
                $body = "<html><body>" . $body . "</body></html>";
            }
        } else {
            $body = $this->get_default_body($service_name, $row_count, $data_source, $schedule->id);
        }
        
        return array(
            'subject' => $subject,
            'body' => $body
        );
    }
    
    /**
     * Replace template variables
     */
    private function replace_variables($text, $service_name, $row_count, $data_source, $schedule_id) {
        // Use WordPress site timezone for date/time
        $wp_timezone = wp_timezone();
        $now = new DateTime('now', $wp_timezone);
        
        $replacements = array(
            '{service_name}' => $service_name,
            '{count}' => $row_count,
            '{data_source}' => $data_source,
            '{schedule_id}' => $schedule_id,
            '{date}' => $now->format('Y-m-d'),
            '{time}' => $now->format('H:i')
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
    
    /**
     * Default email body - uses settings or generates HTML version
     */
    private function get_default_body($service_name, $row_count, $data_source, $schedule_id) {
        // Check if there's a custom default body in settings
        $custom_body = get_option('aals_default_email_body', '');
        
        if (!empty($custom_body)) {
            // Use the custom body from settings
            $body = $this->replace_variables($custom_body, $service_name, $row_count, $data_source, $schedule_id);
            $body = nl2br($body);
            return "<html><body style='font-family: Arial, sans-serif; line-height: 1.6;'><div style='max-width: 600px; margin: 0 auto; padding: 20px;'>" . $body . "</div></body></html>";
        }
        
        // Use WordPress site timezone for generated timestamp
        $wp_timezone = wp_timezone();
        $now = new DateTime('now', $wp_timezone);
        $timezone_name = $wp_timezone->getName();
        
        $html = "<html><body style='font-family: Arial, sans-serif; line-height: 1.6;'>";
        $html .= "<div style='max-width: 600px; margin: 0 auto; padding: 20px;'>";
        $html .= "<h2 style='color: #333; border-bottom: 2px solid #0073aa; padding-bottom: 10px;'>Scheduled Appointment Export</h2>";
        $html .= "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
        $html .= "<tr><td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Service:</strong></td><td style='padding: 8px; border-bottom: 1px solid #eee;'>" . esc_html($service_name) . "</td></tr>";
        $html .= "<tr><td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Total Appointments:</strong></td><td style='padding: 8px; border-bottom: 1px solid #eee;'>" . intval($row_count) . "</td></tr>";
        $html .= "<tr><td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Data Source:</strong></td><td style='padding: 8px; border-bottom: 1px solid #eee;'>" . esc_html($data_source) . "</td></tr>";
        $html .= "<tr><td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Schedule ID:</strong></td><td style='padding: 8px; border-bottom: 1px solid #eee;'>#" . intval($schedule_id) . "</td></tr>";
        $html .= "<tr><td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Generated:</strong></td><td style='padding: 8px; border-bottom: 1px solid #eee;'>" . $now->format('Y-m-d H:i:s') . " (" . esc_html($timezone_name) . ")</td></tr>";
        $html .= "</table>";
        $html .= "<p style='color: #666;'>Please find the attached CSV file with the complete appointment details.</p>";
        $html .= "<hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>";
        $html .= "<p style='color: #999; font-size: 12px;'>This email was sent automatically by the Amelia Appointment List Schedule plugin.</p>";
        $html .= "</div></body></html>";
        
        return $html;
    }
}
