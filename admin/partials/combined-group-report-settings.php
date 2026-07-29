<?php
/**
 * Combined Group Report admin page.
 *
 * This page hosts:
 * - Combined Group Report (multi-service, optional customer filter)
 * - Saved Combined Reports listing
 *
 * It reuses the same markup and IDs that the Settings page used previously,
 * so existing JavaScript in `wpamelia-addon-admin.js` continues to work.
 *
 * @package Wpamelia_Addon
 */

// Fetch data for dropdowns. Guard with function_exists to avoid fatals
// if Amelia core helpers are unavailable for any reason.
$services_for_report  = function_exists('get_all_services') ? get_all_services() : array();
$customers_for_report = function_exists('get_all_customers') ? get_all_customers() : array();
?>

<div class="wrap">
    <div class="kawaii-wrap">
        <h1 class="kawaii-page-title">Combined Report</h1>

        <!-- Generate Report Card -->
        <div class="kawaii-card">
            <h2><span class="icon-dot"></span>Generate Combined Report</h2>
            <p>Select multiple services and optionally filter by customers to build a consolidated group report.</p>

            <label for="combined_services_select">Services</label>
            <select id="combined_services_select" name="combined_services[]" multiple size="6" style="min-width: 100%; max-width: 400px;">
                <?php if (!empty($services_for_report)) : ?>
                    <?php foreach ($services_for_report as $serv) : ?>
                        <option value="<?php echo esc_attr($serv->id); ?>"><?php echo esc_html($serv->name); ?></option>
                    <?php endforeach; ?>
                <?php else : ?>
                    <option value=""><?php esc_html_e('No services found', 'wpamelia-addon'); ?></option>
                <?php endif; ?>
            </select>
            <p class="description">Hold Ctrl (Windows) or Cmd (Mac) to multi-select.</p>

            <label for="combined_customers_select" style="margin-top: 20px;">Customers (Optional)</label>
            <p class="description" style="margin-bottom: 8px;">Search and select multiple customers to filter the report. Leave empty to include all customers.</p>
            <select id="combined_customers_select" name="combined_customers[]" class="select2drop" multiple style="min-width: 100%; max-width: 400px;">
                <?php if (!empty($customers_for_report)) : ?>
                    <?php foreach ($customers_for_report as $customer) : ?>
                        <option value="<?php echo esc_attr($customer->id); ?>">
                            <?php echo esc_html(trim($customer->firstName . ' ' . $customer->lastName) . ' (' . $customer->email . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                <?php else : ?>
                    <option value=""><?php esc_html_e('No customers found', 'wpamelia-addon'); ?></option>
                <?php endif; ?>
            </select>

            <div style="margin-top: 24px;">
                <button type="button" class="button button-primary" id="generate_combined_report">
                    Generate Combined Report
                </button>
            </div>

            <div id="combined-report-message" class="notice" style="display:none;margin-top:16px;"></div>
            <div id="combined-report-summary" class="notice notice-info" style="display:none;margin-top:16px;"></div>
            
            <div id="combined-preview-inline" style="display:none;margin-top:20px; padding:20px; background:var(--kawaii-cream, #FFF9F5); border-radius:12px;">
                <h3 style="font-family: 'Quicksand', sans-serif; color: #5A4A5A; margin-bottom:16px;">Combined Report Preview</h3>
                <?php include_once 'wpamelia-addon-admin-display.php'; ?>
            </div>
        </div>

        <!-- Saved Reports Card -->
        <div class="kawaii-card">
            <h2><span class="icon-dot" style="background: #FFB5C5;"></span>Saved Combined Reports</h2>
            
            <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
                <input type="text" id="saved-reports-search" placeholder="Search by report name or services..." style="min-width: 280px;">
                <button type="button" class="button" id="search-saved-reports">Search</button>
                <button type="button" class="button" id="clear-saved-reports-search">Clear</button>
            </div>
            
            <div id="saved-reports-loading" style="display:none; padding: 16px; color: #8A7A8A;">Loading...</div>
            <div id="saved-reports-list"></div>
            <div id="saved-reports-pagination" style="margin-top: 20px;"></div>
        </div>
    </div>
</div>