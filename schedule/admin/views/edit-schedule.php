<?php
/**
 * Edit Schedule View V3.1 - Simplified Export, All Status Option, Email Defaults
 */

if (!defined('ABSPATH')) {
    exit;
}

$schedule_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$schedule = null;

if ($schedule_id) {
    $schedule = AALS_Database::get_schedule($schedule_id);
    if (!$schedule) {
        echo '<div class="notice notice-error"><p>Schedule not found.</p></div>';
        return;
    }
}

$manager = new AALS_Schedule_Manager();
$services_result = $manager->get_provider()->get_services();

if (!$services_result['success']) {
    echo '<div class="notice notice-warning"><p>Unable to load services from Amelia: ' . esc_html($services_result['error']) . '</p></div>';
    $services = array();
} else {
    $services = $services_result['services'];
}

// Get timezone for display
$timezone_string = get_option('timezone_string');
if (empty($timezone_string)) {
    $timezone_string = 'UTC';
}

// Default CSV fields (fixed set)
$default_csv_fields = array('start_datetime', 'customer_first_name', 'customer_last_name', 'customer_email', 'customer_phone');

// Prepare form data - handle multi-service
$selected_service_ids = array();
if ($schedule) {
    if (!empty($schedule->service_ids_json)) {
        $selected_service_ids = json_decode($schedule->service_ids_json, true);
    } elseif (!empty($schedule->service_id)) {
        $selected_service_ids = array($schedule->service_id);
    }
}

// Get current CSV fields - check if status field is included (for "all" status)
$current_csv_fields = $schedule ? json_decode($schedule->csv_fields_json, true) : $default_csv_fields;
$has_status_field = is_array($current_csv_fields) && in_array('appointment_status', $current_csv_fields);

// Get schedule type and time direction
$schedule_type = $schedule && isset($schedule->schedule_type) ? $schedule->schedule_type : 'attendee_list';
$time_direction = $schedule && isset($schedule->time_direction) ? $schedule->time_direction : 'before';

// Get scheduled datetime for group report reminders
$scheduled_datetime = $schedule && isset($schedule->next_trigger_utc) ? $schedule->next_trigger_utc : '';

$form_data = array(
    'schedule_type' => $schedule_type,
    'service_ids' => $selected_service_ids,
    'recipients' => $schedule ? $schedule->recipients : '',
    'time_value' => $schedule ? ($schedule->time_value ?: 24) : 24,
    'time_unit' => $schedule ? ($schedule->time_unit ?: 'hours') : 'hours',
    'time_direction' => $time_direction,
    'enabled' => $schedule ? $schedule->enabled : 1,
    'appointment_status' => $schedule ? $schedule->appointment_status : 'approved',
    'email_subject' => $schedule ? $schedule->email_subject : '',
    'email_body' => $schedule ? $schedule->email_body : '',
    'scheduled_datetime' => $scheduled_datetime
);

// Get last appointment info for selected services if editing
$last_appointment_info = null;
if (!empty($selected_service_ids)) {
    global $wpdb;
    $service_ids_str = implode(',', array_map('intval', $selected_service_ids));
    $last_appt = $wpdb->get_row("
        SELECT a.bookingStart, a.bookingEnd, s.name as service_name
        FROM {$wpdb->prefix}amelia_appointments a
        JOIN {$wpdb->prefix}amelia_services s ON a.serviceId = s.id
        WHERE a.serviceId IN ($service_ids_str)
        AND a.status = 'approved'
        ORDER BY a.bookingEnd DESC
        LIMIT 1
    ");
    if ($last_appt) {
        $last_appointment_info = $last_appt;
    }
}

$time_units = array(
    'minutes' => 'Minutes',
    'hours' => 'Hours',
    'days' => 'Days',
    'weeks' => 'Weeks'
);

// Get default email values from settings (or use hardcoded fallbacks)
$hardcoded_default_subject = 'Amelia Appointments: {service_name} ({count} appointments)';
$hardcoded_default_body = 'This is an automated email containing your scheduled appointment export.

Service: {service_name}
Total Appointments: {count}
Schedule ID: #{schedule_id}
Generated: {date} at {time}

Please find the attached CSV file with the complete appointment details.

---
This email was sent automatically by the Amelia Appointment List Schedule plugin.';

$default_email_subject = get_option('aals_default_email_subject', $hardcoded_default_subject);
$default_email_body = get_option('aals_default_email_body', $hardcoded_default_body);

// Group Report Reminder defaults (loaded from settings, with hardcoded fallbacks)
$hardcoded_reminder_subject = 'Group Report Reminder: {services}';
$hardcoded_reminder_body = "Hi {customer_name},\n\nThe appointments for {services} have completed.\n\nLast appointment: {last_appointment_date} at {last_appointment_time}\nTime since last appointment: {hours_since_appointment} hours\n\nPlease review and send the group report.\n\n---\nThis is an automated reminder from the SkinChX Addon plugin.\nSchedule ID: #{schedule_id}\nGenerated: {date} at {time}";

$default_reminder_subject = get_option('aals_default_reminder_email_subject', $hardcoded_reminder_subject);
$default_reminder_body = get_option('aals_default_reminder_email_body', $hardcoded_reminder_body);

// Settings page URL for editing defaults
$settings_url = admin_url('admin.php?page=wpamelia-setting');

?>

<style>
/* Mobile-responsive styles for schedule edit page */
@media screen and (max-width: 782px) {
    .card {
        padding: 12px !important;
        margin-left: -10px !important;
        margin-right: -10px !important;
        max-width: calc(100% + 20px) !important;
        box-sizing: border-box !important;
    }
    
    .card h3 {
        font-size: 16px;
    }
    
    /* Email defaults header - stack vertically */
    .email-defaults-header,
    .email-edit-header {
        display: flex;
        flex-direction: column;
        align-items: flex-start !important;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .email-defaults-buttons {
        display: flex;
        flex-direction: column;
        width: 100%;
        gap: 8px;
    }
    
    .email-defaults-buttons .button,
    .email-defaults-buttons a.button,
    .email-edit-header .button {
        width: 75% !important;
        box-sizing: border-box !important;
        text-align: center !important;
        margin: 0 auto !important;
        padding: 8px 12px !important;
        font-size: 13px !important;
        white-space: normal !important;
        line-height: 1.4 !important;
        height: auto !important;
        display: block !important;
    }
    
    /* Form tables on mobile */
    .form-table th,
    .form-table td {
        display: block;
        width: 100% !important;
        padding: 8px 0 !important;
    }
    
    .form-table th {
        padding-bottom: 4px !important;
    }
    
    /* Input fields full width */
    .form-table input[type="text"],
    .form-table input[type="number"],
    .form-table textarea,
    .form-table select {
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
    
    /* Submit buttons */
    .submit input[type="submit"],
    .submit .button,
    p.submit .button {
        width: 75% !important;
        box-sizing: border-box !important;
        margin: 0 auto 10px auto !important;
        display: block !important;
    }
    
    /* Send timing flex row */
    .form-table td > div[style*="display: flex"] {
        flex-wrap: wrap !important;
        gap: 8px !important;
    }
    
    .form-table td > div[style*="display: flex"] input,
    .form-table td > div[style*="display: flex"] select {
        flex: 1 1 auto !important;
        min-width: 80px !important;
    }
    
    /* Summary card */
    .card[style*="background: #f0f6fc"] {
        font-size: 14px;
    }
    
    /* Service checkboxes container */
    .form-table td > div[style*="max-height: 250px"] {
        max-height: 200px !important;
    }
}

/* Desktop styles for email header */
@media screen and (min-width: 783px) {
    .email-defaults-header,
    .email-edit-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .email-defaults-buttons {
        display: flex;
        gap: 8px;
    }
}
</style>

<h2><?php echo $schedule_id ? 'Edit Schedule #' . $schedule_id : 'Create New Schedule'; ?></h2>
<p><a href="<?php echo admin_url('admin.php?page=amelia-appt-list-schedule'); ?>">&larr; Back to List</a></p>

<?php if ($schedule && $schedule->status): ?>
<div class="card" style="max-width: 600px; padding: 15px; margin-bottom: 20px;">
    <h3 style="margin-top: 0;">Current Status</h3>
    <p><strong>Status:</strong> <?php echo esc_html($schedule->status); ?></p>
    <?php if ($schedule->status_message): ?>
        <p><strong>Details:</strong> <?php echo esc_html($schedule->status_message); ?></p>
    <?php endif; ?>
    <?php if ($schedule->next_trigger_utc && !in_array($schedule->status, array('Sent', 'Canceled', 'Failed'))): ?>
        <p><strong>Next Trigger (UTC):</strong> <?php echo esc_html($schedule->next_trigger_utc); ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<form method="post" action="<?php echo admin_url('admin.php?page=amelia-appt-list-schedule&action=save'); ?>" id="aals-schedule-form">
    <?php wp_nonce_field('aals_save_schedule', 'aals_nonce'); ?>
    <input type="hidden" name="schedule_id" value="<?php echo esc_attr($schedule_id); ?>" />
    
    <!-- Hidden field for CSV fields - will be set by JavaScript based on status selection -->
    <input type="hidden" name="csv_fields[]" value="start_datetime" />
    <input type="hidden" name="csv_fields[]" value="customer_first_name" />
    <input type="hidden" name="csv_fields[]" value="customer_last_name" />
    <input type="hidden" name="csv_fields[]" value="customer_email" />
    <input type="hidden" name="csv_fields[]" value="customer_phone" />
    <input type="hidden" name="csv_fields[]" value="appointment_status" id="csv-field-status" disabled />
    
    <!-- Schedule Type Selection -->
    <div class="card" style="max-width: 800px; padding: 20px; margin-bottom: 20px; background: linear-gradient(135deg, #f0f6fc 0%, #e8f4f8 100%); border-left: 4px solid #0073aa;">
        <h3 style="margin-top: 0;">Schedule Type</h3>
        <table class="form-table" style="margin-top: 0;">
            <tr>
                <td>
                    <label style="display: block; padding: 12px; margin-bottom: 10px; border: 2px solid #c3c4c7; border-radius: 8px; cursor: pointer; background: white;" id="type-attendee-label">
                        <input type="radio" name="schedule_type" value="attendee_list" <?php checked($form_data['schedule_type'], 'attendee_list'); ?> style="margin-right: 10px;" />
                        <strong>Attendee List Export</strong>
                        <p style="margin: 8px 0 0 24px; color: #646970; font-size: 13px;">
                            Send a CSV with attendee details <strong>before</strong> appointments start. Great for preparation and check-ins.
                        </p>
                    </label>
                    <label style="display: block; padding: 12px; border: 2px solid #c3c4c7; border-radius: 8px; cursor: pointer; background: white;" id="type-reminder-label">
                        <input type="radio" name="schedule_type" value="group_report_reminder" <?php checked($form_data['schedule_type'], 'group_report_reminder'); ?> style="margin-right: 10px;" />
                        <strong>Group Report Reminder</strong>
                        <p style="margin: 8px 0 0 24px; color: #646970; font-size: 13px;">
                            Send a reminder email <strong>after</strong> appointments end. Includes a link to the Combined Report page. Perfect for end-of-day reporting.
                        </p>
                    </label>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="card" style="max-width: 800px; padding: 20px; margin-bottom: 20px;">
        <h3 style="margin-top: 0;">1. Basic Settings</h3>
        
        <table class="form-table">
            <tr>
                <th scope="row"><label>Amelia Services *</label></th>
                <td>
                    <div style="max-height: 250px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background: #f9f9f9;">
                        <?php if (empty($services)): ?>
                            <p style="color: #999;">No services found. Make sure Amelia is installed and has active services.</p>
                        <?php else: ?>
                            <label style="display: block; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                                <input type="checkbox" id="select-all-services" />
                                <strong>Select All Services</strong>
                            </label>
                            <?php foreach ($services as $service): ?>
                                <label style="display: block; padding: 5px 0;">
                                    <input type="checkbox" name="service_ids[]" value="<?php echo esc_attr($service['id']); ?>" 
                                           class="service-checkbox"
                                           <?php checked(in_array($service['id'], $form_data['service_ids'])); ?> />
                                    <?php echo esc_html($service['name']); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <p class="description">Select one or more Amelia services. Appointments from all selected services will be included in the export.</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="recipients">Recipient Emails *</label></th>
                <td>
                    <textarea name="recipients" id="recipients" rows="2" class="large-text" required placeholder="email@example.com, another@example.com"><?php echo esc_textarea($form_data['recipients']); ?></textarea>
                    <p class="description">Comma-separated email addresses that will receive the notification.</p>
                </td>
            </tr>
            
            <!-- Timing for Attendee List (relative timing) -->
            <tr id="timing-row-attendee">
                <th scope="row"><label>Send Timing *</label></th>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <input type="number" name="time_value" id="time_value" value="<?php echo esc_attr($form_data['time_value']); ?>" min="1" max="999" style="width: 80px;" />
                        <select name="time_unit" id="time_unit">
                            <?php foreach ($time_units as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($form_data['time_unit'], $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="time_direction" id="time_direction">
                            <option value="before" <?php selected($form_data['time_direction'], 'before'); ?>>before</option>
                            <option value="after" <?php selected($form_data['time_direction'], 'after'); ?>>after</option>
                        </select>
                        <span id="timing-context">the <strong>first upcoming appointment</strong></span>
                    </div>
                    <p class="description" id="timing-description">
                        Example: "24 hours before" means the email will be sent 24 hours before the earliest future appointment across all selected services.<br>
                        The trigger time is automatically recalculated when appointments change.
                    </p>
                </td>
            </tr>
            
            <!-- Timing for Group Report Reminder (specific date/time) -->
            <tr id="timing-row-reminder" style="display: none;">
                <th scope="row"><label>Schedule Reminder</label></th>
                <td>
                    <div id="last-appointment-info" style="background: #f0f6fc; border: 1px solid #c3c4c7; border-radius: 6px; padding: 15px; margin-bottom: 15px;">
                        <strong>Last Booked Appointment:</strong>
                        <div id="last-appointment-display" style="margin-top: 8px; color: #1d2327;">
                        <?php if ($last_appointment_info): ?>
                            <span style="font-size: 14px;">
                                <?php 
                                $end_time = new DateTime($last_appointment_info->bookingEnd, new DateTimeZone('UTC'));
                                $end_time->setTimezone(wp_timezone());
                                echo esc_html($last_appointment_info->service_name) . ' - ';
                                echo $end_time->format('F j, Y \a\t g:i A');
                                ?>
                            </span>
                        <?php else: ?>
                            <em style="color: #646970;">Select services above to see the last appointment</em>
                        <?php endif; ?>
                        </div>
                        <p class="description" style="margin-top: 10px; margin-bottom: 0;">
                            This shows the most recent approved appointment end time for the selected services. Updates automatically when you change service selection.
                        </p>
                        <button type="button" id="use-last-appt-btn" class="button button-small" style="margin-top: 8px; display: none;">Use this date/time</button>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <div>
                            <label for="scheduled_date"><strong>Reminder Date:</strong></label><br>
                            <input type="date" name="scheduled_date" id="scheduled_date" 
                                   value="<?php echo $form_data['scheduled_datetime'] ? date('Y-m-d', strtotime($form_data['scheduled_datetime'])) : ''; ?>" 
                                   style="width: 180px; margin-top: 5px;" />
                        </div>
                        <div>
                            <label for="scheduled_time"><strong>Reminder Time:</strong></label><br>
                            <input type="time" name="scheduled_time" id="scheduled_time" 
                                   value="<?php echo $form_data['scheduled_datetime'] ? date('H:i', strtotime($form_data['scheduled_datetime'])) : '17:00'; ?>" 
                                   style="width: 140px; margin-top: 5px;" />
                        </div>
                    </div>
                    <p class="description" style="margin-top: 10px;">
                        Set the specific date and time when you want to receive the reminder email.<br>
                        <strong>Tip:</strong> Schedule it for after your last appointment of the day ends.
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="enabled">Enable Schedule</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="enabled" id="enabled" value="1" <?php checked($form_data['enabled'], 1); ?> />
                        Schedule is active and will run automatically
                    </label>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="card" style="max-width: 800px; padding: 20px; margin-bottom: 20px;">
        <h3 style="margin-top: 0;">2. Export Configuration</h3>
        
        <table class="form-table">
            <tr>
                <th scope="row"><label for="appointment_status">Appointment Status</label></th>
                <td>
                    <select name="appointment_status" id="appointment_status" class="regular-text">
                        <option value="all" <?php selected($form_data['appointment_status'], 'all'); ?>>All Statuses</option>
                        <option value="approved" <?php selected($form_data['appointment_status'], 'approved'); ?>>Approved</option>
                        <option value="pending" <?php selected($form_data['appointment_status'], 'pending'); ?>>Pending</option>
                        <option value="canceled" <?php selected($form_data['appointment_status'], 'canceled'); ?>>Canceled</option>
                        <option value="rejected" <?php selected($form_data['appointment_status'], 'rejected'); ?>>Rejected</option>
                    </select>
                    <p class="description">Filter appointments by status. Select "All Statuses" to include appointments regardless of status.</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label>CSV Export Fields</label></th>
                <td>
                    <div style="background: #f0f6fc; border: 1px solid #c3c4c7; border-radius: 4px; padding: 12px 15px;">
                        <p style="margin: 0; color: #1d2327;">
                            <strong>The following fields will be exported:</strong>
                        </p>
                        <ul style="margin: 10px 0 0 0; padding-left: 20px; color: #50575e;">
                            <li>Start Date/Time</li>
                            <li>Customer First Name</li>
                            <li>Customer Last Name</li>
                            <li>Customer Email</li>
                            <li>Customer Phone</li>
                            <li id="status-field-note" style="display: none; color: #0073aa;"><strong>Appointment Status</strong> (included when "All Statuses" is selected)</li>
                        </ul>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="card" style="max-width: 800px; padding: 20px; margin-bottom: 20px;">
        <h3 style="margin-top: 0;">3. Email Customization</h3>
        <p class="description">Customize the email that will be sent with the CSV attachment. Variables: <code>{service_name}</code>, <code>{count}</code>, <code>{date}</code>, <code>{time}</code>, <code>{schedule_id}</code></p>
        
        <!-- Default Settings Display -->
        <div id="email-defaults-display" style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin-bottom: 20px;">
            <div class="email-defaults-header">
                <h4 style="margin: 0 0 10px 0; color: #1d2327;" id="email-defaults-title">Default Email Settings</h4>
                <div class="email-defaults-buttons">
                    <a href="<?php echo esc_url($settings_url); ?>#scheduled_lists_email_section" class="button button-link" target="_blank" id="edit-defaults-link">Edit Defaults</a>
                    <button type="button" id="edit-email-btn" class="button button-secondary">Customize for This Schedule</button>
                </div>
            </div>
            <table style="width: 100%;">
                <tr>
                    <td style="width: 80px; vertical-align: top; padding: 5px 10px 5px 0; color: #646970;"><strong>Subject:</strong></td>
                    <td style="padding: 5px 0; color: #1d2327; word-break: break-word;" id="default-subject-display"><?php echo esc_html($default_email_subject); ?></td>
                </tr>
                <tr>
                    <td style="vertical-align: top; padding: 5px 10px 5px 0; color: #646970;"><strong>Body:</strong></td>
                    <td style="padding: 5px 0; color: #1d2327; white-space: pre-line; word-break: break-word;" id="default-body-display"><?php echo esc_html($default_email_body); ?></td>
                </tr>
            </table>
            <!-- Hidden datasets for JS to swap on schedule-type change -->
            <span id="default-subject-attendee" data-val="<?php echo esc_attr($default_email_subject); ?>" style="display:none;"></span>
            <span id="default-body-attendee" data-val="<?php echo esc_attr($default_email_body); ?>" style="display:none;"></span>
            <span id="default-subject-reminder" data-val="<?php echo esc_attr($default_reminder_subject); ?>" style="display:none;"></span>
            <span id="default-body-reminder" data-val="<?php echo esc_attr($default_reminder_body); ?>" style="display:none;"></span>
        </div>
        
        <!-- Editable Fields (hidden by default unless already customized) -->
        <div id="email-edit-section" style="display: <?php echo (!empty($form_data['email_subject']) || !empty($form_data['email_body'])) ? 'block' : 'none'; ?>;">
            <div class="email-edit-header">
                <h4 style="margin: 0 0 10px 0; color: #1d2327;">Custom Email Settings for This Schedule</h4>
                <button type="button" id="reset-to-defaults-btn" class="button button-link-delete">Use Defaults Instead</button>
            </div>
            <p class="description" style="margin-bottom: 15px;">
                Edit the fields below to customize the email for this specific schedule. 
                <a href="<?php echo esc_url($settings_url); ?>#scheduled_lists_email_section" target="_blank">Edit global defaults in Settings</a>
            </p>
            
            <table class="form-table" style="margin-top: 0;">
                <tr>
                    <th scope="row"><label for="email_subject">Email Subject</label></th>
                    <td>
                        <input type="text" name="email_subject" id="email_subject" class="large-text" 
                               value="<?php echo esc_attr(!empty($form_data['email_subject']) ? $form_data['email_subject'] : ''); ?>" />
                        <p class="description">Leave blank to use the default subject.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="email_body">Email Body</label></th>
                    <td>
                        <textarea name="email_body" id="email_body" rows="10" class="large-text"><?php echo esc_textarea(!empty($form_data['email_body']) ? $form_data['email_body'] : ''); ?></textarea>
                        <p class="description">Leave blank to use the default body. You can use HTML for formatting.</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <div class="card" style="max-width: 800px; padding: 20px; margin-bottom: 20px; background: #f0f6fc; border-left: 4px solid #0073aa;">
        <h3 style="margin-top: 0;">Summary</h3>
        <p id="summary-text">
            This schedule will send a CSV of all future <strong id="sum-status">approved</strong> appointments 
            for <strong id="sum-service">the selected service(s)</strong> 
            to <strong id="sum-recipients">the specified recipients</strong>, 
            <strong id="sum-timing">24 hours</strong> before the first upcoming appointment.
        </p>
    </div>
    
    <p class="submit">
        <?php submit_button($schedule_id ? 'Update Schedule' : 'Create Schedule', 'primary', 'submit', false); ?>
        <a href="<?php echo admin_url('admin.php?page=amelia-appt-list-schedule'); ?>" class="button" style="margin-left: 10px;">Cancel</a>
    </p>
</form>

<script>
jQuery(document).ready(function($) {
    // Default values from settings (attendee list)
    var defaultSubject = <?php echo json_encode($default_email_subject); ?>;
    var defaultBody = <?php echo json_encode($default_email_body); ?>;
    
    // Default reminder email values (loaded from settings, option keys: aals_default_reminder_email_*)
    var defaultReminderSubject = <?php echo json_encode($default_reminder_subject); ?>;
    var defaultReminderBody = <?php echo json_encode($default_reminder_body); ?>;
    
    // Settings anchor for "Edit Defaults" link
    var settingsUrlBase = <?php echo json_encode($settings_url); ?>;
    
    // Schedule type change handler
    $('input[name="schedule_type"]').on('change', function() {
        var scheduleType = $(this).val();
        updateUIForScheduleType(scheduleType);
    });
    
    function updateUIForScheduleType(scheduleType) {
        // Update visual selection
        $('#type-attendee-label, #type-reminder-label').css('border-color', '#c3c4c7');
        if (scheduleType === 'attendee_list') {
            $('#type-attendee-label').css('border-color', '#0073aa');
            // Show relative timing, hide specific datetime
            $('#timing-row-attendee').show();
            $('#timing-row-reminder').hide();
            // Show export configuration
            $('.card:has(#appointment_status)').show();
            // Swap default email display to attendee
            $('#email-defaults-title').text('Default Email Settings');
            $('#default-subject-display').text($('#default-subject-attendee').data('val') || defaultSubject);
            $('#default-body-display').text($('#default-body-attendee').data('val') || defaultBody);
            $('#edit-defaults-link').attr('href', settingsUrlBase + '#scheduled_lists_email_section');
        } else {
            $('#type-reminder-label').css('border-color', '#0073aa');
            // Hide relative timing, show specific datetime
            $('#timing-row-attendee').hide();
            $('#timing-row-reminder').show();
            // Hide export configuration for reminders
            $('.card:has(#appointment_status)').hide();
            // Swap default email display to reminder
            $('#email-defaults-title').text('Default Reminder Email Settings');
            $('#default-subject-display').text($('#default-subject-reminder').data('val') || defaultReminderSubject);
            $('#default-body-display').text($('#default-body-reminder').data('val') || defaultReminderBody);
            $('#edit-defaults-link').attr('href', settingsUrlBase + '#group_report_reminder_email_section');
        }
        updateSummary();
    }
    
    // Initialize based on current selection
    var initialType = $('input[name="schedule_type"]:checked').val() || 'attendee_list';
    updateUIForScheduleType(initialType);
    
    // Select all services toggle
    $('#select-all-services').on('change', function() {
        $('.service-checkbox').prop('checked', $(this).is(':checked'));
        updateSummary();
        refreshLastAppointment();
    });
    
    // Update select-all state when individual checkboxes change
    $('.service-checkbox').on('change', function() {
        var total = $('.service-checkbox').length;
        var checked = $('.service-checkbox:checked').length;
        $('#select-all-services').prop('checked', total === checked);
        updateSummary();
        refreshLastAppointment();
    });
    
    // Last-appointment lookup (for Group Report Reminder)
    var lastAppointmentData = null;
    var refreshTimer = null;
    function refreshLastAppointment() {
        // Debounce rapid changes
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(doRefreshLastAppointment, 150);
    }
    function doRefreshLastAppointment() {
        var selectedIds = $('.service-checkbox:checked').map(function() { return $(this).val(); }).get();
        var $display = $('#last-appointment-display');
        var $useBtn = $('#use-last-appt-btn');
        
        if (selectedIds.length === 0) {
            $display.html('<em style="color: #646970;">Select services above to see the last appointment</em>');
            $useBtn.hide();
            lastAppointmentData = null;
            return;
        }
        
        $display.html('<em style="color: #646970;">Loading…</em>');
        
        $.post(aals_ajax.ajax_url, {
            action: 'aals_get_last_appointment',
            service_ids: selectedIds,
            nonce: aals_ajax.nonce
        }, function(response) {
            if (response.success && response.data.found) {
                lastAppointmentData = response.data;
                $display.html(
                    '<span style="font-size: 14px;">' +
                    $('<div/>').text(response.data.service_name).html() + ' - ' +
                    $('<div/>').text(response.data.formatted).html() +
                    '</span>'
                );
                $useBtn.show();
            } else {
                lastAppointmentData = null;
                var msg = (response.success && response.data.message) ? response.data.message : 'No approved appointments found for the selected services';
                $display.html('<em style="color: #646970;">' + $('<div/>').text(msg).html() + '</em>');
                $useBtn.hide();
            }
        }).fail(function() {
            $display.html('<em style="color: #a00;">Failed to load last appointment</em>');
            $useBtn.hide();
        });
    }
    
    // "Use this date/time" button — copies last appointment's date/time into the pickers
    $('#use-last-appt-btn').on('click', function() {
        if (lastAppointmentData && lastAppointmentData.date) {
            $('#scheduled_date').val(lastAppointmentData.date);
            if (lastAppointmentData.time) {
                $('#scheduled_time').val(lastAppointmentData.time);
            }
            updateSummary();
        }
    });
    
    // Initial load: fetch if services already selected
    refreshLastAppointment();
    
    // Handle appointment status change - show/hide status field note and enable/disable hidden field
    $('#appointment_status').on('change', function() {
        var isAll = $(this).val() === 'all';
        if (isAll) {
            $('#status-field-note').show();
            $('#csv-field-status').prop('disabled', false);
        } else {
            $('#status-field-note').hide();
            $('#csv-field-status').prop('disabled', true);
        }
        updateSummary();
    });
    
    // Initialize status field visibility
    $('#appointment_status').trigger('change');
    
    // Email customization toggle - pre-fill with defaults
    $('#edit-email-btn').on('click', function() {
        $('#email-defaults-display').hide();
        $('#email-edit-section').show();
        
        var scheduleType = $('input[name="schedule_type"]:checked').val();
        var useSubject = scheduleType === 'group_report_reminder' ? defaultReminderSubject : defaultSubject;
        var useBody = scheduleType === 'group_report_reminder' ? defaultReminderBody : defaultBody;
        
        // Pre-fill fields with defaults if they're empty
        if (!$('#email_subject').val()) {
            $('#email_subject').val(useSubject);
        }
        if (!$('#email_body').val()) {
            $('#email_body').val(useBody);
        }
    });
    
    // Reset to defaults - clear fields and show defaults display
    $('#reset-to-defaults-btn').on('click', function() {
        $('#email_subject').val('');
        $('#email_body').val('');
        $('#email-edit-section').hide();
        $('#email-defaults-display').show();
    });
    
    function updateSummary() {
        var selectedServices = $('.service-checkbox:checked');
        var serviceCount = selectedServices.length;
        var serviceText = '';
        
        if (serviceCount === 0) {
            serviceText = 'no services selected';
        } else if (serviceCount === 1) {
            serviceText = selectedServices.first().parent().text().trim();
        } else {
            serviceText = serviceCount + ' services';
        }
        
        var status = $('#appointment_status').val();
        var statusText = status === 'all' ? 'all' : status;
        var recipients = $('#recipients').val() || 'no recipients';
        var timeValue = $('#time_value').val();
        var timeUnit = $('#time_unit').val();
        var timeDirection = $('#time_direction').val();
        var scheduleType = $('input[name="schedule_type"]:checked').val();
        
        $('#sum-service').text(serviceText);
        $('#sum-status').text(statusText);
        $('#sum-recipients').text(recipients.split(',').length + ' recipient(s)');
        $('#sum-timing').text(timeValue + ' ' + timeUnit + ' ' + timeDirection);
        
        // Update summary text based on schedule type
        if (scheduleType === 'group_report_reminder') {
            // For reminder, show the specific picked date/time instead of relative timing
            var schedDate = $('#scheduled_date').val();
            var schedTime = $('#scheduled_time').val() || '17:00';
            var whenText;
            if (schedDate) {
                try {
                    var d = new Date(schedDate + 'T' + schedTime + ':00');
                    whenText = d.toLocaleString(undefined, {
                        weekday: 'short', year: 'numeric', month: 'short', day: 'numeric',
                        hour: 'numeric', minute: '2-digit'
                    });
                } catch (e) {
                    whenText = schedDate + ' at ' + schedTime;
                }
            } else {
                whenText = '(no date/time selected)';
            }
            
            $('#summary-text').html(
                'This schedule will send a <strong>reminder email</strong> ' +
                'for <strong>' + serviceText + '</strong> ' +
                'to <strong>' + recipients.split(',').length + ' recipient(s)</strong> ' +
                'on <strong>' + whenText + '</strong>.'
            );
        } else {
            $('#summary-text').html(
                'This schedule will send a CSV of all future <strong id="sum-status">' + statusText + '</strong> appointments ' +
                'for <strong id="sum-service">' + serviceText + '</strong> ' +
                'to <strong id="sum-recipients">' + recipients.split(',').length + ' recipient(s)</strong>, ' +
                '<strong id="sum-timing">' + timeValue + ' ' + timeUnit + ' ' + timeDirection + '</strong> the ' +
                (timeDirection === 'after' ? 'last appointment' : 'first upcoming appointment') + '.'
            );
        }
    }
    
    $('#aals-schedule-form').on('change keyup', 'input, select, textarea', updateSummary);
    updateSummary();
    
    // Form validation
    $('#aals-schedule-form').on('submit', function(e) {
        if ($('.service-checkbox:checked').length === 0) {
            alert('Please select at least one service.');
            e.preventDefault();
            return false;
        }
    });
    
    // Show edit section if there are already custom values
    <?php if (!empty($form_data['email_subject']) || !empty($form_data['email_body'])): ?>
    $('#email-defaults-display').hide();
    $('#email-edit-section').show();
    <?php endif; ?>
});
</script>
