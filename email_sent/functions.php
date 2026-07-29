<?php
/**
 * Email functions for Amelia Addon
 * Uses wp_mail() which integrates with FluentSMTP for sending and logging
 */

/**
 * Send email with PDF attachment using wp_mail (FluentSMTP compatible)
 *
 * @param string|array $customerEmail Recipient email(s)
 * @param string $bodyContent HTML body content
 * @param string $pdf URL or path to PDF file
 * @param string $emailType Type of email: 'body_chart', 'individual', 'referal', 'group', 'materials'
 * @return string Success or failure message
 */
function sendEmailWithPdf($customerEmail, $bodyContent, $pdf, $emailType = 'body_chart') {
    // Get the appropriate subject based on email type
    $subject = getEmailSubject($emailType);
    
    // Parse and validate recipient emails
    if (is_array($customerEmail)) {
        $to = array_values(array_filter(array_map(function($e) {
            $e = trim($e);
            return filter_var($e, FILTER_VALIDATE_EMAIL) ? $e : null;
        }, $customerEmail)));
    } else {
        $emails = preg_split('/[,;]/', (string)$customerEmail);
        $to = array_values(array_filter(array_map(function($e) {
            $e = trim($e);
            return filter_var($e, FILTER_VALIDATE_EMAIL) ? $e : null;
        }, $emails)));
    }

    if (empty($to)) {
        return 'Failed: No valid recipient emails.';
    }

    // Set up headers for HTML email
    $headers = array('Content-Type: text/html; charset=UTF-8');

    // Handle PDF attachment
    $attachments = array();
    $temp_file_path = '';
    
    if ($pdf) {
        $pdfContent = file_get_contents($pdf);
        if ($pdfContent !== false) {
            $temp_file_path = ABSPATH . 'wp-content/uploads/report_' . time() . '.pdf';
            file_put_contents($temp_file_path, $pdfContent);
            $attachments[] = $temp_file_path;
        }
    }

    // Format body content
    $message = wpautop($bodyContent);
    $message = wp_kses_post($message);

    // Send email using wp_mail (FluentSMTP will handle the actual sending and logging)
    $mail_sent = wp_mail($to, $subject, $message, $headers, $attachments);

    // Clean up temporary file
    if ($temp_file_path && file_exists($temp_file_path)) {
        unlink($temp_file_path);
    }

    if ($mail_sent) {
        return 'Success';
    } else {
        return 'Failed to send the email.';
    }
}

/**
 * Get email subject based on email type
 *
 * @param string $emailType Type of email
 * @return string Subject line
 */
function getEmailSubject($emailType) {
    $subjects = array(
        'body_chart' => get_option('email_subject_body_chart', 'Your Body Chart Report from SkinChx'),
        'individual' => get_option('email_subject_individual', 'Your Individual Profile Report from SkinChx'),
        'referal' => get_option('email_subject_referal', 'Your Referal Chart Report from SkinChx'),
        'group' => get_option('email_subject_group', 'Your Group Chart Report from SkinChx'),
        'materials' => get_option('email_subject_materials', 'Your Program Materials from SkinChx'),
    );

    return isset($subjects[$emailType]) ? $subjects[$emailType] : $subjects['body_chart'];
}

/**
 * Legacy function for backward compatibility
 * @deprecated Use sendEmailWithPdf() instead
 */
function sendEmailWithPdf_old($customerEmail, $bodyContent, $pdfUrl) {
    return sendEmailWithPdf($customerEmail, $bodyContent, $pdfUrl, 'body_chart');
}
?>
