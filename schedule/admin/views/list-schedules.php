<?php
/**
 * List Schedules View V3 - with bulk actions
 * Kawaii Style Design
 */

if (!defined('ABSPATH')) {
    exit;
}

$list_table = new AALS_Schedule_List_Table();
$list_table->prepare_items();

// Stats
global $wpdb;
$table = $wpdb->prefix . 'aals_schedules';
$total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
$active = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE enabled = 1 AND status NOT IN ('Sent', 'Canceled')");
$sent = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'Sent'");

// Bulk action message
$bulk_message = isset($_GET['bulk_message']) ? sanitize_text_field($_GET['bulk_message']) : '';
?>

<style>
/* Mobile-responsive styles for schedule list page */
@media screen and (max-width: 782px) {
    .kawaii-stats {
        flex-direction: column;
        gap: 10px;
    }
    
    .kawaii-stat-card {
        width: 100%;
        box-sizing: border-box;
    }
    
    /* Create button full width */
    div[style*="margin-bottom: 20px"] .button {
        width: 100%;
        box-sizing: border-box;
        text-align: center;
    }
    
    /* Modal mobile fixes */
    .kawaii-modal {
        width: 95% !important;
        max-width: none !important;
        left: 2.5% !important;
        transform: translate(0, -50%) !important;
    }
    
    .kawaii-modal-header {
        padding: 15px !important;
        flex-direction: column !important;
        gap: 10px !important;
    }
    
    .kawaii-modal-header h2 {
        font-size: 18px !important;
    }
    
    .kawaii-modal-body {
        padding: 15px !important;
    }
    
    .kawaii-modal-footer {
        padding: 15px !important;
        flex-direction: column !important;
        gap: 8px !important;
    }
    
    .kawaii-modal-footer .button {
        width: 100% !important;
        box-sizing: border-box !important;
        margin: 0 !important;
    }
    
    /* Radio options mobile */
    .kawaii-modal-body label[style*="padding: 16px"] {
        padding: 12px !important;
    }
    
    /* Table actions mobile */
    .wp-list-table .aals-actions {
        flex-direction: column;
        gap: 5px;
    }
    
    .wp-list-table .aals-actions .button {
        width: 100%;
        text-align: center;
        font-size: 12px;
        padding: 5px 8px;
    }
}
</style>

<?php if ($bulk_message): ?>
<div class="notice notice-success is-dismissible"><p><?php echo esc_html($bulk_message); ?></p></div>
<?php endif; ?>

<!-- Kawaii Stats Cards -->
<div class="kawaii-stats">
    <div class="kawaii-stat-card stat-total">
        <div class="stat-value"><?php echo intval($total); ?></div>
        <div class="stat-label">Total Schedules</div>
    </div>
    <div class="kawaii-stat-card stat-active">
        <div class="stat-value"><?php echo intval($active); ?></div>
        <div class="stat-label">Active</div>
    </div>
    <div class="kawaii-stat-card stat-completed">
        <div class="stat-value"><?php echo intval($sent); ?></div>
        <div class="stat-label">Completed</div>
    </div>
</div>

<div style="margin-bottom: 20px;">
    <a href="<?php echo admin_url('admin.php?page=amelia-appt-list-schedule&action=new'); ?>" class="button button-primary">
        + Create New Schedule
    </a>
</div>

<div class="kawaii-card" style="padding: 0; overflow: hidden;">
    <form method="post">
        <input type="hidden" name="page" value="amelia-appt-list-schedule" />
        <?php $list_table->display(); ?>
    </form>
</div>

<!-- Preview Modal -->
<div id="aals-preview-modal" class="kawaii-modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(90, 74, 90, 0.6); z-index: 100000;">
    <div class="kawaii-modal" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; width: 90%; max-width: 900px; max-height: 90vh; overflow: auto; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.2);">
        <div class="kawaii-modal-header" style="padding: 20px 24px; border-bottom: 2px solid #FFE4EC; background: #FFF9F5; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; font-family: 'Quicksand', sans-serif; font-weight: 700; color: #5A4A5A;">Preview</h2>
            <button type="button" id="aals-close-preview" class="button">Close</button>
        </div>
        <div id="aals-preview-content" class="kawaii-modal-body" style="padding: 24px;">
            <p>Loading preview...</p>
        </div>
    </div>
</div>

<!-- Run Now Modal -->
<div id="aals-run-modal" class="kawaii-modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(90, 74, 90, 0.6); z-index: 100000;">
    <div class="kawaii-modal" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; width: 90%; max-width: 500px; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.2);">
        <div class="kawaii-modal-header" style="padding: 20px 24px; border-bottom: 2px solid #FFE4EC; background: #FFF9F5;">
            <h2 style="margin: 0; font-family: 'Quicksand', sans-serif; font-weight: 700; color: #5A4A5A;">Run Schedule Now</h2>
        </div>
        <div class="kawaii-modal-body" style="padding: 24px;">
            <p style="font-family: 'Quicksand', sans-serif; color: #5A4A5A; margin-bottom: 20px;">Choose how to run this schedule:</p>
            <div>
                <label style="display: block; padding: 16px; background: #D4F1E8; border: 2px solid #98D9C2; border-radius: 12px; margin-bottom: 12px; cursor: pointer; font-family: 'Quicksand', sans-serif;">
                    <input type="radio" name="run_mode" value="complete" checked style="margin-right: 10px; accent-color: #98D9C2;" />
                    <strong style="color: #5A4A5A;">Send and Mark Complete</strong>
                    <p style="margin: 8px 0 0 25px; color: #8A7A8A; font-size: 13px;">Send the email now and mark this schedule as completed. The scheduled trigger will be canceled.</p>
                </label>
                <label style="display: block; padding: 16px; background: #FFF9F5; border: 2px solid #FFE4EC; border-radius: 12px; cursor: pointer; font-family: 'Quicksand', sans-serif;">
                    <input type="radio" name="run_mode" value="keep_active" style="margin-right: 10px; accent-color: #98D9C2;" />
                    <strong style="color: #5A4A5A;">Send but Keep Active</strong>
                    <p style="margin: 8px 0 0 25px; color: #8A7A8A; font-size: 13px;">Send the email now but keep the schedule active. It will continue to trigger at its next scheduled time.</p>
                </label>
            </div>
        </div>
        <div class="kawaii-modal-footer" style="padding: 16px 24px; border-top: 2px solid #FFE4EC; display: flex; justify-content: flex-end; gap: 12px;">
            <button type="button" id="aals-cancel-run" class="button">Cancel</button>
            <button type="button" id="aals-confirm-run" class="button button-primary">Send Email</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var currentScheduleId = 0;
    
    // Preview functionality
    $('.aals-preview-btn').on('click', function() {
        var scheduleId = $(this).data('id');
        $('#aals-preview-content').html('<p>Loading preview...</p>');
        $('#aals-preview-modal').show();
        
        $.post(aals_ajax.ajax_url, {
            action: 'aals_preview',
            schedule_id: scheduleId,
            nonce: aals_ajax.nonce
        }, function(response) {
            if (response.success) {
                var data = response.data;
                var html = '';
                
                if (data.schedule_type === 'group_report_reminder') {
                    // === Group Report Reminder preview (no CSV section) ===
                    html += '<div style="margin-bottom: 20px; font-family: Quicksand, sans-serif;">';
                    html += '<strong style="color: #5A4A5A;">Schedule Type:</strong> <span style="color: #5FBDA0;">Group Report Reminder</span><br>';
                    html += '<strong style="color: #5A4A5A;">Services:</strong> <span style="color: #8A7A8A;">' + escapeHtml(data.service_list || '') + '</span><br>';
                    if (data.scheduled_local) {
                        html += '<strong style="color: #5A4A5A;">Scheduled For:</strong> <span style="color: #8A7A8A;">' + escapeHtml(data.scheduled_local) + '</span><br>';
                    }
                    if (data.last_appointment_datetime) {
                        html += '<strong style="color: #5A4A5A;">Last Appointment:</strong> <span style="color: #8A7A8A;">' + escapeHtml(data.last_appointment_datetime) + '</span>';
                        if (data.hours_since_appointment !== '') {
                            html += ' <span style="color: #8A7A8A;">(' + escapeHtml(String(data.hours_since_appointment)) + ' hours ago)</span>';
                        }
                        html += '<br>';
                    }
                    if (data.customer_name && data.customer_name !== 'there') {
                        html += '<strong style="color: #5A4A5A;">Customer:</strong> <span style="color: #8A7A8A;">' + escapeHtml(data.customer_name) + '</span><br>';
                    }
                    html += '<strong style="color: #5A4A5A;">Recipients:</strong> <span style="color: #8A7A8A;">' + escapeHtml(data.recipients || '') + '</span>';
                    html += '</div>';
                    
                    html += '<h3 style="font-family: Quicksand, sans-serif; color: #5A4A5A;">Email Preview</h3>';
                    html += '<div style="background: #D4F1E8; padding: 15px; border: 2px solid #98D9C2; border-radius: 12px; margin-bottom: 20px; font-family: Quicksand, sans-serif;">';
                    html += '<strong style="color: #5FBDA0;">Subject:</strong> ' + escapeHtml(data.email_subject || '');
                    html += '</div>';
                    html += '<div style="background: #fff; padding: 15px; border: 2px solid #FFE4EC; border-radius: 12px;">';
                    html += data.email_body;
                    html += '</div>';
                } else {
                    // === Attendee List preview (original with CSV) ===
                    html += '<div style="margin-bottom: 20px; font-family: Quicksand, sans-serif;">';
                    html += '<strong style="color: #5A4A5A;">Appointments Found:</strong> <span style="color: #5FBDA0;">' + data.appointment_count + '</span><br>';
                    html += '<strong style="color: #5A4A5A;">Data Source:</strong> <span style="color: #8A7A8A;">' + escapeHtml(data.data_source || '') + '</span><br>';
                    html += '<strong style="color: #5A4A5A;">Recipients:</strong> <span style="color: #8A7A8A;">' + escapeHtml(data.recipients || '') + '</span>';
                    html += '</div>';
                    
                    html += '<h3 style="font-family: Quicksand, sans-serif; color: #5A4A5A;">Email Preview</h3>';
                    html += '<div style="background: #D4F1E8; padding: 15px; border: 2px solid #98D9C2; border-radius: 12px; margin-bottom: 20px; font-family: Quicksand, sans-serif;">';
                    html += '<strong style="color: #5FBDA0;">Subject:</strong> ' + escapeHtml(data.email_subject || '');
                    html += '</div>';
                    html += '<div style="background: #fff; padding: 15px; border: 2px solid #FFE4EC; border-radius: 12px; margin-bottom: 20px;">';
                    html += data.email_body;
                    html += '</div>';
                    
                    html += '<h3 style="font-family: Quicksand, sans-serif; color: #5A4A5A;">CSV Preview (first rows)</h3>';
                    html += '<div style="background: #FFF9F5; padding: 15px; border: 2px solid #FFE4EC; border-radius: 12px; overflow-x: auto;">';
                    html += '<pre style="margin: 0; white-space: pre-wrap; font-size: 12px; color: #5A4A5A;">' + escapeHtml(data.csv_content.substring(0, 2000));
                    if (data.csv_content.length > 2000) {
                        html += '\n... (truncated)';
                    }
                    html += '</pre></div>';
                }
                
                $('#aals-preview-content').html(html);
            } else {
                $('#aals-preview-content').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
            }
        });
    });
    
    $('#aals-close-preview').on('click', function() {
        $('#aals-preview-modal').hide();
    });
    
    // Run Now functionality
    $('.aals-run-now-btn').on('click', function() {
        currentScheduleId = $(this).data('id');
        $('#aals-run-modal').show();
    });
    
    $('#aals-cancel-run').on('click', function() {
        $('#aals-run-modal').hide();
    });
    
    $('#aals-confirm-run').on('click', function() {
        var markComplete = $('input[name="run_mode"]:checked').val() === 'complete';
        var $btn = $(this);
        
        $btn.prop('disabled', true).text('Sending...');
        
        $.post(aals_ajax.ajax_url, {
            action: 'aals_run_now',
            schedule_id: currentScheduleId,
            mark_complete: markComplete,
            nonce: aals_ajax.nonce
        }, function(response) {
            $btn.prop('disabled', false).text('Send Email');
            $('#aals-run-modal').hide();
            
            if (response.success) {
                alert('Email sent successfully! ' + response.data.rows_exported + ' appointments exported.');
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
    
    // Close modals on backdrop click
    $('#aals-preview-modal, #aals-run-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
