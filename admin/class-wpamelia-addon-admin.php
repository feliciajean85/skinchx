<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://encoderit.com
 * @since      1.0.0
 *
 * @package    Wpamelia_Addon
 * @subpackage Wpamelia_Addon/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpamelia_Addon
 * @subpackage Wpamelia_Addon/admin
 * @author     Encoder It <nadim@encoderit.net>
 */
class Wpamelia_Addon_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        add_action('admin_init', array($this, 'custom_smtp_settings_init'));
        add_action('admin_init', array($this, 'api_settings_init'));
        add_action('admin_head', array($this, 'admin_head_fucntion'));
        
        // Suppress all WP/third-party admin notice badges on our plugin pages
        add_action('in_admin_header', array($this, 'suppress_admin_notices_on_our_pages'), 1);
        add_action('admin_head', array($this, 'suppress_admin_notices_css'), 1);
        
        require_once plugin_dir_path(__FILE__) . 'ajax/booking-ajax.php';
        // For non-logged-in users if needed
    }
    
    /**
     * Return the list of admin page slugs owned by this plugin.
     * Used to scope notice-suppression and in-page behaviours.
     */
    public static function get_plugin_page_slugs()
    {
        return array(
            'amelia-report',
            'amelia-group-reports',
            'amelia-booking',
            'amelia-combined-group-report',
            'amelia-list-comments',
            'amelia-program-meterials-report',
            'amelia-appt-list-schedule',
            'amelia-email-logs-redirect',
            'amelia-historical-photos',
            'amelia-photo-migration',
            'amelia-number-checker',
            'amelia-booking-counter',
            'amelia-waitlist',
            'wpamelia-setting',
            'wpamelia-bodychart',
        );
    }
    
    /**
     * Check whether the current admin screen belongs to our plugin.
     */
    public static function is_our_admin_page()
    {
        if (!is_admin()) {
            return false;
        }
        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
        return in_array($page, self::get_plugin_page_slugs(), true);
    }
    
    /**
     * Remove all admin notices (WP core update nag, third-party plugin banners,
     * "rate us" prompts, etc.) when viewing our plugin pages. Our own status
     * messages are rendered via inline HTML / settings_errors() and are NOT
     * affected by this hook removal.
     */
    public function suppress_admin_notices_on_our_pages()
    {
        if (!self::is_our_admin_page()) {
            return;
        }
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('user_admin_notices');
        remove_all_actions('network_admin_notices');
    }
    
    /**
     * CSS backup to hide any remaining WP update-nag / third-party banner that
     * bypasses the standard admin_notices hooks (e.g. printed directly in
     * admin_head). Only targets well-known banner classes to avoid hiding our
     * own status messages, which use generic `.notice` / `.updated`.
     */
    public function suppress_admin_notices_css()
    {
        if (!self::is_our_admin_page()) {
            return;
        }
        ?>
        <style id="wpamelia-suppress-notices">
            /* WP core update nag (printed directly in admin_head via update_nag hook) */
            .update-nag,
            #update-nag,
            /* Common third-party plugin banner/nag classes */
            .e-notice,
            .elementor-message,
            .yoast-notification,
            .yoast_premium_upsell,
            .wpseo-dismissible,
            .woocommerce-message,
            .woocommerce-error,
            .wc_plugin_upgrade_notice,
            .rank-math-notice,
            .wpforms-admin-notice,
            .wpforms-notice,
            .seedprod-notice,
            .updraftplus-notice,
            .itsec-admin-notice,
            .wpmudev-notice,
            .jetpack-dismissible-notice,
            .wp-rocket-notice,
            .siteground-notice {
                display: none !important;
            }
        </style>
        <?php
    }
    function admin_head_fucntion()
    {
?>
        <style>
            /* Your admin CSS here */
            textarea {
                resize: vertical !important;
                overflow: auto !important;
                min-height: 300px;
            }
        </style>
    <?php
    }
    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Wpamelia_Addon_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Wpamelia_Addon_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */


        wp_enqueue_style('bootstrap_css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), $this->version, 'all');
        wp_enqueue_style('alertify_css', 'https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css', array(), $this->version, 'all');
        wp_enqueue_style('alertify_min_css', 'https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/default.min.css', array(), $this->version, 'all');
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wpamelia-addon-admin.css', array(), $this->version, 'all');
        
        // Load Kawaii Booking styles for the booking dashboard page
        if (isset($_GET['page']) && $_GET['page'] === 'amelia-booking') {
            wp_enqueue_style('kawaii-booking-css', plugin_dir_url(__FILE__) . 'css/kawaii-booking.css', array(), $this->version, 'all');
        }
        
        // Load Kawaii Admin styles for Combined Report, Scheduled Lists, Comments, and Settings pages
        $kawaii_pages = array('amelia-combined-group-report', 'amelia-appt-list-schedule', 'wpamelia-setting', 'amelia-list-comments');
        if (isset($_GET['page']) && in_array($_GET['page'], $kawaii_pages)) {
            wp_enqueue_style('kawaii-admin-css', plugin_dir_url(__FILE__) . 'css/kawaii-admin.css', array(), $this->version, 'all');
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Wpamelia_Addon_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Wpamelia_Addon_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_media();
        wp_enqueue_script('amelia_addon_bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', array('jquery'), $this->version, false);
        wp_enqueue_script('alertifyjs', 'https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js', array('jquery'), $this->version, false);

        wp_enqueue_script('amelia_addon_pdf_min', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', array('jquery'), $this->version, false);
        wp_enqueue_script('amelia_addon_pdf_canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', array('jquery'), $this->version, false);
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-style', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css');
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wpamelia-addon-admin.js', array('jquery'), $this->version, false);
        wp_enqueue_script('ai-comment-js', plugin_dir_url(__FILE__) . 'js/ai-comment.js', array('jquery'), $this->version, false);
        wp_localize_script('ai-comment-js', 'AI_IMPROVER', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ai_improve_nonce'),
        ]);
        $wp_date_format = get_option('date_format');

        // Convert WordPress date format to jQuery UI format
        $date_format = str_replace(
            array('d', 'j', 'm', 'n', 'Y', 'y'),
            array('dd', 'd', 'mm', 'm', 'yy', 'y'),
            $wp_date_format
        );
        $plugin_url = plugin_dir_url(dirname(__DIR__));
        wp_localize_script($this->plugin_name, 'wpDateFormat', array('format' => $date_format));
        wp_localize_script($this->plugin_name, 'ajax', array(
            'url' => admin_url('admin-ajax.php'),
            'admin_url' => admin_url(),
            'plugin_url' => WPAMELIA_ADDON_PLUGIN_URL,
            'nonce' => wp_create_nonce('ajax-nonce')
        ));
        if (isset($_GET['page']) && $_GET['page'] === 'amelia-booking') {
            wp_enqueue_script(
                'amelia-booking-js',
                plugin_dir_url(__FILE__) . 'js/booking-admin.js',
                ['jquery'],
                $this->version,
                true
            );
            wp_localize_script('amelia-booking-js', 'ajax', array(
                'url' => admin_url('admin-ajax.php'),
                'admin_url' => admin_url(),
                'plugin_url' => WPAMELIA_ADDON_PLUGIN_URL,
                'nonce' => wp_create_nonce('ajax-nonce')
            ));
        }
    }
    public function admin_dashboard_page()
    {

        add_menu_page(
            // Parent menu slug,  // Parent menu slug
            'Amelia Addon',                       // Page title (hidden)
            'Amelia Addon',                       // Menu title (hidden)
            'manage_options',          // Capability
            'amelia-report',
            array($this, 'wpamelia_addon_about_page'), // Callback Function
            WPAMELIA_ADDON_PLUGIN_URL . 'admin/images/amelia-addon-icon.svg', // Icon
            81
        );
        // Override the duplicate first submenu item created by add_menu_page
        add_submenu_page(
            'amelia-report',
            'About',
            'About',
            'manage_options',
            'amelia-report',
            array($this, 'wpamelia_addon_about_page')
        );
        add_submenu_page(
            'amelia-report',
            'Group Reports',
            'Group Reports',
            'manage_options',
            'amelia-group-reports',
            array($this, 'wpamelia_screening_report_chart_details_page')
        );
        add_submenu_page(
            'amelia-report',
            'Booking Dashboard',
            'Booking Dashboard',
            'manage_options',
            'amelia-booking',
            array($this, 'wpamelia_booking_page')
        );
        add_submenu_page(
            'amelia-report',  // Parent menu slug
            'Combined Report',          // Page title
            'Combined Report',          // Menu title
            'manage_options',           // Capability
            'amelia-combined-group-report',   // Page slug
            array($this, 'wpamelia_combined_group_report_page') // Callback
        );
        add_submenu_page(

            'amelia-report',
            'Bank of Comments',                       // Page title (hidden)
            'Bank of Comments',                       // Menu title (hidden)
            'manage_options',          // Capability
            'amelia-list-comments',
            array($this, 'wpamelia_program_comments_page'), // Callback Function
            WPAMELIA_ADDON_PLUGIN_URL . 'admin/images/amelia-logo-admin-icon.svg', // Icon// Callback function,
            83
        );
        add_submenu_page(

            'amelia-report',
            'Program Materials',                       // Page title (hidden)
            'Program Materials',                       // Menu title (hidden)
            'manage_options',          // Capability
            'amelia-program-meterials-report',
            array($this, 'wpamelia_program_meterials_page'), // Callback Function
            WPAMELIA_ADDON_PLUGIN_URL . 'admin/images/amelia-logo-admin-icon.svg', // Icon// Callback function,
            82
        );
        add_submenu_page(
            'amelia-report',
            'Scheduled Lists',
            'Scheduled Lists',
            'manage_options',
            'amelia-appt-list-schedule',
            array($this, 'wpamelia_scheduled_lists_page')
        );
        add_submenu_page(
            'amelia-report',
            'Email Logs',
            'Email Logs',
            'manage_options',
            'amelia-email-logs-redirect',
            array($this, 'wpamelia_redirect_to_fluentsmtp_logs')
        );
        add_submenu_page(
            'amelia-report',  // Parent menu slug
            'Historical Photos', // Page title
            'Historical Photos', // Menu title
            'manage_options',    // Capability
            'amelia-historical-photos', // Page slug
            array($this, 'wpamelia_historical_photos_page') // Callback function
        );
        add_submenu_page(
            'amelia-report',  // Parent menu slug
            'Photo Migration', // Page title
            'Photo Migration', // Menu title
            'manage_options',    // Capability
            'amelia-photo-migration', // Page slug
            array($this, 'wpamelia_photo_migration_page') // Callback function
        );
        add_submenu_page(
            'amelia-report',  // Parent menu slug
            'Number Checker', // Page title
            'Number Checker', // Menu title
            'manage_options',    // Capability
            'amelia-number-checker', // Page slug
            array($this, 'wpamelia_number_checker_page') // Callback function
        );
        add_submenu_page(
            'amelia-report',
            'Booking Slot Counter',
            'Booking Slot Counter',
            'manage_options',
            'amelia-booking-counter',
            array($this, 'wpamelia_booking_counter_page')
        );
        add_submenu_page(
            'amelia-report',
            'Waitlist',
            'Waitlist',
            'manage_options',
            'amelia-waitlist',
            array($this, 'wpamelia_waitlist_page')
        );
        add_submenu_page(
            'amelia-report',  // Parent menu slug
            'Settings',        // Page title
            'Settings',        // Menu title
            'manage_options', // Capability
            'wpamelia-setting', // Page slug
            array($this, 'custom_smtp_settings_page') // Callback function
        );
        // Hidden page - accessible via URL but not shown in menu
        add_submenu_page(
            null,             // Null parent = hidden from all menus
            'Body Chart',     // Page title
            'Body Chart',     // Menu title (won't show since parent is null)
            'manage_options', // Capability
            'wpamelia-bodychart', // Page slug
            array($this, 'wpamelia_body_chart_details_page') // Callback function
        );
    }
    public function wpamelia_scheduled_lists_page()
    {
        // This is handled by AALS_Admin_Menu class
        $admin_menu = new AALS_Admin_Menu();
        $admin_menu->render_page();
    }
    public function wpamelia_booking_page()
    {
        include_once 'partials/booking-page.php';
    }
    public function wpamelia_historical_photos_page()
    {
        include_once 'partials/historical-photos.php';
    }
    public function wpamelia_photo_migration_page()
    {
        include_once 'partials/photo-migration.php';
    }
    public function wpamelia_number_checker_page()
    {
        include_once 'partials/number-checker.php';
    }
    public function wpamelia_booking_counter_page()
    {
        include_once 'partials/booking-counter-settings.php';
    }
    public function wpamelia_waitlist_page()
    {
        include_once 'partials/booking-counter-waitlist.php';
    }
    public function wpamelia_addon_about_page()
    {
        ?>
        <style>
            .kawaii-about-wrap {
                background: #D4F1E8;
                padding: 30px;
                border-radius: 16px;
                font-family: 'Quicksand', sans-serif;
                box-shadow: 0 8px 32px rgba(255, 181, 197, 0.25);
                max-width: 800px;
                margin-top: 20px;
            }
            .kawaii-about-wrap h1 {
                font-family: 'Quicksand', sans-serif;
                font-weight: 700;
                font-size: 28px;
                color: #5A4A5A;
                margin-bottom: 24px;
                display: inline-flex;
                align-items: center;
                gap: 12px;
            }
            .kawaii-about-wrap h1::before {
                content: '';
                display: inline-block;
                width: 8px;
                height: 8px;
                background: #FFB5C5;
                border-radius: 50%;
            }
            .kawaii-about-wrap h1::after {
                content: '';
                display: inline-block;
                width: 6px;
                height: 6px;
                background: #98D9C2;
                border-radius: 50%;
            }
            .kawaii-card {
                background: white;
                padding: 24px;
                border-radius: 16px;
                box-shadow: 0 4px 16px rgba(255, 181, 197, 0.12);
                margin-bottom: 20px;
            }
            .kawaii-card h2 {
                font-family: 'Quicksand', sans-serif;
                font-weight: 700;
                font-size: 18px;
                color: #5A4A5A;
                margin: 0 0 16px 0;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .kawaii-card h2 .icon-dot {
                width: 10px;
                height: 10px;
                background: linear-gradient(135deg, #FFB5C5 0%, #E8829A 100%);
                border-radius: 50%;
            }
            .kawaii-table {
                width: 100%;
                border-collapse: collapse;
            }
            .kawaii-table tr {
                border-bottom: 1px solid #FFE4EC;
            }
            .kawaii-table tr:last-child {
                border-bottom: none;
            }
            .kawaii-table th {
                font-family: 'Quicksand', sans-serif;
                font-weight: 600;
                color: #8A7A8A;
                text-align: left;
                padding: 12px 16px 12px 0;
                font-size: 14px;
                width: 140px;
            }
            .kawaii-table td {
                font-family: 'Quicksand', sans-serif;
                color: #5A4A5A;
                padding: 12px 0;
                font-size: 14px;
            }
            .kawaii-table td strong {
                font-weight: 700;
                color: #5A4A5A;
            }
            .kawaii-table td a {
                color: #5FBDA0;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.3s ease;
            }
            .kawaii-table td a:hover {
                color: #3A9A7D;
                text-decoration: underline;
                text-decoration-style: wavy;
                text-underline-offset: 3px;
            }
            .kawaii-version-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 14px;
                border-radius: 20px;
                background: #D4F1E8;
                color: #5FBDA0;
                font-weight: 600;
                font-size: 13px;
                border: 2px solid #98D9C2;
            }
            .kawaii-version-badge::before {
                content: '';
                width: 6px;
                height: 6px;
                background: #5FBDA0;
                border-radius: 50%;
            }
            .kawaii-ishortn-card {
                background: linear-gradient(135deg, #FFF9F5 0%, #FFE4EC 100%);
                border: 2px solid #FFB5C5;
            }
            .kawaii-ishortn-card h2 {
                color: #E8829A;
            }
            .kawaii-ishortn-card p {
                font-family: 'Quicksand', sans-serif;
                color: #5A4A5A;
                font-size: 14px;
                line-height: 1.6;
                margin: 0 0 20px 0;
            }
            .kawaii-mint-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 12px 24px;
                background: linear-gradient(135deg, #98D9C2 0%, #5FBDA0 100%);
                color: white !important;
                font-family: 'Quicksand', sans-serif;
                font-weight: 700;
                font-size: 14px;
                text-decoration: none !important;
                border-radius: 25px;
                border: none;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 16px rgba(152, 217, 194, 0.4);
            }
            .kawaii-mint-btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 24px rgba(152, 217, 194, 0.5);
                color: white !important;
            }
            .kawaii-mint-btn svg {
                width: 16px;
                height: 16px;
                stroke: white;
                stroke-width: 2;
            }
        </style>
        <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
        
        <div class="wrap">
            <div class="kawaii-about-wrap">
                <h1>Amelia Addon</h1>
                
                <!-- Plugin Info Card -->
                <div class="kawaii-card">
                    <h2><span class="icon-dot"></span>Plugin Information</h2>
                    <table class="kawaii-table">
                        <tr>
                            <th>Plugin Name:</th>
                            <td><strong>Amelia Addon</strong></td>
                        </tr>
                        <tr>
                            <th>Version:</th>
                            <td><span class="kawaii-version-badge"><?php echo esc_html(WPAMELIA_ADDON_VERSION); ?></span></td>
                        </tr>
                        <tr>
                            <th>Author:</th>
                            <td><a href="https://whiteheartgroup.com" target="_blank">White Heart Group</a></td>
                        </tr>
                        <tr>
                            <th>Description:</th>
                            <td>A custom extension for Amelia that enhances booking automation, scheduling logic, and client notifications for SkinChX.</td>
                        </tr>
                    </table>
                </div>
                
                <!-- iShortn Card -->
                <div class="kawaii-card kawaii-ishortn-card">
                    <h2><span class="icon-dot" style="background: linear-gradient(135deg, #FFB5C5 0%, #E8829A 100%);"></span>QR Codes & Short Links</h2>
                    <p>We create our QR codes and shortlinks on <strong>iShortn.ink</strong> — a fast and reliable link shortening service that helps us manage and track all our booking links efficiently.</p>
                    <a href="https://ishortn.ink" target="_blank" class="kawaii-mint-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        Visit iShortn.ink
                    </a>
                </div>
                
                <!-- Changelog Card -->
                <div class="kawaii-card">
                    <h2><span class="icon-dot"></span>Changelog</h2>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.7.4</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>NEW: Bulk import existing waitlist (per service)</strong> — collapsible <em>"📥 Import existing waitlist from CSV"</em> panel inside the Manual Entry card. Pick a service, paste rows from a spreadsheet (Excel / Google Sheets / Numbers — copy &amp; paste straight in) <em>or</em> upload a <code>.csv</code>/<code>.txt</code> file. Each row = <code>Name, Email, Phone, Months</code>. Months use 3-letter abbreviations (<code>Jan</code>, <code>Feb</code>, …) separated by semicolons or spaces (e.g. <code>Jan;Feb;Mar</code>) so they fit cleanly inside one CSV cell.</li>
                                <li><strong>Smart import safeguards:</strong> auto-detects tab vs. comma delimiter (so spreadsheet copy-paste just works), optional "first row is a header" toggle, skips duplicate emails already on that service's list, skips rows missing a name or valid email, tags every imported row with source <code>import</code>. End-of-import summary tells you how many were saved / skipped / invalid. No confirmation emails are sent on import (treated as historical data).</li>
                                <li><strong>NEW: Phone column</strong> — schema migration adds <code>phone VARCHAR(60)</code> to <code>wp_amelia_bc_waitlist</code> (idempotent <code>dbDelta</code>). Surfaces in the entries table (with <code>tel:</code> click-to-call), the manual Add Entry form, and the CSV export. Imports populate it from the third column.</li>
                                <li><strong>NEW: Demand chart now honours every filter</strong> — the per-service month-demand chart used to ignore the Service and Status inputs. Now it respects <strong>all four</strong> Filter card inputs (From, To, Service, Status). Pick a single service to focus the chart on one card, or pick Status = <em>new</em> to see demand from people you haven't contacted yet.</li>
                                <li><strong>UI: Card order rearranged + Manual Entry card compacted</strong> — Manual Entry now lives directly under <em>At a Glance</em>; Filter sits above the Demand chart so the chart's filters are always within reach. Manual Entry uses a 4-column header row (Name, Email, Phone, Service), flex-wrap month chips that match the frontend form's pill style, and a single shared row for Status + Notes — so the card takes ~2 rows less.</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.7.3</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>NEW: Customer confirmation email</strong> — auto-sent on every successful frontend signup, and optional on manual admin add (checkbox in the dashboard, default ON). Plain HTML template, uses your site name + admin email as sender. Best-effort: failures don't break the signup flow.</li>
                                <li><strong>NEW: Pagination</strong> — WordPress-style per-page selector (5 / 10 / 20 / 50) plus First / Prev / Next / Last nav on the waitlist dashboard. Per-page choice and filters are preserved across page navigation. Replaces the previous hard 500-row cap.</li>
                                <li><strong>NEW: In-panel Waitlist form</strong> — when the Booking Slot Counter renders the "fully booked" panel, customers can drop their name + email + one or more <strong>preferred booking months</strong> (Jan–Dec multi-select chips). Submissions land in <code>wp_amelia_bc_waitlist</code>.</li>
                                <li><strong>NEW: Waitlist admin dashboard</strong> — <em>Amelia Addon → Waitlist</em>. Filter by service / status / date range, change status per row (<code>new</code> / <code>exported</code> / <code>contacted</code> / <code>booked</code>), edit notes, manually add or remove entries.</li>
                                <li><strong>NEW: "Top Demanded Months" mini-charts</strong> — per-service bar chart (Jan–Dec) on the dashboard showing how many people requested each month. Top month highlighted in mint, the rest in pink. Sorted by total signups so the busiest service is at the top. Respects the From/To date filter so you can see this-month vs. this-quarter demand.</li>
                                <li><strong>NEW: CSV export</strong> — exports the currently filtered list (UTF-8 with BOM for Excel) and optionally auto-flips matching <code>new</code> rows to <code>exported</code> so it's clear what's already been pulled.</li>
                                <li><strong>NEW: Manual add</strong> — capture phone / DM signups directly from the dashboard with the same status + notes fields.</li>
                                <li><strong>Rate-limit:</strong> same email within 60 seconds returns success without inserting a duplicate row, so refresh-spam doesn't pollute the table.</li>
                                <li><strong>Schema:</strong> new table <code>wp_amelia_bc_waitlist</code> created/migrated idempotently on every <code>admin_init</code>.</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.7.2</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>PATCH — frontend display fix:</strong> Counter now ALSO renders via a JavaScript DOM injector, so it shows up on pages where Amelia is embedded through Elementor / Divi / Beaver Builder / Gutenberg widgets that mount the Vue app directly (bypassing the shortcode). The PHP shortcode filter still handles plain pages and Elementor's "Shortcode" widget. Polling + MutationObserver handle late-mounting Vue apps; no double-render when both paths fire.</li>
                                <li><strong>PATCH — per-service email merge:</strong> Decay-date auto-reminders now pull each service's own report email (saved on the body-chart admin panel — <code>wp_amelia_service_chart.data.email</code>) AND merge it with the global default recipients list (deduped, case-insensitive, invalid entries dropped). The "Default reminder recipient" field is now optional. The schedule's status_message lists which service-specific email was added.</li>
                                <li><strong>NEW: Booking Slot Counter</strong> — display "X of Y slots booked — Z available" above your <code>[ameliabooking]</code> / <code>[ameliastepbooking]</code> shortcodes</li>
                                <li><strong>How it counts:</strong> You enter the planned total slots per service; the plugin counts existing future appointments in <code>wp_amelia_appointments</code> and shows the difference</li>
                                <li><strong>Auto-fully-booked:</strong> When booked reaches the total, the panel switches to your configurable waitlist message — no manual intervention needed</li>
                                <li><strong>NEW: Decay date</strong> ("Slots valid through") per service — after that date passes, the panel auto-switches to the fully-booked / waitlist message</li>
                                <li><strong>NEW: Decay-date auto-reminder</strong> — set a global lead time + recipient email; the addon auto-manages an entry in your existing <em>Scheduled Lists</em> for every service with a decay date, so you get an email N days before slots run out</li>
                                <li><strong>Auto-managed schedules are tagged</strong> (<code>source_marker = "bc:decay:&lt;id&gt;"</code>) so they never collide with manually-created Scheduled Lists entries</li>
                                <li><strong>Per-service overrides:</strong> Manual "Mark as Fully Booked" checkbox + custom counter label + custom waitlist message (each falls back to a global default)</li>
                                <li><strong>Live status</strong> preview in the admin table — colour-coded chip (OK / Nearing capacity / Full / Decay passed)</li>
                                <li><strong>Counter variables:</strong> <code>{booked}</code>, <code>{total}</code>, <code>{remaining}</code>, <code>{service}</code></li>
                                <li><strong>Manual shortcodes:</strong> <code>[amelia_booking_counter service_ids="1,2"]</code> &middot; <code>[amelia_booking_full_message force="1"]</code></li>
                                <li><strong>Per-page opt-out:</strong> <code>hide_counter="1"</code> on the Amelia booking shortcode</li>
                                <li><strong>Schema migration:</strong> <code>aals_schedules</code> gains a <code>source_marker</code> column for tracking auto-managed schedules</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.7.1</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>FIX: Booking Dashboard date filter</strong> — early-morning appointments (e.g. 7am) were leaking to the previous day's page</li>
                                <li><strong>Root cause:</strong> Amelia stores <code>bookingStart</code> in UTC, but the filter compared it against local-date literal strings, causing a timezone offset bug</li>
                                <li><strong>Fix:</strong> The selected local date boundaries (12:00 AM – 11:59 PM) are now converted to UTC before querying — every appointment now appears on its correct calendar day</li>
                                <li><strong>NEW: Number Checker</strong> (Amelia Addon → Number Checker) — scans customers for phone numbers that need cleanup</li>
                                <li><strong>Number Checker:</strong> Detects two issues — missing country code, AND non-digit characters (parens, dashes, commas, spaces, letters); <em>Reason</em> column explains each flag</li>
                                <li><strong>Number Checker:</strong> Clean &amp; Apply strips every non-digit (preserving <code>+</code>), converts <code>00</code> to <code>+</code>, and prepends <code>+XX</code> to numbers missing a country code</li>
                                <li><strong>Number Checker:</strong> Country code is optional when every selected row already has one (formatting-only cleanup)</li>
                                <li><strong>Prevention tips included on page:</strong> Use Amelia's intl-tel-input phone field type in booking forms to force a country prefix at entry time</li>
                                <li><strong>NEW: Booking form helper note</strong> — a small informational note now appears under the phone field on the Amelia booking form: <em>"The country code is required to receive SMS notifications."</em> (purely informational, does not block Continue)</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.7.0</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>NEW: Hover Thumbnail Preview</strong> - Hover over any red spot with an attached photo to see a floating preview next to your cursor</li>
                                <li><strong>Hover Preview:</strong> Shows the full photo thumbnail (up to 200px), lesion ID (if linked), smart positioning to stay on-screen</li>
                                <li><strong>Cursor Feedback:</strong> Changes to "zoom-in" when hovering a photo-attached spot; "crosshair" elsewhere</li>
                                <li><strong>Auto-hide:</strong> Preview vanishes when you click a new spot or open the upload modal</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.6.9</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>NEW:</strong> Admin notice badges/banners from third-party plugins are now suppressed on Amelia Addon pages</li>
                                <li><strong>Covers:</strong> WP core update nag, Yoast, WooCommerce, Elementor, Rank Math, WPForms, SeedProd, UpdraftPlus, iThemes, WPMU DEV, Jetpack, WP Rocket, SiteGround, and any plugin using the standard <code>admin_notices</code> hooks</li>
                                <li><strong>Preserved:</strong> Our own success/error messages still display normally</li>
                                <li><strong>FIX: Photo Migration</strong> - Now scans the correct body chart tables (<code>wp_amelia_body_chart</code> &amp; <code>wp_amelia_body_chart_ref</code>) — previously looked in the wrong table, which is why no photos were ever found</li>
                                <li><strong>FIX: Photo Migration</strong> - Resolves customer IDs correctly by joining through customer_bookings</li>
                                <li><strong>FIX: Photo Migration</strong> - Updates the source body-chart row's data JSON after moving the file to protected storage</li>
                                <li><strong>NEW: "Reset &amp; Re-scan" button</strong> on the Photo Migration page — re-run the migration after a prior completion</li>
                                <li><strong>Photo Migration:</strong> Now skips URLs already in <code>/patient-photos/</code> (won't re-process migrated photos)</li>
                                <li><strong>NEW: Body Chart Marker Photos</strong> - Clicking a red spot on any canvas now opens an "Attach Photo" popup with <strong>Upload from Device</strong> or <strong>Take Photo</strong> (mobile camera)</li>
                                <li><strong>Marker Photos:</strong> Uploaded directly to <code>/wp-content/patient-photos/</code> — never enter the Media Library</li>
                                <li><strong>Marker Photos:</strong> Saved to Historical Photos DB with exact marker coords, customer_id, appointment_id, body_location</li>
                                <li><strong>Marker Photos:</strong> Auto-linked to nearby existing lesions for visit-over-visit tracking</li>
                                <li><strong>Marker Photos:</strong> Red spots with attached photos show a small green camera badge; undoing the spot also deletes the photo</li>
                                <li><strong>Marker Photos:</strong> Existing patient photos auto-match to red spots by coordinate proximity on reload</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.6.8</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>FIX:</strong> "Last Booked Appointment" box now live-updates when you tick/untick services (AJAX)</li>
                                <li><strong>NEW:</strong> "Use this date/time" button next to the last appointment — one-click copies it into the reminder pickers</li>
                                <li><strong>FIX:</strong> Scheduled Lists table Timing column now shows the picked date/time for reminders</li>
                                <li><strong>FIX:</strong> Edit Schedule summary reflects the picked date/time (and updates as you change it)</li>
                                <li><strong>Summary:</strong> Attendee list summary now reflects before/after direction and last/first anchor accurately</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.6.7</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>NEW:</strong> Preview button now works for Group Report Reminder schedules</li>
                                <li><strong>Reminder Preview:</strong> Shows resolved Subject + Body with placeholders replaced (customer name, last appointment, hours since)</li>
                                <li><strong>Reminder Preview:</strong> CSV section hidden (not applicable to reminders)</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.6.6</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>FIX: Group Report Reminder</strong> - Date/Time picker now saves correctly (was being overwritten by appointment-based recalculation)</li>
                                <li><strong>NEW:</strong> Separate default email template for Group Report Reminder (Subject + Body) in Settings</li>
                                <li><strong>Reminder Email Placeholders:</strong> <code>{customer_name}</code>, <code>{last_appointment_date}</code>, <code>{last_appointment_time}</code>, <code>{hours_since_appointment}</code></li>
                                <li><strong>Edit Schedule:</strong> Default email preview auto-swaps when switching between Attendee List / Reminder</li>
                                <li><strong>Status message:</strong> Shows the scheduled local date/time for group report reminders</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.6.5</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>NEW: Group Report Reminder</strong> - New schedule type in Scheduled Lists</li>
                                <li><strong>Group Report Reminder:</strong> Shows last booked appointment date/time for selected services</li>
                                <li><strong>Group Report Reminder:</strong> Set specific date and time for the reminder email</li>
                                <li><strong>Group Report Reminder:</strong> Simple email notification - event finished, please send report</li>
                                <li><strong>Scheduled Lists:</strong> New schedule type selector (Attendee List Export vs Group Report Reminder)</li>
                                <li><strong>Database:</strong> Added schedule_type and time_direction columns</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.6.0</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>NEW: Historical Photos</strong> - Search customers and view all their photos across appointments</li>
                                <li><strong>Historical Photos:</strong> Body region overview showing photo counts per body location</li>
                                <li><strong>Historical Photos:</strong> Automatic lesion tracking - photos at similar body chart coordinates are linked together</li>
                                <li><strong>Historical Photos:</strong> Tracked Lesions panel showing all unique lesions with first/last seen dates</li>
                                <li><strong>Historical Photos:</strong> Photo timeline view grouped by appointment date</li>
                                <li><strong>Historical Photos:</strong> Side-by-side comparison tool - select up to 4 photos to compare</li>
                                <li><strong>NEW: Photo Migration Tool</strong> - Move existing photos from Media Library to protected storage</li>
                                <li><strong>Photo Migration:</strong> Scans body chart data for existing photos</li>
                                <li><strong>Photo Migration:</strong> Moves files to protected wp-content/patient-photos/ directory</li>
                                <li><strong>Photo Migration:</strong> Updates all body chart references and removes from Media Library</li>
                                <li><strong>NEW: AI Lesion Analysis</strong> - GPT-4o Vision integration for comparing photos</li>
                                <li><strong>AI Analysis:</strong> Analyzes size, color, border, shape, and surface changes</li>
                                <li><strong>AI Analysis:</strong> ABCDE criteria assessment with risk level (Low/Medium/High)</li>
                                <li><strong>AI Analysis:</strong> Save analysis results to patient record</li>
                                <li><strong>Database:</strong> New wp_amelia_patient_photos table for secure photo storage</li>
                                <li><strong>Security:</strong> Protected photos directory with .htaccess restrictions</li>
                                <li><strong>Referral Report:</strong> DOB field restored as manual entry (auto-population removed)</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.5.1</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>Referral Report: Signature image constrained to 300px, left-aligned</strong></li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.5.0</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>Performance updates</strong></li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.3.5</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>Referral Chart:</strong> Empty image boxes (N/A) are now hidden during PDF generation; only uploaded images print, centered in the row</li>
                                <li><strong>Program Materials:</strong> Fixed mobile preview dead space caused by CSS transform scaling; replaced with zoom-based scaling</li>
                                <li><strong>Program Materials:</strong> Fixed PDF generation on mobile producing incorrect sizes; generating mode now fully overrides mobile styles</li>
                                <li><strong>General:</strong> Updated version constant and header to 9.3.5</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.3.4</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>Referral Chart:</strong> Changed image layout to single row (matching info page style)</li>
                                <li><strong>Referral Chart:</strong> Added green borders (20px) around image slots</li>
                                <li><strong>Referral Chart:</strong> Images now fixed at 200x200 pixels, centered</li>
                                <li><strong>Referral Chart:</strong> Empty slots now display "N/A" placeholder</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.3.3</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>Program Materials:</strong> Applied kawaii color scheme to match other plugin pages</li>
                                <li><strong>Program Materials:</strong> Fixed mobile preview - now displays at desktop width with horizontal scroll</li>
                                <li><strong>Program Materials:</strong> Improved page layout with styled cards and sections</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.3.2</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>Scheduled Lists:</strong> Removed duplicate "Create New Schedule" button from page header</li>
                                <li><strong>PDF Generation:</strong> Fixed mobile PDF generation - PDFs now render at desktop dimensions regardless of device</li>
                                <li><strong>PDF Generation:</strong> Fixed gauge/risk level sizing in PDF exports</li>
                                <li><strong>Referral Chart:</strong> Added 2x2 image upload grid with dynamic sizing (300x300 for single image, 150x150 for multiple)</li>
                                <li><strong>Referral Chart:</strong> Replaced placeholder with "Attach Images" button when no images are present</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.3.1</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>Mobile UI:</strong> Fixed button overflow issues on mobile view screens across all admin pages</li>
                                <li><strong>Mobile UI:</strong> Improved responsive layout for action buttons, forms, and pagination</li>
                                <li><strong>UI:</strong> Fixed gradient color consistency - gradients now use single color families only</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.3.0</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>Scheduled Lists:</strong> Simplified CSV export - now automatically exports Start Date/Time, Customer First Name, Last Name, Email, and Phone</li>
                                <li><strong>Scheduled Lists:</strong> Added "All Statuses" option to appointment status filter - includes Appointment Status field in export when selected</li>
                                <li><strong>Scheduled Lists:</strong> Improved email customization - shows default settings with option to customize per schedule</li>
                                <li><strong>Scheduled Lists:</strong> Added global default email settings in Settings page - edit defaults that apply to all schedules</li>
                                <li><strong>Scheduled Lists:</strong> Email customization now pre-fills with defaults for easier editing</li>
                                <li><strong>UI Updates:</strong> Various minor interface improvements</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.2.6</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>Group Reports:</strong> Replaced separate upload button with "Select from Media Library" button for logo selection</li>
                                <li><strong>Program Materials:</strong> Updated text field labels to "Line 1 Text", "Line 2 Text", "Line 3 Text"</li>
                                <li><strong>Program Materials:</strong> Fixed typo - removed duplicate "Our Day" text from session times description</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.2.5</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>Scheduled Lists:</strong> Fixed timezone issue - exported CSV dates now match WordPress site timezone settings (e.g., Perth)</li>
                                <li><strong>Scheduled Lists:</strong> Email timestamps now display in site timezone instead of UTC</li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #FFE4EC;">
                            <h3 style="font-family: 'Quicksand', sans-serif; font-size: 16px; color: #5A4A5A; margin: 0 0 12px 0;">
                                <span class="kawaii-version-badge" style="font-size: 12px; padding: 4px 10px;">v9.2.4</span>
                            </h3>
                            <ul style="margin: 0; padding-left: 20px; color: #5A4A5A; font-family: 'Quicksand', sans-serif; font-size: 14px; line-height: 1.8;">
                                <li><strong>Booking Dashboard:</strong> Booking count badge now displays sky blue when count is greater than 1</li>
                                <li><strong>Booking Dashboard:</strong> Customer name links styled with mint green bold text</li>
                                <li><strong>Booking Dashboard:</strong> Pagination active page changed from gradient to solid mint green</li>
                                <li><strong>Bank of Comments:</strong> Applied kawaii design styling to comments list page</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    public function wpamelia_program_qr_page()
    {
        include_once 'partials/qr_page.php';
    }
    public function wpamelia_redirect_to_fluentsmtp_logs()
    {
        // Redirect to FluentSMTP email logs
        $fluentsmtp_url = admin_url('options-general.php?page=fluent-mail#/logs');
        wp_redirect($fluentsmtp_url);
        exit;
    }
    public function wpamelia_program_comments_page()
    {
        global $wpdb;
        $page_name = 'amelia-list-comments';
        $table_name = $wpdb->prefix . 'amelia_report_comments';
        if (isset($_GET['action']) && $_GET['action'] == 'add' || isset($_GET['action']) && $_GET['action'] == 'edit') {
            include_once 'partials/add-comment-page.php';
        } else {
            include_once 'partials/comment-page.php';
        }

        // exit;
    }
    public function display_amelia_report_page()
    {
        include_once 'partials/report-chart.php';
    }
    public function wpamelia_program_meterials_page()
    {
        include_once 'partials/program_meterials.php';
    }
    public function wpamelia_body_chart_details_page()
    {
        $referal = isset($_GET['referal']) ? $_GET['referal'] : '';
        if (!$referal) {
            include_once 'partials/body-chart.php';
        } else {
            include_once 'partials/body-chart-referal.php';
        }
    }
    public function wpamelia_screening_report_chart_details_page()
    {
        include_once 'partials/wpamelia-addon-admin-display.php';
    }
    public function custom_smtp_settings_page()
    {
    ?>
        <div class="wrap">
            <div class="kawaii-wrap">
                <h1 class="kawaii-page-title">Settings</h1>
                
                <div class="kawaii-card">
                    <h2><span class="icon-dot"></span>Plugin Configuration</h2>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('smtp_settings_group');
                        do_settings_sections('smtp-settings');
                        submit_button('Save Settings', 'button button-primary');
                        ?>
                    </form>
                </div>
            </div>
        </div>
<?php
    }
    /**
     * Renders the Combined Group Report admin page (multi-service + saved reports).
     */
    public function wpamelia_combined_group_report_page()
    {
        include_once 'partials/combined-group-report-settings.php';
    }
    function api_settings_init()
    {

        register_setting('resend_api_group', 'resend_api_username');
        register_setting('resend_api_group', 'resend_api_password');
        register_setting('qrcode-tiger_api_group', 'qrcode-tiger_username');
        register_setting('qrcode-tiger_api_group', 'qrcode-tiger_password');
    }
    function custom_smtp_settings_init()
    {
        // Register email template settings (subjects and body content)
        register_setting('smtp_settings_group', 'email_subject_body_chart');
        register_setting('smtp_settings_group', 'email_body_content');
        register_setting('smtp_settings_group', 'email_subject_individual');
        register_setting('smtp_settings_group', 'ind_email_body_content');
        register_setting('smtp_settings_group', 'email_subject_referal');
        register_setting('smtp_settings_group', 'ref_email_body_content');
        register_setting('smtp_settings_group', 'email_subject_group');
        register_setting('smtp_settings_group', 'group_email_body_content');
        register_setting('smtp_settings_group', 'email_subject_materials');
        register_setting('smtp_settings_group', 'group_email_body_content_meterials');

        register_setting('smtp_settings_group', 'chatGpt_api_key');
        register_setting('smtp_settings_group', 'chatGpt_api_role_system_prompt');
        register_setting('smtp_settings_group', 'chatGpt_api_role_user_prompt');
        register_setting('smtp_settings_group', 'chatGpt_api_role_user_prompt_description_improvement');
        
        // Scheduled Lists default email settings
        register_setting('smtp_settings_group', 'aals_default_email_subject');
        register_setting('smtp_settings_group', 'aals_default_email_body');
        
        // Group Report Reminder default email (separate from attendee list default)
        register_setting('smtp_settings_group', 'aals_default_reminder_email_subject');
        register_setting('smtp_settings_group', 'aals_default_reminder_email_body');

        // Add email templates section
        add_settings_section(
            'email_templates_section',
            'Email Templates',
            array($this, 'email_templates_section_description'),
            'smtp-settings'
        );

        // Body Chart Email
        add_settings_field('email_subject_body_chart', 'Body Chart Email Subject', array($this, 'email_subject_body_chart_field'), 'smtp-settings', 'email_templates_section');
        add_settings_field('email_body_content', 'Body Chart Email Body Content', array($this, 'email_body_content'), 'smtp-settings', 'email_templates_section');

        // Individual Profile Email
        add_settings_field('email_subject_individual', 'Individual Profile Email Subject', array($this, 'email_subject_individual_field'), 'smtp-settings', 'email_templates_section');
        add_settings_field('ind_email_body_content', 'Individual Profile Email Body Content', array($this, 'ind_email_body_content'), 'smtp-settings', 'email_templates_section');

        // Referal Chart Email
        add_settings_field('email_subject_referal', 'Referal Chart Email Subject', array($this, 'email_subject_referal_field'), 'smtp-settings', 'email_templates_section');
        add_settings_field('ref_email_body_content', 'Referal Chart Email Body Content', array($this, 'ref_email_body_content'), 'smtp-settings', 'email_templates_section');

        // Group Chart Email
        add_settings_field('email_subject_group', 'Group Chart Email Subject', array($this, 'email_subject_group_field'), 'smtp-settings', 'email_templates_section');
        add_settings_field('group_email_body_content', 'Group Chart Email Body Content', array($this, 'group_email_body_content'), 'smtp-settings', 'email_templates_section');

        // Program Materials Email
        add_settings_field('email_subject_materials', 'Program Materials Email Subject', array($this, 'email_subject_materials_field'), 'smtp-settings', 'email_templates_section');
        add_settings_field('group_email_body_content_meterials', 'Program Materials Email Body Content', array($this, 'group_email_body_content_meterials'), 'smtp-settings', 'email_templates_section');

        // Scheduled Lists Email Section
        add_settings_section(
            'scheduled_lists_email_section',
            'Scheduled Lists Default Email',
            array($this, 'scheduled_lists_email_section_description'),
            'smtp-settings'
        );
        
        add_settings_field('aals_default_email_subject', 'Default Email Subject', array($this, 'aals_default_email_subject_field'), 'smtp-settings', 'scheduled_lists_email_section');
        add_settings_field('aals_default_email_body', 'Default Email Body', array($this, 'aals_default_email_body_field'), 'smtp-settings', 'scheduled_lists_email_section');

        // Group Report Reminder Default Email Section (separate, simpler email)
        add_settings_section(
            'group_report_reminder_email_section',
            'Group Report Reminder Default Email',
            array($this, 'group_report_reminder_email_section_description'),
            'smtp-settings'
        );
        
        add_settings_field('aals_default_reminder_email_subject', 'Default Reminder Subject', array($this, 'aals_default_reminder_email_subject_field'), 'smtp-settings', 'group_report_reminder_email_section');
        add_settings_field('aals_default_reminder_email_body', 'Default Reminder Body', array($this, 'aals_default_reminder_email_body_field'), 'smtp-settings', 'group_report_reminder_email_section');

        // AI Settings Section
        add_settings_section(
            'ai_settings_section',
            'AI Settings',
            null,
            'smtp-settings'
        );

        add_settings_field('chatGpt_api_key', 'ChatGpt Api Key', array($this, 'chatGpt_api_key'), 'smtp-settings', 'ai_settings_section');
        add_settings_field('chatGpt_api_role_system_prompt', 'Group Report System Prompt', array($this, 'chatGpt_api_role_system_prompt'), 'smtp-settings', 'ai_settings_section');
        add_settings_field('chatGpt_api_role_user_prompt', 'Group Report Role User Prompt', array($this, 'chatGpt_api_role_user_prompt'), 'smtp-settings', 'ai_settings_section');
        add_settings_field('chatGpt_api_role_user_prompt_improvement', 'AI Prompt For Comment Improvement', array($this, 'chatGpt_api_role_user_prompt_description_improvement'), 'smtp-settings', 'ai_settings_section');
    }

    function email_templates_section_description()
    {
        echo '<p>Configure email subjects and body content for each email type. Emails are sent via FluentSMTP.</p>';
    }
    
    function scheduled_lists_email_section_description()
    {
        echo '<p>Configure the default email template for Scheduled Lists exports. These defaults are used when no custom email is specified for a schedule. Variables: <code>{service_name}</code>, <code>{count}</code>, <code>{date}</code>, <code>{time}</code>, <code>{schedule_id}</code></p>';
    }
    
    function aals_default_email_subject_field()
    {
        $default = 'Amelia Appointments: {service_name} ({count} appointments)';
        $value = get_option('aals_default_email_subject', $default);
        echo '<input type="text" name="aals_default_email_subject" value="' . esc_attr($value) . '" class="regular-text" style="width: 100%;">';
        echo '<p class="description">Default: ' . esc_html($default) . '</p>';
    }
    
    function aals_default_email_body_field()
    {
        $default = 'This is an automated email containing your scheduled appointment export.

Service: {service_name}
Total Appointments: {count}
Schedule ID: #{schedule_id}
Generated: {date} at {time}

Please find the attached CSV file with the complete appointment details.

---
This email was sent automatically by the Amelia Appointment List Schedule plugin.';
        $value = get_option('aals_default_email_body', $default);
        echo '<textarea name="aals_default_email_body" rows="12" class="large-text" style="width: 100%;">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">You can use HTML for formatting.</p>';
    }
    
    function group_report_reminder_email_section_description()
    {
        echo '<p>Configure the default email for <strong>Group Report Reminder</strong> schedules (no CSV attachment - just a simple reminder). These defaults are used when no custom email is specified on the schedule. ';
        echo 'Variables: <code>{services}</code>, <code>{service_name}</code>, <code>{customer_name}</code>, <code>{last_appointment_date}</code>, <code>{last_appointment_time}</code>, <code>{hours_since_appointment}</code>, <code>{schedule_id}</code>, <code>{date}</code>, <code>{time}</code></p>';
    }
    
    function aals_default_reminder_email_subject_field()
    {
        $default = 'Group Report Reminder: {services}';
        $value = get_option('aals_default_reminder_email_subject', $default);
        echo '<input type="text" name="aals_default_reminder_email_subject" value="' . esc_attr($value) . '" class="regular-text" style="width: 100%;">';
        echo '<p class="description">Default: ' . esc_html($default) . '</p>';
    }
    
    function aals_default_reminder_email_body_field()
    {
        $default = 'Hi {customer_name},

The appointments for {services} have completed.

Last appointment: {last_appointment_date} at {last_appointment_time}
Time since last appointment: {hours_since_appointment} hours

Please review and send the group report.

---
This is an automated reminder from the SkinChX Addon plugin.
Schedule ID: #{schedule_id}
Generated: {date} at {time}';
        $value = get_option('aals_default_reminder_email_body', $default);
        echo '<textarea name="aals_default_reminder_email_body" rows="14" class="large-text" style="width: 100%;">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">You can use HTML for formatting. Line breaks will be preserved automatically in plain text.</p>';
    }

    function email_subject_body_chart_field()
    {
        $value = get_option('email_subject_body_chart', 'Your Body Chart Report from SkinChx');
        echo '<input type="text" name="email_subject_body_chart" value="' . esc_attr($value) . '" class="regular-text" style="width: 100%;">';
    }

    function email_subject_individual_field()
    {
        $value = get_option('email_subject_individual', 'Your Individual Profile Report from SkinChx');
        echo '<input type="text" name="email_subject_individual" value="' . esc_attr($value) . '" class="regular-text" style="width: 100%;">';
    }

    function email_subject_referal_field()
    {
        $value = get_option('email_subject_referal', 'Your Referal Chart Report from SkinChx');
        echo '<input type="text" name="email_subject_referal" value="' . esc_attr($value) . '" class="regular-text" style="width: 100%;">';
    }

    function email_subject_group_field()
    {
        $value = get_option('email_subject_group', 'Your Group Chart Report from SkinChx');
        echo '<input type="text" name="email_subject_group" value="' . esc_attr($value) . '" class="regular-text" style="width: 100%;">';
    }

    function email_subject_materials_field()
    {
        $value = get_option('email_subject_materials', 'Your Program Materials from SkinChx');
        echo '<input type="text" name="email_subject_materials" value="' . esc_attr($value) . '" class="regular-text" style="width: 100%;">';
    }
    /*function higher_risk_question(){
    $value = get_option('higher_risk_question', '');
    if(!$value){
       $value='' 
    }
     echo '<textarea  name="higher_risk_question"  class="regular-text">' . esc_attr($value) . '</textarea>';
}
function skin_cancer_question(){
      $value = get_option('skin_cancer_question', '');
    echo '<textarea  name="skin_cancer_question" class="regular-text">' . esc_attr($value) . '</textarea>';
}*/
    function email_body_content()
    {
        $content = get_option('email_body_content', ''); // Get saved value
        if (!$content) {
            $content = 'Dear <strong>[name]</strong>,

Please find your medical report attached to this email.

If you have any questions, feel free to contact us.

Best regards,
<a href="' . site_url() . '"><strong>SkinChx</strong></a>';
            update_option('email_body_content', $content);
        }
        $editor_id = 'email_body_content';

        wp_editor($content, $editor_id, array(
            'textarea_name' => 'email_body_content',
            'media_buttons' => true,
            'textarea_rows' => 10,
            'teeny' => false,
            'quicktags' => true,
        ));
    }
    function ref_email_body_content()
    {
        $content = get_option('ref_email_body_content', ''); // Get saved value
        if (!$content) {
            $content = 'Dear <strong>[name]</strong>,

Please find your medical report attached to this email.

If you have any questions, feel free to contact us.

Best regards,
<a href="' . site_url() . '"><strong>SkinChx</strong></a>';
            update_option('ref_email_body_content', $content);
        }
        $editor_id = 'ref_email_body_content';

        wp_editor($content, $editor_id, array(
            'textarea_name' => 'ref_email_body_content',
            'media_buttons' => true,
            'textarea_rows' => 10,
            'teeny' => false,
            'quicktags' => true,
        ));
    }
    function ind_email_body_content()
    {
        $content = get_option('ind_email_body_content', ''); // Get saved value
        if (!$content) {
            $content = 'Dear <strong>[name]</strong>,

Please find your medical report attached to this email.

If you have any questions, feel free to contact us.

Best regards,
<a href="' . site_url() . '"><strong>SkinChx</strong></a>';
            update_option('ind_email_body_content', $content);
        }
        $editor_id = 'ind_email_body_content';

        wp_editor($content, $editor_id, array(
            'textarea_name' => 'ind_email_body_content',
            'media_buttons' => true,
            'textarea_rows' => 10,
            'teeny' => false,
            'quicktags' => true,
        ));
    }
    function group_email_body_content()
    {
        $content = get_option('group_email_body_content', ''); // Get saved value
        if (!$content) {
            $content = 'Dear <strong>[name]</strong>,

Please find your medical report attached to this email.

If you have any questions, feel free to contact us.

Best regards,
<a href="' . site_url() . '"><strong>SkinChx</strong></a>';
            update_option('group_email_body_content', $content);
        }
        $editor_id = 'group_email_body_content';

        wp_editor($content, $editor_id, array(
            'textarea_name' => 'group_email_body_content',
            'media_buttons' => true,
            'textarea_rows' => 10,
            'teeny' => false,
            'quicktags' => true,
        ));
    }
    function group_email_body_content_meterials()
    {
        $content = get_option('group_email_body_content_meterials', ''); // Get saved value
        if (!$content) {
            $content = 'Dear <strong>[name]</strong>,

Please find your medical report attached to this email.

If you have any questions, feel free to contact us.

Best regards,
<a href="' . site_url() . '"><strong>SkinChx</strong></a>';
            update_option('group_email_body_content_meterials', $content);
        }
        $editor_id = 'group_email_body_content_meterials';

        wp_editor($content, $editor_id, array(
            'textarea_name' => 'group_email_body_content_meterials',
            'media_buttons' => true,
            'textarea_rows' => 10,
            'teeny' => false,
            'quicktags' => true,
        ));
    }
    function chatGpt_api_key()
    {
        $value = get_option('chatGpt_api_key', '');
        echo '<input type="text" name="chatGpt_api_key" value="' . esc_attr($value) . '" class="regular-text">';
    }
    function chatGpt_api_role_system_prompt()
    {
        $value = get_option('chatGpt_api_role_system_prompt', '');
        echo '<textarea type="text" name="chatGpt_api_role_system_prompt" value="" style="width: 100%;">' . esc_attr($value) . '</textarea>';
    }
    function chatGpt_api_role_user_prompt()
    {
        $value = get_option('chatGpt_api_role_user_prompt', '');
        echo '<textarea type="text" name="chatGpt_api_role_user_prompt" value="' . esc_attr($value) . '" class="" style="width: 100%;">' . esc_attr($value) . '</textarea>';
    }
    function chatGpt_api_role_user_prompt_description_improvement()
    {
        $value = get_option('chatGpt_api_role_user_prompt_description_improvement', '');
        echo '<textarea type="text" name="chatGpt_api_role_user_prompt_description_improvement" value="' . esc_attr($value) . '" class="" style="width: 100%;">' . esc_attr($value) . '</textarea>';
    }
}
