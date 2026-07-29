<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              
 * @since             1.0.0
 * @package           Wpamelia_Addon
 *
 * @wordpress-plugin
 * Plugin Name:       Amelia Addon
 * Plugin URI:        
 * Description:       A custom extension for Amelia that enhances booking automation, scheduling logic, and client notifications for SkinChX.
 * Version:           9.7.4
 * Author:            White Heart Group
 * Author URI:        https://whiteheartgroup.com
 * License:           
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpamelia-addon
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WPAMELIA_ADDON_VERSION', '9.7.4');

define('WPAMELIA_ADDON_PLUGIN_DIR', plugin_dir_path(__FILE__)); // Absolute path
define('WPAMELIA_ADDON_PLUGIN_URL', plugin_dir_url(__FILE__)); // URL path
define('WPAMELIA_ADDON_PLUGIN_BASENAME', plugin_basename(__FILE__)); // Plugin basename
require WPAMELIA_ADDON_PLUGIN_DIR . 'email_sent/functions.php';
require WPAMELIA_ADDON_PLUGIN_DIR . 'form-autocomplete/functions.php';
require_once WPAMELIA_ADDON_PLUGIN_DIR . 'public/booking-counter.php';
require_once WPAMELIA_ADDON_PLUGIN_DIR . 'public/booking-counter-decay-sync.php';
require_once WPAMELIA_ADDON_PLUGIN_DIR . 'public/booking-counter-waitlist.php';

/**
 * ==========================================
 * SCHEDULED ATTENDEE LIST FEATURE (AALS)
 * Merged from amelia-appt-list-schedule plugin
 * ==========================================
 */
define('AALS_VERSION', '3.0.0');
define('AALS_PLUGIN_DIR', WPAMELIA_ADDON_PLUGIN_DIR . 'schedule/');
define('AALS_PLUGIN_URL', WPAMELIA_ADDON_PLUGIN_URL . 'schedule/');
define('AALS_PLUGIN_BASENAME', WPAMELIA_ADDON_PLUGIN_BASENAME);

// Load schedule feature includes
require_once AALS_PLUGIN_DIR . 'includes/class-database.php';
require_once AALS_PLUGIN_DIR . 'includes/class-admin-menu.php';
require_once AALS_PLUGIN_DIR . 'includes/class-schedule-list-table.php';
require_once AALS_PLUGIN_DIR . 'includes/class-schedule-manager.php';
require_once AALS_PLUGIN_DIR . 'includes/class-cron-handler.php';
require_once AALS_PLUGIN_DIR . 'includes/providers/interface-appointments-provider.php';
require_once AALS_PLUGIN_DIR . 'includes/providers/class-api-provider.php';
require_once AALS_PLUGIN_DIR . 'includes/providers/class-db-fallback-provider.php';
require_once AALS_PLUGIN_DIR . 'includes/class-csv-exporter.php';
require_once AALS_PLUGIN_DIR . 'includes/class-email-sender.php';
require_once AALS_PLUGIN_DIR . 'includes/class-ajax-handler.php';

/**
 * Initialize the Scheduled Attendee List feature
 */
add_filter('cron_schedules', function($schedules) {
    $schedules['aals_fifteen_minutes'] = array(
        'interval' => 900,
        'display'  => __('Every 15 Minutes')
    );
    return $schedules;
});

add_action('plugins_loaded', function() {
    if (is_admin()) {
        new AALS_Admin_Menu();
        new AALS_Ajax_Handler();
    }
    new AALS_Cron_Handler();
});

// Schedule activation hook for AALS tables
function activate_aals_schedule_feature() {
    AALS_Database::create_tables();
    if (!wp_next_scheduled('aals_check_schedules')) {
        wp_schedule_event(time(), 'aals_fifteen_minutes', 'aals_check_schedules');
    }
}

// Deactivation hook for AALS
function deactivate_aals_schedule_feature() {
    wp_clear_scheduled_hook('aals_check_schedules');
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpamelia-addon-activator.php
 */
function activate_wpamelia_addon()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-wpamelia-addon-activator.php';
    Wpamelia_Addon_Activator::activate();
    
    // Also activate the Scheduled Attendee List feature
    activate_aals_schedule_feature();
}
/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpamelia-addon-deactivator.php
 */
function deactivate_wpamelia_addon()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-wpamelia-addon-deactivator.php';
    Wpamelia_Addon_Deactivator::deactivate();
    
    // Also deactivate the Scheduled Attendee List feature
    deactivate_aals_schedule_feature();
}

register_activation_hook(__FILE__, 'activate_wpamelia_addon');
register_deactivation_hook(__FILE__, 'deactivate_wpamelia_addon');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wpamelia-addon.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpamelia_addon()
{

    $plugin = new Wpamelia_Addon();
    $plugin->run();
}
run_wpamelia_addon();

/**
 * Run database migrations on admin init
 * @since 9.6.5
 */
add_action('admin_init', 'wpamelia_addon_run_migrations');
function wpamelia_addon_run_migrations() {
    // Only run if schedule system is loaded
    if (class_exists('AALS_Database')) {
        // Check if we've already run the 9.6.5 migration
        $migration_version = get_option('wpamelia_addon_db_version', '0');
        if (version_compare($migration_version, '9.6.5', '<')) {
            AALS_Database::migrate_add_schedule_type_columns();
            update_option('wpamelia_addon_db_version', '9.6.5');
        }
    }
}

function get_appoinment_booking($id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'amelia_appointments';
    $results = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
    return $results;
}
function get_appoinment_customer_booking($id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'amelia_customer_bookings';
    $results = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE appointmentId = %d", $id));
    return $results;
}
function get_appoinment_service_details($id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'amelia_services';
    $results = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
    return $results;
}
function get_all_services()
{
    global $wpdb;
    $table = $wpdb->prefix . 'amelia_services';

    $results = $wpdb->get_results("SELECT * FROM " . $table . " ");
    return $results;
}
function get_all_customers()
{
    global $wpdb;
    $table = $wpdb->prefix . 'amelia_users';

    $results = $wpdb->get_results("SELECT id, firstName, lastName, email FROM " . $table . " WHERE type = 'customer' ORDER BY firstName, lastName");
    return $results;
}
function get_appoinment_body_chart($id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'amelia_body_chart';
    $results = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE appoinment_id = %d", $id));
    return $results;
}
function get_appoinment_booking_details($id)
{
    global $wpdb;
    $customer_booking_table = $wpdb->prefix . 'amelia_customer_bookings';
    $customer_table = $wpdb->prefix . 'amelia_users';
    $table = $wpdb->prefix . 'amelia_appointments';

    $results = $wpdb->get_row($wpdb->prepare(
        "SELECT a.id as appointment_id,a.serviceId,c.firstName,c.lastName,c.email,c.phone,c.gender,b.customFields as question_answer,b.info FROM {$table} a left join {$customer_booking_table} b ON (a.id=b.appointmentId) left join {$customer_table} c ON (b.customerId=c.id) WHERE a.id = %d",
        $id
    ), ARRAY_A);
    return $results;
}
function get_amelia_customer_id_by_Email($email)
{
    global $wpdb;
    $table = $wpdb->prefix . 'amelia_users';

    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE email = %s", $email));
    if (!empty($result)) {
        return $result->id;
    } else {
        return false;
    }
}
function get_appoinment_booking_details_service_wise_latest($service_id, $customerid = '')
{
    global $wpdb;
    $user_info = get_userdata($customerid);
    $ameliaCustomerId = get_amelia_customer_id_by_Email($user_info->user_email);
    $customer_booking_table = $wpdb->prefix . 'amelia_customer_bookings';
    $customer_table = $wpdb->prefix . 'amelia_users';
    $table = $wpdb->prefix . 'amelia_appointments';
    $sql = "SELECT b.customFields as question_answer,b.customerId FROM {$table} a left join {$customer_booking_table} b ON (a.id=b.appointmentId) WHERE a.serviceId = %d";
    $params = array($service_id);

    if ($customerid) {
        $sql .= ' AND b.customerId=%d';
        $params[] = $ameliaCustomerId;
    }
    $sql .= ' order by a.id desc limit 1';
    $results = $wpdb->get_row($wpdb->prepare($sql, $params), ARRAY_A);
    if (empty($results)) {
        return $results['question_answer'] = array();
    } else {
        $customerDetails = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$customer_table} WHERE id = %d", $results['customerId']));
        $results['first_name'] = $customerDetails->firstName;
        $results['last_name'] = $customerDetails->lastName;
        $results['phone'] = $customerDetails->phone;
        return $results;
    }
}
function get_appoinment_booking_details_service_wise($service_id, $customerid = '')
{
    global $wpdb;
    $customer_booking_table = $wpdb->prefix . 'amelia_customer_bookings';
    $customer_table = $wpdb->prefix . 'amelia_users';
    $table = $wpdb->prefix . 'amelia_appointments';
    $sql = "SELECT a.id as appointment_id,a.serviceId,c.firstName,c.lastName,c.email,c.id as customer_id,c.phone,c.gender,b.customFields as question_answer,b.info FROM {$table} a left join {$customer_booking_table} b ON (a.id=b.appointmentId) left join {$customer_table} c ON (b.customerId=c.id) WHERE a.serviceId = %d";
    $params = array($service_id);

    if ($customerid) {
        $sql .= ' AND c.id=%d';
        $params[] = $customerid;
    }
    $results = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    return $results;
}
add_action('wp_ajax_get_appointment_ref', 'get_appointment_ref');
add_action('wp_ajax_nopriv_get_appointment_ref', 'get_appointment_ref');
function get_appointment_ref()
{
    $id = isset($_GET['id']) ? $_GET['id'] : '';
    $body_chart = get_appoinment_body_chart($id);
    $body_chart_data = json_decode($body_chart->data, true);
    $data = array();

    if ($body_chart_data['referal']) {
        if ($body_chart_data['referal'] == 'No') {
            $data['referral'] = 'No';
        } else if ($body_chart_data['referal'] == 'Info Only') {
            $data['referral'] = 'Info';
        } else {

            $data['referral'] = 'Yes';
        }
    } else {
        $data['referral'] = $body_chart_data['referal'];
    }

    $data['attend'] = $body_chart_data['attend'];
    echo json_encode($data);
    exit;
}

add_action('wp_ajax_load_appointment_modal_content', 'load_appointment_modal_content');
add_action('wp_ajax_nopriv_load_appointment_modal_content', 'load_appointment_modal_content');

function load_appointment_modal_content()
{
    ob_start();
    $id = $_GET['id'];

    $infoappointment = get_appoinment_booking_details($id);
    $info = isset($infoappointment['info']) ? json_decode($infoappointment['info'], true) : array();

    $fname = isset($info['firstName']) ? $info['firstName'] : $infoappointment['firstName'];
    $lname = isset($info['lastName']) ? $info['lastName'] : $infoappointment['lastName'];
    $phone = isset($info['phone']) ? $info['phone'] : $infoappointment['phone'];
    $email = isset($infoappointment['email']) ? $infoappointment['email'] : '';
    $gender = isset($infoappointment['gender']) ? $infoappointment['gender'] : '';

    $serviceid = isset($infoappointment['serviceId']) ? $infoappointment['serviceId'] : '';
    $question_answer = isset($infoappointment['question_answer']) ? json_decode($infoappointment['question_answer'], true) : array();
    $serviceinfo = get_appoinment_service_details($serviceid);
?>
    <div
        class="pdfHtml modal fade"
        id="customerInfoPopup"
        tabindex="-1"
        aria-labelledby="customerInfoPopupLabel"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="d-flex justify-contetn-space-between" style="justify-content: space-between;padding: 10px;">
                    <p></p>

                    <button
                        type="button"
                        class="btn-close popClose removefrompdf"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="formInput" style="padding:20px;">
                    <input type="text" name="report_email" placeholder="PLease add emails with comma separated" value="<?php echo $email; ?>" id="report_to_email">
                    <input type="hidden" name="name" value="<?php if ($fname || $lname) echo $fname . ' ' . $lname;
                                                            else echo 'Member'; ?>">
                    <button type="button" class="btn btn-primary" id="send_popup_report">Send Report</button>
                    <button class="btn btn-primary removefrompdf" id="pdf_generator_popup" type="button">Pdf Generate</button>
                </div>


                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="customerInfoPopupLabel">
                        Appointment Information
                    </h1>

                </div>
                <div class="modal-body pdf-capture">

                    <img class="w-100" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/page-3-1.jpg" alt="" />
                    <div class="popContent table-responsive">
                        <h2 class="section_heading">Basic Information</h2>
                        <table class="table table-striped table-bordered">
                            <tbody>
                                <?php if ($fname) { ?>
                                    <tr>
                                        <td>Customer Name:</td>
                                        <td><?php echo $fname . ' ' . $lname; ?></td>
                                    </tr>
                                <?php } ?>

                                <?php if ($email) { ?>
                                    <tr>
                                        <td>Customer Email:</td>
                                        <td><?php echo $email; ?></td>
                                    </tr>
                                <?php } ?>
                                <?php if ($phone) { ?>
                                    <tr>
                                        <td>Customer Phone:</td>
                                        <td><?php echo $phone; ?></td>
                                    </tr>
                                <?php } ?>
                                <?php if ($gender) { ?>
                                    <tr>
                                        <td>Gender</td>
                                        <td><?php echo $gender; ?></td>
                                    </tr>
                                <?php } ?>



                            </tbody>
                        </table>
                    </div>
                    <!--  service-->
                    <div class="popContent table-responsive">
                        <h2 class="section_heading">Customer Details</h2>
                        <table class="table table-striped table-bordered">
                            <tbody>
                                <tr>
                                    <td>Service</td>
                                    <td><?php echo $serviceinfo->name; ?></td>
                                </tr>
                                <?php if (!empty($question_answer)) {
                                    foreach ($question_answer as $answer) {
                                        if (is_array($answer['value'])) {
                                            $answer_value = implode(', ', array_map('htmlspecialchars', $answer['value']));
                                        } elseif (is_string($answer['value'])) {
                                            // If it's a string, print it directly
                                            $answer_value = ($answer['value']);
                                        } else {
                                            // If it's a string, print it directly
                                            $answer_value = $answer['value'];
                                        }
                                ?>
                                        <tr>
                                            <td><?php echo $answer['label']; ?></td>
                                            <td><?php echo  $answer_value; ?></td>
                                        </tr>
                                <?php }
                                } ?>


                            </tbody>
                        </table>
                    </div>
                    <!--//question-->
                    <img class="w-100" style="" src="<?php echo WPAMELIA_ADDON_PLUGIN_URL ?>/admin/images/page-9-2.jpg" alt="" />
                </div>

            </div>
        </div>

    </div>
<?php
    echo ob_get_clean();
    wp_die();
}
add_action('wp_ajax_save_body_chart_action', 'save_body_chart_action');
add_action('wp_ajax_nopriv_save_body_chart_action', 'save_body_chart_action');
function save_body_chart_action()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        echo 'Invalid nonce';
        exit;
    }
    if (isset($_POST['formdata'])) {


        $appointmentid = $_POST['appoinment_id'];
        $data['formdata'] = $_POST['formdata'];
        $formdata = $_POST['formdata'];
        $cleanedString = str_replace('%23', '', $formdata);
        $cleanedString = str_replace('#', '', $cleanedString);
        $cleanedString = wp_unslash(stripslashes($cleanedString));
        $parsed_data = parse_url($cleanedString);
        parse_str($parsed_data['path'], $query_params);
        if ($query_params['mark_positions']) {
        }

        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'amelia_body_chart', ['appoinment_id' => $appointmentid]);
        $wpdb->insert($wpdb->prefix . 'amelia_body_chart', array(
            'appoinment_id'             => $appointmentid,
            'user_id'             => (int)get_current_user_id(),
            'data'             => json_encode($query_params, JSON_UNESCAPED_UNICODE)


        ));
        echo 'Successfully Saved';
        exit;
    }
}
add_action('wp_ajax_save_program_meterials_action', 'save_program_meterials_action');
add_action('wp_ajax_nopriv_save_program_meterials_action', 'save_program_meterials_action');
function save_program_meterials_action()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        echo 'Invalid nonce';
        exit;
    }
    if (isset($_POST['formdata'])) {

        global $wpdb;
        $service_id = $_POST['service_id'];


        $data['formdata'] = $_POST['formdata'];
        $formdata = $_POST['formdata'];
        $cleanedString = str_replace('%23', '', $formdata);
        $cleanedString = str_replace('#', '', $cleanedString);
        $cleanedString = wp_unslash(stripslashes($cleanedString));
        $parsed_data = parse_url($cleanedString);
        parse_str($parsed_data['path'], $query_params);

        if ($query_params['logo_url'] && $service_id) {
            update_post_meta($service_id, 'service_logo', $query_params['logo_url']);
        }
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'amelia_program_meterials', ['service_id' => $service_id]);
        $wpdb->insert($wpdb->prefix . 'amelia_program_meterials', array(
            'service_id'             => $service_id,
            'data'             => json_encode($query_params, JSON_UNESCAPED_UNICODE)


        ));
        echo 'Successfully Saved';
        exit;
    }
}
add_action('wp_ajax_get_service_meterials', 'get_service_meterials');
add_action('wp_ajax_nopriv_get_service_meterials', 'get_service_meterials');
function get_service_meterials($service_id)
{
    global $wpdb;
    $service = $_POST['service'];
    $table = $wpdb->prefix . 'amelia_service_chart';

    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE service_id = %d", $service));
    $service_json_decode = json_decode($result->data, true);

    $table = $wpdb->prefix . 'amelia_program_meterials';
    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE service_id = %d", $service));
    $json_decode = json_decode($result->data, true);
    if (get_post_meta($service, 'service_logo', true)) {
        $data['logo_url'] = get_post_meta($service, 'service_logo', true);
    } else {
        $data['logo_url'] = '';
    }


    if (!empty($json_decode)) {
        $data['selected_dates'] = ($json_decode['selected_dates']) ? $json_decode['selected_dates'] : '';
        $data['selected_dates_2'] = ($json_decode['selected_dates_2']) ? $json_decode['selected_dates_2'] : '';
        $data['selected_dates_3'] = ($json_decode['selected_dates_3']) ? $json_decode['selected_dates_3'] : '';
        $data['daily_rate'] = isset($json_decode['daily_rate']) ? $json_decode['daily_rate'] : '';
        $data['qr_code_url'] = $json_decode['qr_code_url'];
    } else {
        $data['selected_dates'] = '';
        $data['selected_dates_2'] = '';
        $data['selected_dates_3'] = '';
        $data['daily_rate'] = '$1500+GST';
        $data['qr_code_url'] = '';
    }


    echo json_encode($data);
    exit;
}
add_action('wp_ajax_save_body_chart_ref_action', 'save_body_chart_ref_action');
add_action('wp_ajax_nopriv_save_body_chart_ref_action', 'save_body_chart_ref_action');
function save_body_chart_ref_action()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        echo 'Invalid nonce';
        exit;
    }
    if (isset($_POST['formdata'])) {


        $appointmentid = $_POST['appoinment_id'];
        $data['formdata'] = $_POST['formdata'];
        $formdata = $_POST['formdata'];
        $cleanedString = str_replace('%23', '', $formdata);
        $cleanedString = str_replace('#', '', $cleanedString);
        $cleanedString = wp_unslash(stripslashes($cleanedString));
        $parsed_data = parse_url($cleanedString);
        parse_str($parsed_data['path'], $query_params);
        if ($query_params['mark_positions']) {
        }

        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'amelia_body_chart_ref', ['appointment_id' => $appointmentid]);
        $signature = $query_params['uploaded_sig_url'];
        if ($signature) {
            update_user_meta((int)get_current_user_id(), 'signature_img', $signature);
        }

        $wpdb->insert($wpdb->prefix . 'amelia_body_chart_ref', array(
            'appointment_id'             => $appointmentid,
            'user_id'             => (int)get_current_user_id(),
            'data'             => json_encode($query_params, JSON_UNESCAPED_UNICODE)


        ));
        echo 'Successfully Saved';
        exit;
    }
}
function get_appoinment_body_chart_ref($id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'amelia_body_chart_ref';

    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE appointment_id = %d", $id));
    return $result;
}
function get_service_custom_data($service_id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'amelia_service_chart';

    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE service_id = %d", $service_id));
    $json_decode = json_decode($result->data, true);
    if (!empty($json_decode)) {
        $data['file'] = (get_post_meta($service_id, 'service_logo', true)) ? get_post_meta($service_id, 'service_logo', true) : 'https://placehold.co/300x300';
        $data['email'] = $json_decode['email'];
        $data['contact_name'] = $json_decode['contact_name'];
        $data['phone'] = $json_decode['phone'];
        $data['details'] = $json_decode['details'];
        $data['chat_gpt_sumarry'] = $json_decode['chat_gpt_sumarry'];
    } else {
        $data['file'] = 'https://placehold.co/300x300';
        $data['email'] = '';
        $data['contact_name'] = '';
        $data['phone'] = '';
        $data['details'] = '';
        $data['chat_gpt_sumarry'] = '';
    }


    return $data;
}
add_action('wp_ajax_save_service_chart_action', 'save_service_chart_action');
add_action('wp_ajax_nopriv_save_service_chart_action', 'save_service_chart_action');
function save_service_chart_action()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        echo 'Invalid nonce';
        exit;
    }
    if (isset($_POST['formdata'])) {


        $service_id = $_POST['service_id'];
        $data['formdata'] = $_POST['formdata'];
        $formdata = $_POST['formdata'];
        $cleanedString = str_replace('%23', '', $formdata);
        $cleanedString = str_replace('#', '', $cleanedString);
        $cleanedString = wp_unslash(stripslashes($cleanedString));
        $parsed_data = parse_url($cleanedString);
        parse_str($parsed_data['path'], $query_params);

        update_post_meta($service_id, 'service_logo', $query_params['uploaded_file_url']);
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'amelia_service_chart', ['service_id' => $service_id]);
        $wpdb->insert($wpdb->prefix . 'amelia_service_chart', array(
            'service_id'             => $service_id,
            'data'             => json_encode($query_params)


        ));
        echo 'Successfully Saved';
        exit;

    }
}

add_action('wp_ajax_fetch_service_qa_chart_data', 'get_chart_data');
add_action('wp_ajax_nopriv_fetch_service_qa_chart_data', 'get_chart_data');

add_action('wp_ajax_get_skin_screening_chart', 'get_skin_screening_chart');
add_action('wp_ajax_nopriv_get_skin_screening_chart', 'get_skin_screening_chart');
add_action('wp_ajax_get_combined_group_report', 'get_combined_group_report');
add_action('wp_ajax_nopriv_get_combined_group_report', 'get_combined_group_report');
add_action('wp_ajax_save_combined_report', 'save_combined_report');
add_action('wp_ajax_get_saved_combined_reports', 'get_saved_combined_reports');
add_action('wp_ajax_delete_saved_combined_report', 'delete_saved_combined_report');
add_action('admin_init', 'download_saved_combined_report');

/**
 * Build chart and summary data for one or more services.
 *
 * @param array       $serviceIds      List of Amelia service IDs.
 * @param string      $customer        Optional customer filter.
 * @param string|null $existingSummary Summary to reuse instead of regenerating.
 *
 * @return array
 */
function build_skin_screening_chart_data($serviceIds = array(), $customer = '', $existingSummary = '', $selectedCustomers = array())
{
    $chartdata = array(
        'body_report' => array(),
        'agedata' => array(),
        'ppe' => array(),
        'referal' => array(),
        'cancerHistorydata' => array(),
        'outdoordata' => array(),
        'sunscreen' => array(),
        'spent_time_outside_at_home' => array(),
        'spent_time_outside_at_work' => array(),
        'totalparticipent' => 0,
        'totalattended' => 0,
        'chatGptSumarry' => '',
    );

    if (empty($serviceIds) || !is_array($serviceIds)) {
        return $chartdata;
    }

    $questionStats = array();
    $patientsData = array();
    $totalparticipent = 0;
    $totalattended = 0;
    $headnick = 0;
    $limbs = 0;
    $torso_front = 0;
    $torso_back = 0;

    $targetLabels = array(
        "What is your age bracket?",
        "Have you ever been diagnosed with any form of skin cancer in the past?",
        "Are you an outdoor worker?",
        "How many hours per week do you spend outside when at work?",
        "How many hours per week do you spend outside recreationally?",
        "IS WORKPLACE PPE SUFFICIENT?",
        "Do you wear sunscreen?"
    );

    foreach ($serviceIds as $serviceId) {
        $serviceId = intval($serviceId);
        if (!$serviceId) {
            continue;
        }

        $serviceAppointments = get_appoinment_booking_details_service_wise($serviceId, $customer);
        if (empty($serviceAppointments)) {
            continue;
        }

        // Filter by selected customers if provided
        if (!empty($selectedCustomers) && is_array($selectedCustomers)) {
            $selectedCustomers = array_map('intval', $selectedCustomers);
            $serviceAppointments = array_filter($serviceAppointments, function ($appointment) use ($selectedCustomers) {
                return isset($appointment['customer_id']) && in_array(intval($appointment['customer_id']), $selectedCustomers);
            });
        }

        foreach ($serviceAppointments as $infoappointment) {
            $totalparticipent++;

            $question_answer = isset($infoappointment['question_answer']) ? json_decode($infoappointment['question_answer'], true) : array();

            $body_chart = get_appoinment_body_chart($infoappointment['appointment_id']);
            if (!empty($body_chart)) {
                $json_decode = json_decode($body_chart->data, true);

                if (isset($json_decode['ppe'])) {
                    $ppevalue = $json_decode['ppe'];
                    if (!isset($questionStats['ppe'][$ppevalue])) {
                        $questionStats['ppe'][$ppevalue] = 0;
                    }
                    $questionStats['ppe'][$ppevalue]++;
                }

                if (isset($json_decode['referal'])) {
                    $rvalue2 = $json_decode['referal'];
                    if (!isset($questionStats['referal'][$rvalue2])) {
                        $questionStats['referal'][$rvalue2] = 0;
                    }
                    $questionStats['referal'][$rvalue2]++;
                }

                if (isset($json_decode['attend']) && $json_decode['attend'] && $json_decode['attend'] === 'Yes') {
                    $totalattended++;
                }

                if (isset($json_decode['mark_positions'])) {
                    $targetLabelsbody = $json_decode["mark_positions"];
                    $mark_positions = is_array($targetLabelsbody) ? $targetLabelsbody : json_decode($targetLabelsbody, true);

                    $face1Canvas = isset($mark_positions['face1Canvas']) ? $mark_positions['face1Canvas'] : array();
                    $face2Canvas = isset($mark_positions['face2Canvas']) ? $mark_positions['face2Canvas'] : array();
                    $backCanvas = isset($mark_positions['backCanvas']) ? $mark_positions['backCanvas'] : array();
                    $frontCanvas = isset($mark_positions['frontCanvas']) ? $mark_positions['frontCanvas'] : array();

                    if (!empty($face2Canvas) || !empty($face1Canvas)) {
                        $headnick += 1;
                    }

                    if (!empty($frontCanvas)) {
                        $headnickfromfront = array_filter($frontCanvas, function ($item) {
                            return isset($item['y']) && $item['y'] >= 0 && $item['y'] <= 85;
                        });
                        if (!empty($headnickfromfront)) {
                            $headnick += 1;
                        }

                        $torsofromfront = array_filter($frontCanvas, function ($item) {
                            return isset($item['x']) && $item['x'] >= 85 && $item['x'] <= 205;
                        });
                        $torsofromfront2 = array_filter($frontCanvas, function ($item) {
                            return isset($item['y']) && $item['y'] >= 120 && $item['y'] <= 250;
                        });
                        if (!empty($torsofromfront) && !empty($torsofromfront2)) {
                            $torso_front += 1;
                        }

                        $limbsfromfront = array_filter($frontCanvas, function ($item) {
                            return isset($item['x']) && $item['x'] >= 15 && $item['x'] <= 84;
                        });
                        $limbsfromfront2 = array_filter($frontCanvas, function ($item) {
                            return isset($item['y']) && $item['y'] >= 280 && $item['y'] <= 600;
                        });
                        if (!empty($limbsfromfront2) || !empty($limbsfromfront)) {
                            $limbs += 1;
                        }
                    }

                    if (!empty($backCanvas)) {
                        $headnickfromback = array_filter($backCanvas, function ($item) {
                            return isset($item['y']) && $item['y'] >= 0 && $item['y'] <= 85;
                        });
                        if (!empty($headnickfromback)) {
                            $headnick += 1;
                        }

                        $limbsfromback2 = array_filter($backCanvas, function ($item) {
                            return isset($item['x']) && $item['x'] >= 210 && $item['x'] <= 266;
                        });
                        $limbsfromback = array_filter($backCanvas, function ($item) {
                            return isset($item['y']) && $item['y'] >= 267 && $item['y'] <= 600;
                        });

                        $torsofromback = array_filter($backCanvas, function ($item) {
                            return isset($item['x']) && $item['x'] >= 90 && $item['x'] <= 205;
                        });
                        $torsofromback2 = array_filter($backCanvas, function ($item) {
                            return isset($item['y']) && $item['y'] >= 98 && $item['y'] <= 245;
                        });
                        if (!empty($torsofromback) && !empty($torsofromback2)) {
                            $torso_back += 1;
                        }
                        if (!empty($limbsfromback2) || !empty($limbsfromback)) {
                            $limbs += 1;
                        }
                    }
                }
            }

            $filteredData = array_filter($question_answer, function ($item) use ($targetLabels) {
                return in_array($item['label'], $targetLabels);
            });

            $quensanswer = array();
            foreach ($filteredData as $answerv) {
                $label = trim($answerv['label']);
                if (is_array($answerv['value'])) {
                    $answer = implode(', ', array_map('sanitize_text_field', $answerv['value']));
                } elseif (is_string($answerv['value'])) {
                    $answer = sanitize_text_field($answerv['value']);
                } else {
                    $answer = sanitize_text_field((string) $answerv['value']);
                }

                $quensanswer[$label] = $answer;
                if (!isset($questionStats[$label][$answer])) {
                    $questionStats[$label][$answer] = 0;
                }
                $questionStats[$label][$answer]++;
            }

            if (!empty($quensanswer)) {
                $patientsData[] = $quensanswer;
            }
        }
    }

    $chartdata['totalparticipent'] = $totalparticipent;
    $chartdata['totalattended'] = $totalattended;

    $chartdata['body_report'] = array(
        'Head & Neck' => $headnick,
        'Limbs' => $limbs,
        'Torso Back' => $torso_back,
        'Torso Font' => $torso_front
    );

    $agedatalabel = array('19-24', '25-40', '41-56', '57-75', '75+');
    $cagedataresult = isset($questionStats['What is your age bracket?']) ? $questionStats['What is your age bracket?'] : array();
    foreach ($agedatalabel as $agev) {
        $chartdata['agedata'][$agev] = isset($cagedataresult[$agev]) ? $cagedataresult[$agev] : '0';
    }

    $ppelabel = array('Yes', 'No');
    $ppedataresult = isset($questionStats['ppe']) ? $questionStats['ppe'] : array();
    foreach ($ppelabel as $pppv) {
        $chartdata['ppe'][$pppv] = isset($ppedataresult[$pppv]) ? $ppedataresult[$pppv] : 0;
    }

    $referallabel = array('Yes with One month timeline', 'Yes with immediate timeline');
    $refdataresult = isset($questionStats['referal']) ? $questionStats['referal'] : array();
    foreach ($referallabel as $rfv) {
        $chartdata['referal'][$rfv] = isset($refdataresult[$rfv]) ? $refdataresult[$rfv] : 0;
    }
    $chartdata['referal']['month'] = isset($refdataresult['Yes with One month timeline']) ? $refdataresult['Yes with One month timeline'] : 0;
    $chartdata['referal']['imidiate'] = isset($refdataresult['Yes with immediate timeline']) ? $refdataresult['Yes with immediate timeline'] : 0;

    $chartdata['cancerHistorydata'] = isset($questionStats['Have you ever been diagnosed with any form of skin cancer in the past?']) ? $questionStats['Have you ever been diagnosed with any form of skin cancer in the past?'] : array();
    $chartdata['outdoordata'] = isset($questionStats['Are you an outdoor worker?']) ? $questionStats['Are you an outdoor worker?'] : array();
    $chartdata['sunscreen'] = isset($questionStats['Do you wear sunscreen?']) ? $questionStats['Do you wear sunscreen?'] : array();

    $spentlabel = array('1-5', '5-10', '10-15', '15-20', '20+');
    $spent1 = isset($questionStats['How many hours per week do you spend outside recreationally?']) ? $questionStats['How many hours per week do you spend outside recreationally?'] : array();
    $spent2 = isset($questionStats['How many hours per week do you spend outside when at work?']) ? $questionStats['How many hours per week do you spend outside when at work?'] : array();
    foreach ($spentlabel as $slabel) {
        $chartdata['spent_time_outside_at_home'][$slabel] = isset($spent1[$slabel]) ? $spent1[$slabel] : '0';
        $chartdata['spent_time_outside_at_work'][$slabel] = isset($spent2[$slabel]) ? $spent2[$slabel] : '0';
    }

    if (!empty($patientsData)) {
        if (!empty($existingSummary)) {
            $chartdata['chatGptSumarry'] = $existingSummary;
        } else {
            $summary = generateSkinCancerSummary($patientsData);
            $chartdata['chatGptSumarry'] = $summary ? $summary : '';
        }
    }

    return $chartdata;
}

/**
 * AJAX handler to build a combined report across multiple services.
 */
function get_combined_group_report()
{
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce provided.', 403);
    }

    $services = isset($_POST['services']) ? (array) $_POST['services'] : array();
    $services = array_filter(array_map('intval', $services));

    if (empty($services)) {
        wp_send_json_error('Please select at least one service.');
    }

    $selectedCustomers = isset($_POST['customers']) ? (array) $_POST['customers'] : array();
    $selectedCustomers = array_filter(array_map('intval', $selectedCustomers));

    $chartdata = build_skin_screening_chart_data($services, '', '', $selectedCustomers);

    if (empty($chartdata) || (!$chartdata['totalparticipent'] && !$chartdata['totalattended'])) {
        wp_send_json_error('No data found for the selected services.');
    }

    wp_send_json_success($chartdata);
}

function get_skin_screening_chart()
{
    global $wpdb;
    $service = isset($_POST['service']) ? sanitize_text_field($_POST['service']) : '';
    $customer = isset($_POST['customer']) ? sanitize_text_field($_POST['customer']) : '';

    $chartdata = array();
    if ($service) {
        $service_meta = get_service_custom_data($service);
        $chartdata = build_skin_screening_chart_data(array($service), $customer, isset($service_meta['chat_gpt_sumarry']) ? $service_meta['chat_gpt_sumarry'] : '');
        $chartdata['service_save_data'] = $service_meta;
    }

    echo wp_json_encode($chartdata);
    exit;
}
function generateSkinCancerSummary($patientsData)
{
    // $apiKey = "sk-proj-9a9Mi79ojbe6zGRy94WkT3BlbkFJ66Arw2B9KfB7DKFcAIgE"; 
    $apiKey = get_option('chatGpt_api_key', '');
    //$apiKey = "sk-proj-w4HDBg5iFMjtSG6G655jtDKtzioT6_6V1"; //
    if (!$apiKey || empty($patientsData)) {
        return false;
    }
    $url = "https://api.openai.com/v1/chat/completions";

    // Prepare the prompt
    // $messages = [
    //     ["role" => "system", "content" => "You are a medical assistant analyzing data from multiple patients who have filled out a form regarding skin cancer concerns. Based on the aggregated responses, provide a summary of common concerns, trends, and recommendations for improvement or further action."],
    //     ["role" => "user", "content" => "Here are the responses from all patients regarding skin cancer. Please analyze and provide a combined summary."]
    // ];
    if (get_option('chatGpt_api_role_user_prompt', '') && get_option('chatGpt_api_role_system_prompt', '')) {
        $messages = [
            [
                "role" => "system",
                "content" => get_option('chatGpt_api_role_system_prompt', '')
            ],
            [
                "role" => "user",
                "content" => get_option('chatGpt_api_role_user_prompt', '')
            ]
        ];
    } else {
        $messages = [
            [
                "role" => "system",
                "content" => "You are a medical assistant analyzing patient responses about skin cancer. Write the summary in clean plain text ONLY — no Markdown (#, **), no HTML (<br>, <p>). Use simple numbering and hyphens for structure. Sections should be labeled clearly as: Key Findings, Trends, Recommendations."
            ],
            [
                "role" => "user",
                "content" => "Here are the patient responses. Please analyze them and provide the combined summary."
            ]
        ];
    }

    // Add aggregated patient data
    foreach ($patientsData as $questionsAndAnswers) {
        foreach ($questionsAndAnswers as $question => $answer) {
            $messages[] = ["role" => "user", "content" => "Q: $question\nA: $answer"];
        }
    }

    // API request payload
    $postData = [
        "model" => "gpt-4o",
        "messages" => $messages,
        "temperature" => 0.7
    ];

    $headers = [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ];

    // cURL initialization
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute the request
    $response = curl_exec($ch);
    curl_close($ch);

    // Parse and return the response
    $responseData = json_decode($response, true);
    if (isset($responseData['choices'][0]['message']['content'])) {
        $outputprepare = preg_replace('/[#`\/]+/', '', $responseData['choices'][0]['message']['content']);
        return $outputprepare;
    } else {
        return "Error: Unable to generate summary.";
    }
}
function get_chart_data()
{
    global $wpdb;

    $service = isset($_POST['service']) ? sanitize_text_field($_POST['service']) : '';
    $customer = isset($_POST['customer']) ? sanitize_text_field($_POST['customer']) : '';

    $getserviceQuestionAns = get_appoinment_booking_details_service_wise($service, $customer);
    $customeroption = '<option value="0">Select A Customer</option>';
    $chartData = [];
    $questionIndex = 1;
    $addedcus = array();
    $totalresponse = 0;
    if (!empty($getserviceQuestionAns)) {
        foreach ($getserviceQuestionAns as $infoappointment) {
            $totalresponse++;
            $info = isset($infoappointment['info']) ? json_decode($infoappointment['info'], true) : array();

            $fname = isset($info['firstName']) ? $info['firstName'] : $infoappointment['firstName'];
            $lname = isset($info['lastName']) ? $info['lastName'] : $infoappointment['lastName'];

            if (!in_array($infoappointment['customer_id'], $addedcus)) {
                $customeroption .= '<option value="' . $infoappointment['customer_id'] . '">' . $fname . ' ' . $lname . '</option>';
                $addedcus[] = $infoappointment['customer_id'];
            }

            $serviceid = isset($infoappointment['serviceId']) ? $infoappointment['serviceId'] : '';
            $question_answer = isset($infoappointment['question_answer']) ? json_decode($infoappointment['question_answer'], true) : array();
            $appointmentId = $entry['appointment_id'];
            $userNames[$appointmentId] = $entry['firstName'] . ' ' . $entry['lastName'];

            $answers = $question_answer;

            foreach ($answers as $questionId => $answer) {
                if (!isset($chartData['questions'][$questionId])) {
                    $shortLabels[$questionId] = "Q" . $questionIndex;
                    $chartData['questions'][$questionId] = [
                        'id' => $questionId,
                        'short_label' => $shortLabels[$questionId],
                        'label' => $answer['label']
                    ];
                    $questionIndex++;
                }

                $chartData['answers'][] = [
                    'question_id' => $questionId,
                    'answer' => $answer['value'],
                    'patient' => $fname . ' ' . $lname
                ];
            }
        }

        $chartData['questions'] = array_values($chartData['questions']);
        $chartData['customer_option'] = $customeroption;
        $chartData['totalresponse'] = $totalresponse;
        wp_send_json_success($chartData);
        exit;
    }
}
add_action('wp_ajax_pdf_sent_user', 'pdf_sent_user');
add_action('wp_ajax_nopriv_pdf_sent_user', 'pdf_sent_user');
add_action('wp_ajax_popup_pdf_sent_user', 'popup_pdf_sent_user');
add_action('wp_ajax_nopriv_popup_pdf_sent_user', 'popup_pdf_sent_user');
add_action('wp_ajax_service_pdf_sent_user', 'service_pdf_sent_user');
add_action('wp_ajax_nopriv_service_pdf_sent_user', 'service_pdf_sent_user');
add_action('wp_ajax_meterials_pdf_sent_user', 'meterials_pdf_sent_user');
add_action('wp_ajax_nopriv_meterials_pdf_sent_user', 'meterials_pdf_sent_user');
add_action('wp_ajax_ref_pdf_sent_user', 'ref_pdf_sent_user');
add_action('wp_ajax_nopriv_ref_pdf_sent_user', 'ref_pdf_sent_user');

/**
 * Shared helper for all PDF email sending AJAX handlers.
 *
 * @param string $option_key  WordPress option key for the email body template.
 * @param string $email_type  Type identifier passed to sendEmailWithPdf().
 * @param string $name        Recipient display name for the email template.
 */
function _wpamelia_send_pdf_report($option_key, $email_type, $name)
{
    if (!isset($_FILES['pdf'])) {
        echo 'Something went wrong';
        exit;
    }

    $pdf = $_FILES['pdf'];
    $toemail = $_POST['toemail'];

    $uploadDirectory = __DIR__ . '/pdfs/';
    if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0777, true);
    }

    $placeholders = ['[name]' => $name];
    $bodycontent = get_option($option_key, '');
    $bodycontent = wpautop(strtr($bodycontent, $placeholders));

    if ($toemail) {
        echo sendEmailWithPdf($toemail, $bodycontent, $pdf['tmp_name'], $email_type);
    } else {
        echo 'Email not found!';
    }
    exit;
}

/**
 * Resolve the recipient name from an appointment ID, with POST override.
 */
function _wpamelia_resolve_appointment_name()
{
    $name = 'Member';
    if (!empty($_POST['appointment_id'])) {
        $infoappointment = get_appoinment_booking_details($_POST['appointment_id']);
        $info = isset($infoappointment['info']) ? json_decode($infoappointment['info'], true) : array();
        $name = isset($info['firstName']) ? $info['firstName'] . ' ' . $info['lastName'] : $infoappointment['firstName'] . ' ' . $infoappointment['lastName'];
    }
    if (!empty($_POST['name'])) {
        $name = $_POST['name'];
    }
    return $name;
}

function pdf_sent_user()
{
    $name = _wpamelia_resolve_appointment_name();
    _wpamelia_send_pdf_report('email_body_content', 'body_chart', $name);
}

function popup_pdf_sent_user()
{
    $name = _wpamelia_resolve_appointment_name();
    _wpamelia_send_pdf_report('ind_email_body_content', 'individual', $name);
}

function service_pdf_sent_user()
{
    $name = !empty($_POST['name']) ? $_POST['name'] : 'Member';
    _wpamelia_send_pdf_report('group_email_body_content', 'group', $name);
}

function meterials_pdf_sent_user()
{
    $name = !empty($_POST['name']) ? $_POST['name'] : 'Member';
    _wpamelia_send_pdf_report('group_email_body_content_meterials', 'materials', $name);
}

function ref_pdf_sent_user()
{
    _wpamelia_send_pdf_report('ref_email_body_content', 'referal', 'Member');
}
function resize_uploaded_image()
{
    $image_url = $_POST['image_url'];
    $max_width = (int)$_POST['max_width'];
    $max_height = (int)$_POST['max_height'];

    $file_path = str_replace(get_site_url(), ABSPATH, $image_url); // Convert URL to server path

    $editor = wp_get_image_editor($file_path);
    if (!is_wp_error($editor)) {
        $editor->resize($max_width, $max_height, true);
        $editor->save($file_path);
    }

    echo $image_url; // Return new image URL
    wp_die();
}
add_action('wp_ajax_resize_uploaded_image', 'resize_uploaded_image');
//dateformat

function get_dropdown_with_field_map($fieldval, $placeholder, $populate_field_name, $selected)
{
    $html = '<div class="removefrompdf">
    <select class="populate_description_field select2drop" name="populate_' . $populate_field_name . '" data-populate="' . $populate_field_name . '" multiple>
    <option value="0" disabled>' . $placeholder . '</option>' . get_names_by_field($fieldval, $selected);
    $html .= '</select></div>';
    return $html;
}
function get_names_by_field($field_value, $selected)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'amelia_report_comments';

    $like = '%,' . $wpdb->esc_like($field_value) . ',%';

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name FROM $table_name WHERE CONCAT(',', fields, ',') LIKE %s",
        $like
    ));

    $options = '';

    foreach ($results as $row) {
        if ($selected == $row->id) {
            $options .= '<option value="' . $row->id . '" >' . $row->name . '</option>';
        } else {
            $options .= '<option value="' . $row->id . '" >' . $row->name . '</option>';
        }
    }

    return $options;
}
add_action('wp_ajax_fetch_description_by_id', function () {
    global $wpdb;
    $table_name = $wpdb->prefix . 'amelia_report_comments';
    //print_r($_POST);
    $ids = ($_POST['id']);
    $descriptiontext = '';
    if (!empty($ids)) {
        foreach ($ids as $id) {
            $description = $wpdb->get_var($wpdb->prepare(
                "SELECT description FROM $table_name WHERE id = %d",
                $id
            ));
            if ($description) {
                $descriptiontext .= html_entity_decode($description) . "\n";
            }
        }
    }


    echo esc_textarea($descriptiontext);
    wp_die();
});

//uploader
//extension=gd need to enable
add_filter('upload_mimes', 'allow_custom_mime_types');

function allow_custom_mime_types($mimes)
{
    $mimes['jpg'] = 'image/jpeg';  // Allow .jpg
    $mimes['jpeg'] = 'image/jpeg'; // Allow .jpeg
    return $mimes;
}
add_action('wp_ajax_amelia_custom_image_upload', 'resize_handle_custom_image_upload');

function resize_handle_custom_image_upload()
{
    $info_file = isset($_POST['info_file']) ? $_POST['info_file'] : '';
    if (isset($_FILES['image_file']) && !empty($_FILES['image_file']['name']) || $info_file) {
        // Get the file details
        if ($info_file) {
            $file = $_FILES['image_file2'];
        } else {
            $file = $_FILES['image_file'];
        }


        // Check if the file is an image
        if (strpos($file['type'], 'image') === false) {
            wp_send_json_error(array('message' => 'The uploaded file is not an image.'));
        }

        // Check file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'File size exceeds 10 MB.'));
        }

        // Load the image into PHP for resizing (using GD)
        $image_path = $file['tmp_name'];
        list($width, $height, $type) = getimagesize($image_path);

        // Set the maximum size for the resized image (1 MB)
        $max_size = 1 * 1024 * 1024; // 1 MB

        // Resize the image if it exceeds the 1 MB size
        if ($file['size'] > $max_size) {
            $resized_image = resize_image($image_path, $width, $height, $max_size);
            if ($resized_image) {
                $image_path = $resized_image;
            }
        }
        //echo $image_path;
        // Prepare the upload directory
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['path'] . '/' . sanitize_file_name($file['name']);

        // Move the file to the WordPress uploads directory
        move_uploaded_file($image_path, $upload_path);

        // Create an attachment in WordPress (without generating extra sizes)
        $attachment = array(
            'guid'           => $upload_dir['url'] . '/' . basename($upload_path),
            'post_mime_type' => $file['type'],
            'post_title'     => sanitize_text_field($file['name']),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        // Insert the attachment into the media library
        $attachment_id = wp_insert_attachment($attachment, $upload_path);
        $metadata = wp_generate_attachment_metadata($attachment_id, $upload_path);
        wp_update_attachment_metadata($attachment_id, $metadata);
        // Return the URL of the uploaded image
        wp_send_json_success(array('url' => $upload_dir['url'] . '/' . basename($upload_path)));
    } else {
        wp_send_json_error(array('message' => 'No file uploaded.'));
    }
}
add_action('wp_ajax_amelia_custom_image_upload_multiple', 'amelia_custom_image_upload_multiple');

function amelia_custom_image_upload_multiple()
{
    $info_file = isset($_POST['info_file']) ? $_POST['info_file'] : '';
    if (isset($_FILES['image_file']) && !empty($_FILES['image_file']['name'][0]) || $info_file) {
        $uploaded_urls = array();
        if ($info_file) {
            $files = $_FILES['image_file2']['name'];
        } else {
            $files = $_FILES['image_file']['name'];
        }
        // Loop through each file
        foreach ($files as $key => $file_name) {
            // Get file details
            if ($info_file) {
                $file = array(
                    'name'     => $_FILES['image_file2']['name'][$key],
                    'type'     => $_FILES['image_file2']['type'][$key],
                    'tmp_name' => $_FILES['image_file2']['tmp_name'][$key],
                    'error'    => $_FILES['image_file2']['error'][$key],
                    'size'     => $_FILES['image_file2']['size'][$key]
                );
            } else {
                $file = array(
                    'name'     => $_FILES['image_file']['name'][$key],
                    'type'     => $_FILES['image_file']['type'][$key],
                    'tmp_name' => $_FILES['image_file']['tmp_name'][$key],
                    'error'    => $_FILES['image_file']['error'][$key],
                    'size'     => $_FILES['image_file']['size'][$key]
                );
            }


            // Check if the file is an image
            if (strpos($file['type'], 'image') === false) {
                wp_send_json_error(array('message' => 'One of the uploaded files is not an image.'));
            }

            // Load the image into PHP for resizing (using GD)
            $image_path = $file['tmp_name'];
            list($width, $height, $type) = getimagesize($image_path);

            // Set the maximum size for the resized image (1 MB)
            $max_size = 1 * 1024 * 1024; // 1 MB

            // Resize the image if it exceeds the 1 MB size
            if ($file['size'] > $max_size) {
                $resized_image = resize_image($image_path, $width, $height, $max_size);
                if ($resized_image) {
                    $image_path = $resized_image;
                }
            }

            // Prepare the upload directory
            $upload_dir = wp_upload_dir();
            $upload_path = $upload_dir['path'] . '/' . sanitize_file_name($file['name']);

            // Move the file to the WordPress uploads directory
            move_uploaded_file($image_path, $upload_path);

            // Create an attachment in WordPress (without generating extra sizes)
            $attachment = array(
                'guid'           => $upload_dir['url'] . '/' . basename($upload_path),
                'post_mime_type' => $file['type'],
                'post_title'     => sanitize_text_field($file['name']),
                'post_content'   => '',
                'post_status'    => 'inherit',
            );

            // Insert the attachment into the media library
            $attachment_id = wp_insert_attachment($attachment, $upload_path);
            $metadata = wp_generate_attachment_metadata($attachment_id, $upload_path);
            wp_update_attachment_metadata($attachment_id, $metadata);

            // Add URL of uploaded image to response array
            $uploaded_urls[] = $upload_dir['url'] . '/' . basename($upload_path);
        }

        // Return the URLs of the uploaded images
        wp_send_json_success(array('urls' => $uploaded_urls));
    } else {
        wp_send_json_error(array('message' => 'No files uploaded.'));
    }
}

// Helper function to resize image
function resize_image($image_path, $width, $height, $max_size)
{
    //echo 'resizing';
    $image_type = exif_imagetype($image_path);

    switch ($image_type) {
        case IMAGETYPE_JPEG:

            $image = imagecreatefromjpeg($image_path);
            break;
        case IMAGETYPE_PNG:

            $image = imagecreatefrompng($image_path);
            break;
        case IMAGETYPE_GIF:

            $image = imagecreatefromgif($image_path);
            break;
        default:
            // echo 'other';
            return false; // Unsupported image type
    }
    //echo 'processing';
    // Calculate new dimensions while maintaining the aspect ratio
    $aspect_ratio = $width / $height;
    $new_width = 1024; // Set new width (adjust as necessary)
    $new_height = $new_width / $aspect_ratio;

    // Create a new image resource with the new dimensions
    $new_image = imagecreatetruecolor($new_width, $new_height);

    // Copy and resize the original image into the new image
    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Save the resized image to a temporary location
    //$resized_image_path = tempnam(sys_get_temp_dir(), 'resized_image_');
    imagejpeg($new_image, $image_path, 75); // Quality 75 for JPEG

    // Clean up memory
    imagedestroy($image);
    imagedestroy($new_image);

    // Return the path to the resized image
    return $image_path;
}
//uploader

add_action('plugins_loaded', 'my_plugin_check_column');

function my_plugin_check_column()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "amelia_body_chart_ref";
    $table_name2 = $wpdb->prefix . "amelia_body_chart";

    // Check only once by storing in wp_options
    if (!get_option('ref_table_user_id_added')) {
        $row = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $table_name LIKE %s",
            'user_id'
        ));

        if (empty($row)) {
            $wpdb->query("ALTER TABLE $table_name ADD user_id INT(11) NOT NULL DEFAULT 0");
        }

        update_option('ref_table_user_id_added', 1); // mark as done
    }
    if (!get_option('bodychart_table_user_id_added')) {
        $row = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $table_name2 LIKE %s",
            'user_id'
        ));

        if (empty($row)) {
            $wpdb->query("ALTER TABLE $table_name2 ADD user_id INT(11) NOT NULL DEFAULT 0");
        }

        update_option('bodychart_table_user_id_added', 1); // mark as done
    }
}
//ai chatgpt
function ai_improve_button($field_key)
{
    return '<button 
                type="button" 
                class="button ai-improve-btn removefrompdf" 
                data-field="' . esc_attr($field_key) . '">
                ✨ Improve with AI
            </button>';
}


/**
 * AJAX handler
 */
add_action('wp_ajax_ai_improve_text', 'ai_improve_text_callback');
function ai_improve_text_callback()
{
    //  print_r($_POST);

    $text  = isset($_POST['text']) ? sanitize_textarea_field($_POST['text']) : '';
    $field = isset($_POST['field']) ? sanitize_text_field($_POST['field']) : '';

    if (!$text) {
        wp_send_json_error('No text received.');
    }

    // --- PROMPT LOGIC BASED ON FIELD NAME ---
    $prompt = "You are a clinical documentation assistant. ";
    $prompt .= "Improve this medical comment for the field: $field. ";
    $prompt .= get_option('chatGpt_api_role_user_prompt_description_improvement', '');

    $prompt .= "COMMENT:\n" . $text;



    //———————————————
    // CALL CHATGPT API (GPT-4.1 or GPT-3.5)
    //———————————————
    $apiKey = get_option('chatGpt_api_key', '');
    if (!$apiKey) {
        wp_send_json_error("API error: " . $response->get_error_message());
    }
    $payload = [
        "model"       => "gpt-4o-mini",  // fast + accurate
        "messages"    => [
            ["role" => "system", "content" => "You improve medical documentation."],
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 0.2
    ];

    $response = wp_remote_post(
        "https://api.openai.com/v1/chat/completions",
        [
            "headers" => [
                "Content-Type"  => "application/json",
                "Authorization" => "Bearer $apiKey"
            ],
            "body" => json_encode($payload),
            "timeout" => 30
        ]
    );

    if (is_wp_error($response)) {
        wp_send_json_error("API error: " . $response->get_error_message());
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($body["choices"][0]["message"]["content"])) {
        wp_send_json_error("Malformed AI response.");
    }

    $improved = trim($body["choices"][0]["message"]["content"]);

    wp_send_json_success([
        'improved' => $improved
    ]);
}

/**
 * Ensure combined reports table exists, create if it doesn't
 */
function ensure_combined_reports_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'amelia_combined_reports';

    // Check if table exists
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;

    if (!$table_exists) {
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            report_name varchar(255) NOT NULL,
            service_ids text NOT NULL,
            service_names text NOT NULL,
            customer_ids text DEFAULT NULL,
            customer_names text DEFAULT NULL,
            coupon_codes text DEFAULT NULL,
            report_data LONGTEXT NOT NULL,
            form_data LONGTEXT DEFAULT NULL,
            pdf_url varchar(500) DEFAULT NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY created_by (created_by),
            KEY created_at (created_at)
        ) $charset_collate;";

        dbDelta($sql);
    } else {
        // Check if form_data column exists, add it if not
        $column_exists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM $table_name LIKE 'form_data'"));
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN form_data LONGTEXT DEFAULT NULL AFTER report_data");
        }
        // Check if customer_ids column exists, add it if not
        $column_exists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM $table_name LIKE 'customer_ids'"));
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN customer_ids text DEFAULT NULL AFTER service_names");
        }
        // Check if customer_names column exists, add it if not
        $column_exists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM $table_name LIKE 'customer_names'"));
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN customer_names text DEFAULT NULL AFTER customer_ids");
        }
    }
}

/**
 * Save combined report to database
 */
function save_combined_report()
{
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce provided.', 403);
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions.', 403);
    }

    // Ensure table exists before saving
    ensure_combined_reports_table();

    $report_name = isset($_POST['report_name']) ? sanitize_text_field($_POST['report_name']) : '';
    $service_ids = isset($_POST['service_ids']) ? (array) $_POST['service_ids'] : array();
    $service_names = isset($_POST['service_names']) ? sanitize_text_field($_POST['service_names']) : '';
    $customer_ids = isset($_POST['customer_ids']) ? (array) $_POST['customer_ids'] : array();
    $customer_names = isset($_POST['customer_names']) ? sanitize_text_field($_POST['customer_names']) : '';
    $coupon_codes = isset($_POST['coupon_codes']) ? sanitize_text_field($_POST['coupon_codes']) : '';
    $report_data = isset($_POST['report_data']) ? $_POST['report_data'] : '';

    // Get all form data (contact info, address, email, photo, etc.)
    $form_data = array(
        'contact_name' => isset($_POST['contact_name']) ? sanitize_text_field($_POST['contact_name']) : '',
        'phone' => isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '',
        'details' => isset($_POST['details']) ? sanitize_textarea_field($_POST['details']) : '',
        'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
        'report_email' => isset($_POST['report_email']) ? sanitize_text_field($_POST['report_email']) : '',
        'uploaded_file_url' => isset($_POST['uploaded_file_url']) ? esc_url_raw($_POST['uploaded_file_url']) : '',
        'chat_gpt_sumarry' => isset($_POST['chat_gpt_sumarry']) ? wp_kses_post($_POST['chat_gpt_sumarry']) : ''
    );

    if (empty($report_name)) {
        wp_send_json_error('Report name is required.');
    }

    if (empty($service_ids)) {
        wp_send_json_error('At least one service must be selected.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'amelia_combined_reports';

    $service_ids_json = json_encode($service_ids);
    $customer_ids_json = !empty($customer_ids) ? json_encode($customer_ids) : '';
    $report_data_json = is_array($report_data) ? json_encode($report_data) : $report_data;
    $form_data_json = json_encode($form_data);

    $result = $wpdb->insert(
        $table_name,
        array(
            'report_name' => $report_name,
            'service_ids' => $service_ids_json,
            'service_names' => $service_names,
            'customer_ids' => $customer_ids_json,
            'customer_names' => $customer_names,
            'coupon_codes' => $coupon_codes,
            'report_data' => $report_data_json,
            'form_data' => $form_data_json,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
    );

    if ($result === false) {
        wp_send_json_error('Failed to save report: ' . $wpdb->last_error);
    }

    wp_send_json_success(array(
        'id' => $wpdb->insert_id,
        'message' => 'Report saved successfully.'
    ));
}

/**
 * Get saved combined reports with filters
 */
function get_saved_combined_reports()
{
    $nonce = isset($_GET['nonce']) ? sanitize_text_field($_GET['nonce']) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce provided.', 403);
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions.', 403);
    }

    // Ensure table exists
    ensure_combined_reports_table();

    global $wpdb;
    $table_name = $wpdb->prefix . 'amelia_combined_reports';

    // Check if requesting single report by ID
    $report_id = isset($_GET['report_id']) ? intval($_GET['report_id']) : 0;
    if ($report_id > 0) {
        $report = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $report_id), ARRAY_A);
        if ($report) {
            $report['service_ids'] = json_decode($report['service_ids'], true);
            if (!empty($report['customer_ids'])) {
                $report['customer_ids'] = json_decode($report['customer_ids'], true);
            } else {
                $report['customer_ids'] = array();
            }
            $report['report_data'] = json_decode($report['report_data'], true);
            if (!empty($report['form_data'])) {
                $report['form_data'] = json_decode($report['form_data'], true);
            }
            $report['created_by_name'] = get_userdata($report['created_by'])->display_name ?? 'Unknown';
            wp_send_json_success(array('reports' => array($report), 'total' => 1, 'page' => 1, 'per_page' => 1, 'total_pages' => 1));
        } else {
            wp_send_json_error('Report not found.');
        }
        return;
    }

    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 20;
    $offset = ($page - 1) * $per_page;

    $where = array();
    $where_values = array();

    if (!empty($search)) {
        $where[] = "(report_name LIKE %s OR service_names LIKE %s)";
        $search_like = '%' . $wpdb->esc_like($search) . '%';
        $where_values[] = $search_like;
        $where_values[] = $search_like;
    }

    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    if (!empty($where_values)) {
        $where_clause = $wpdb->prepare($where_clause, $where_values);
    }

    $total_query = "SELECT COUNT(*) FROM $table_name $where_clause";
    $total = $wpdb->get_var($total_query);

    $query = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $query = $wpdb->prepare($query, $per_page, $offset);
    $results = $wpdb->get_results($query, ARRAY_A);

    foreach ($results as &$result) {
        $result['service_ids'] = json_decode($result['service_ids'], true);
        if (!empty($result['customer_ids'])) {
            $result['customer_ids'] = json_decode($result['customer_ids'], true);
        } else {
            $result['customer_ids'] = array();
        }
        $result['report_data'] = json_decode($result['report_data'], true);
        if (!empty($result['form_data'])) {
            $result['form_data'] = json_decode($result['form_data'], true);
        }
        $result['created_by_name'] = get_userdata($result['created_by'])->display_name ?? 'Unknown';
    }

    wp_send_json_success(array(
        'reports' => $results,
        'total' => intval($total),
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => ceil($total / $per_page)
    ));
}

/**
 * Delete saved combined report
 */
function delete_saved_combined_report()
{
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce provided.', 403);
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions.', 403);
    }

    // Ensure table exists
    ensure_combined_reports_table();

    $report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;

    if (empty($report_id)) {
        wp_send_json_error('Report ID is required.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'amelia_combined_reports';

    // Get PDF URL before deleting to optionally remove file
    $report = $wpdb->get_row($wpdb->prepare("SELECT pdf_url FROM $table_name WHERE id = %d", $report_id), ARRAY_A);

    if ($report && !empty($report['pdf_url'])) {
        $pdf_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $report['pdf_url']);
        if (file_exists($pdf_path)) {
            @unlink($pdf_path);
        }
    }

    $result = $wpdb->delete($table_name, array('id' => $report_id), array('%d'));

    if ($result === false) {
        wp_send_json_error('Failed to delete report: ' . $wpdb->last_error);
    }

    wp_send_json_success(array('message' => 'Report deleted successfully.'));
}

/**
 * Download saved combined report PDF
 */
function download_saved_combined_report()
{
    if (!isset($_GET['action']) || $_GET['action'] !== 'download_saved_combined_report') {
        return;
    }

    $nonce = isset($_GET['nonce']) ? sanitize_text_field($_GET['nonce']) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'ajax-nonce')) {
        wp_die('Invalid nonce provided.', 403);
    }

    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions.', 403);
    }

    $report_id = isset($_GET['report_id']) ? intval($_GET['report_id']) : 0;

    if (empty($report_id)) {
        wp_die('Report ID is required.');
    }

    // Ensure table exists
    ensure_combined_reports_table();

    global $wpdb;
    $table_name = $wpdb->prefix . 'amelia_combined_reports';

    $report = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $report_id), ARRAY_A);

    if (!$report) {
        wp_die('Report not found.');
    }

    if (!empty($report['pdf_url'])) {
        $pdf_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $report['pdf_url']);
        if (file_exists($pdf_path)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . sanitize_file_name($report['report_name']) . '.pdf"');
            readfile($pdf_path);
            exit;
        }
    }

    // If PDF doesn't exist, redirect to generate PDF with combined services
    $service_ids = json_decode($report['service_ids'], true);
    if (!empty($service_ids)) {
        $redirect_url = admin_url('admin.php?page=amelia-report&combined_services=' . implode(',', $service_ids) . '&generate_pdf=1');
        wp_redirect($redirect_url);
        exit;
    }

    wp_die('Unable to generate PDF for this report.');
}

function get_appointment_ref_btn($id)
{

    $body_chart = get_appoinment_body_chart($id);
    $body_chart_data = json_decode($body_chart->data, true);
    $data = array();

    if ($body_chart_data['referal']) {
        if ($body_chart_data['referal'] == 'No') {
            $data['referral'] = 'No';
        } else if ($body_chart_data['referal'] == 'Info Only') {
            $data['referral'] = 'Info';
        } else {

            $data['referral'] = 'Yes';
        }
    } else {
        $data['referral'] = $body_chart_data['referal'];
    }

    $data['attend'] = $body_chart_data['attend'];
    return $data;
}


/**
 * ==========================================
 * HISTORICAL PHOTOS FEATURE
 * Patient photo database for lesion tracking
 * @since 9.6.0
 * ==========================================
 */

/**
 * Ensure patient photos table exists
 */
function ensure_patient_photos_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'amelia_patient_photos';

    // Check if table exists using option flag for performance
    if (get_option('amelia_patient_photos_table_created')) {
        return;
    }

    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;

    if (!$table_exists) {
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) NOT NULL,
            appointment_id bigint(20) DEFAULT NULL,
            file_url varchar(500) NOT NULL,
            body_location varchar(50) NOT NULL COMMENT 'frontCanvas, backCanvas, face1Canvas, face2Canvas',
            marker_x decimal(10,2) DEFAULT NULL COMMENT 'X coordinate of marker on body chart',
            marker_y decimal(10,2) DEFAULT NULL COMMENT 'Y coordinate of marker on body chart',
            lesion_id varchar(64) DEFAULT NULL COMMENT 'Unique ID for tracking same lesion across visits',
            notes text DEFAULT NULL,
            upload_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            uploaded_by bigint(20) NOT NULL,
            PRIMARY KEY (id),
            KEY customer_id (customer_id),
            KEY appointment_id (appointment_id),
            KEY lesion_id (lesion_id),
            KEY body_location (body_location),
            KEY upload_date (upload_date)
        ) $charset_collate;";

        dbDelta($sql);
        update_option('amelia_patient_photos_table_created', 1);
    }
}

// Initialize table on plugin load
add_action('plugins_loaded', 'ensure_patient_photos_table');

/**
 * AJAX: Search customers for Historical Photos
 */
add_action('wp_ajax_hp_search_customers', 'hp_search_customers_callback');
function hp_search_customers_callback()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    global $wpdb;
    $query = sanitize_text_field($_POST['query']);
    $like = '%' . $wpdb->esc_like($query) . '%';

    $customers = $wpdb->get_results($wpdb->prepare("
        SELECT u.id, u.firstName, u.lastName, u.email, u.phone,
               COALESCE(p.photo_count, 0) as photo_count
        FROM {$wpdb->prefix}amelia_users u
        LEFT JOIN (
            SELECT customer_id, COUNT(*) as photo_count 
            FROM {$wpdb->prefix}amelia_patient_photos 
            GROUP BY customer_id
        ) p ON u.id = p.customer_id
        WHERE u.type = 'customer' 
        AND (u.firstName LIKE %s OR u.lastName LIKE %s OR u.email LIKE %s OR u.phone LIKE %s)
        ORDER BY photo_count DESC, u.firstName ASC
        LIMIT 20
    ", $like, $like, $like, $like));

    wp_send_json_success($customers);
}

/**
 * AJAX: Save photo with lesion tracking
 */
add_action('wp_ajax_hp_save_photo', 'hp_save_photo_callback');
function hp_save_photo_callback()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'amelia_patient_photos';

    $customer_id = intval($_POST['customer_id']);
    $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : null;
    $file_url = esc_url_raw($_POST['file_url']);
    $body_location = sanitize_text_field($_POST['body_location']);
    $marker_x = isset($_POST['marker_x']) ? floatval($_POST['marker_x']) : null;
    $marker_y = isset($_POST['marker_y']) ? floatval($_POST['marker_y']) : null;
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

    // Generate or use existing lesion_id
    $lesion_id = '';
    if ($marker_x !== null && $marker_y !== null) {
        // Check if there's an existing lesion at similar coordinates for this customer
        $existing_lesion = $wpdb->get_var($wpdb->prepare("
            SELECT lesion_id FROM $table_name 
            WHERE customer_id = %d 
            AND body_location = %s 
            AND ABS(marker_x - %f) < 15 
            AND ABS(marker_y - %f) < 15
            AND lesion_id IS NOT NULL AND lesion_id != ''
            ORDER BY upload_date DESC
            LIMIT 1
        ", $customer_id, $body_location, $marker_x, $marker_y));

        if ($existing_lesion) {
            $lesion_id = $existing_lesion;
        } else {
            // Generate new lesion ID based on location
            $lesion_id = wp_generate_uuid4();
        }
    }

    $result = $wpdb->insert($table_name, array(
        'customer_id' => $customer_id,
        'appointment_id' => $appointment_id,
        'file_url' => $file_url,
        'body_location' => $body_location,
        'marker_x' => $marker_x,
        'marker_y' => $marker_y,
        'lesion_id' => $lesion_id,
        'notes' => $notes,
        'uploaded_by' => get_current_user_id()
    ));

    if ($result) {
        wp_send_json_success(array(
            'id' => $wpdb->insert_id,
            'lesion_id' => $lesion_id
        ));
    } else {
        wp_send_json_error('Failed to save photo');
    }
}

/**
 * AJAX: Get photos for a customer
 */
add_action('wp_ajax_hp_get_customer_photos', 'hp_get_customer_photos_callback');
function hp_get_customer_photos_callback()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    global $wpdb;
    $customer_id = intval($_POST['customer_id']);
    $body_location = isset($_POST['body_location']) ? sanitize_text_field($_POST['body_location']) : '';
    $lesion_id = isset($_POST['lesion_id']) ? sanitize_text_field($_POST['lesion_id']) : '';

    $where = "WHERE p.customer_id = %d";
    $params = array($customer_id);

    if ($body_location) {
        $where .= " AND p.body_location = %s";
        $params[] = $body_location;
    }

    if ($lesion_id) {
        $where .= " AND p.lesion_id = %s";
        $params[] = $lesion_id;
    }

    $photos = $wpdb->get_results($wpdb->prepare("
        SELECT p.*, a.bookingStart, s.name as service_name
        FROM {$wpdb->prefix}amelia_patient_photos p
        LEFT JOIN {$wpdb->prefix}amelia_appointments a ON p.appointment_id = a.id
        LEFT JOIN {$wpdb->prefix}amelia_services s ON a.serviceId = s.id
        $where
        ORDER BY p.upload_date DESC
    ", $params));

    wp_send_json_success($photos);
}

/**
 * AJAX: Get lesion history
 */
add_action('wp_ajax_hp_get_lesion_history', 'hp_get_lesion_history_callback');
function hp_get_lesion_history_callback()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    global $wpdb;
    $lesion_id = sanitize_text_field($_POST['lesion_id']);

    $photos = $wpdb->get_results($wpdb->prepare("
        SELECT p.*, a.bookingStart, s.name as service_name,
               u.firstName as clinician_first, u.lastName as clinician_last
        FROM {$wpdb->prefix}amelia_patient_photos p
        LEFT JOIN {$wpdb->prefix}amelia_appointments a ON p.appointment_id = a.id
        LEFT JOIN {$wpdb->prefix}amelia_services s ON a.serviceId = s.id
        LEFT JOIN {$wpdb->prefix}users u ON p.uploaded_by = u.ID
        WHERE p.lesion_id = %s
        ORDER BY p.upload_date ASC
    ", $lesion_id));

    wp_send_json_success($photos);
}

/**
 * AJAX: Update photo notes
 */
add_action('wp_ajax_hp_update_photo_notes', 'hp_update_photo_notes_callback');
function hp_update_photo_notes_callback()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    global $wpdb;
    $photo_id = intval($_POST['photo_id']);
    $notes = sanitize_textarea_field($_POST['notes']);

    $result = $wpdb->update(
        $wpdb->prefix . 'amelia_patient_photos',
        array('notes' => $notes),
        array('id' => $photo_id)
    );

    if ($result !== false) {
        wp_send_json_success('Notes updated');
    } else {
        wp_send_json_error('Failed to update notes');
    }
}

/**
 * AJAX: Delete photo
 */
add_action('wp_ajax_hp_delete_photo', 'hp_delete_photo_callback');
function hp_delete_photo_callback()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    global $wpdb;
    $photo_id = intval($_POST['photo_id']);

    $result = $wpdb->delete(
        $wpdb->prefix . 'amelia_patient_photos',
        array('id' => $photo_id)
    );

    if ($result) {
        wp_send_json_success('Photo deleted');
    } else {
        wp_send_json_error('Failed to delete photo');
    }
}

/**
 * AJAX: Link photo to existing lesion
 */
add_action('wp_ajax_hp_link_to_lesion', 'hp_link_to_lesion_callback');
function hp_link_to_lesion_callback()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    global $wpdb;
    $photo_id = intval($_POST['photo_id']);
    $lesion_id = sanitize_text_field($_POST['lesion_id']);

    $result = $wpdb->update(
        $wpdb->prefix . 'amelia_patient_photos',
        array('lesion_id' => $lesion_id),
        array('id' => $photo_id)
    );

    if ($result !== false) {
        wp_send_json_success('Photo linked to lesion');
    } else {
        wp_send_json_error('Failed to link photo');
    }
}

/**
 * Auto-save photos to historical database when body chart photos are uploaded
 * Hook into the existing body chart save
 */
add_action('wp_ajax_auto_save_body_chart_photo', 'auto_save_body_chart_photo_callback');

/**
 * AJAX: Upload a marker photo directly to /patient-photos/ (bypasses Media Library)
 * 
 * Accepts a multipart file upload attached to a specific body-chart marker.
 * Stores the file in the protected directory and inserts a row in
 * wp_amelia_patient_photos with the marker coordinates, customer_id, and
 * appointment_id.
 * 
 * Request: multipart/form-data
 *   - nonce (required)
 *   - appointment_id (required)
 *   - body_location (required: frontCanvas|backCanvas|face1Canvas|face2Canvas)
 *   - marker_x, marker_y (required, numeric)
 *   - marker_photo (file upload; single image)
 * 
 * Response: { success, data: { photo_id, file_url, lesion_id } }
 */
add_action('wp_ajax_amelia_upload_marker_photo', 'amelia_upload_marker_photo_callback');
function amelia_upload_marker_photo_callback()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
    }
    if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Permission denied'));
    }
    
    if (empty($_FILES['marker_photo']) || empty($_FILES['marker_photo']['tmp_name'])) {
        wp_send_json_error(array('message' => 'No file uploaded'));
    }
    
    $appointment_id = intval($_POST['appointment_id'] ?? 0);
    $body_location = sanitize_text_field($_POST['body_location'] ?? '');
    $marker_x = isset($_POST['marker_x']) ? floatval($_POST['marker_x']) : null;
    $marker_y = isset($_POST['marker_y']) ? floatval($_POST['marker_y']) : null;
    
    if (!$appointment_id || $body_location === '' || $marker_x === null || $marker_y === null) {
        wp_send_json_error(array('message' => 'Missing required fields (appointment_id, body_location, marker_x, marker_y)'));
    }
    
    $file = $_FILES['marker_photo'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(array('message' => 'Upload error code: ' . $file['error']));
    }
    
    // Validate MIME type is an image
    $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif');
    $detected_type = '';
    if (function_exists('mime_content_type')) {
        $detected_type = @mime_content_type($file['tmp_name']);
    }
    $file_type = !empty($detected_type) ? $detected_type : $file['type'];
    
    if (!in_array(strtolower($file_type), $allowed_types, true)) {
        wp_send_json_error(array('message' => 'File must be an image (got: ' . esc_html($file_type) . ')'));
    }
    
    // Resolve customer_id from appointment_id
    global $wpdb;
    $customer_id = $wpdb->get_var($wpdb->prepare(
        "SELECT customerId FROM {$wpdb->prefix}amelia_customer_bookings WHERE appointmentId = %d LIMIT 1",
        $appointment_id
    ));
    if (!$customer_id) {
        wp_send_json_error(array('message' => 'Customer not found for appointment'));
    }
    $customer_id = intval($customer_id);
    
    // Determine destination path
    $protected_dir = ensure_protected_photos_directory();
    $protected_url = content_url('/patient-photos/');
    
    // Generate safe filename
    $original_name = sanitize_file_name(basename($file['name']));
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    if (empty($ext)) {
        // Infer extension from MIME type
        $mime_ext_map = array(
            'image/jpeg' => 'jpg', 'image/jpg' => 'jpg',
            'image/png' => 'png', 'image/gif' => 'gif',
            'image/webp' => 'webp', 'image/heic' => 'heic', 'image/heif' => 'heif'
        );
        $ext = $mime_ext_map[strtolower($file_type)] ?? 'jpg';
    }
    
    $safe_location = preg_replace('/[^a-zA-Z0-9_-]/', '', $body_location);
    $new_filename = sprintf(
        'c%d_a%d_%s_%s_%s.%s',
        $customer_id,
        $appointment_id,
        $safe_location,
        round($marker_x, 1) . 'x' . round($marker_y, 1),
        wp_generate_password(8, false, false),
        $ext
    );
    $new_path = $protected_dir . $new_filename;
    $new_url = $protected_url . $new_filename;
    
    // Optionally resize images over 2MB to save space (GD)
    $max_size = 2 * 1024 * 1024;
    if ($file['size'] > $max_size && function_exists('resize_image')) {
        list($w, $h) = @getimagesize($file['tmp_name']);
        if ($w && $h) {
            $resized = resize_image($file['tmp_name'], $w, $h, $max_size);
            if ($resized && file_exists($resized)) {
                // resize_image saves to a temp location; move that to our final path
                if (!@rename($resized, $new_path) && !@copy($resized, $new_path)) {
                    wp_send_json_error(array('message' => 'Failed to save resized file'));
                }
            } else {
                if (!@move_uploaded_file($file['tmp_name'], $new_path)) {
                    wp_send_json_error(array('message' => 'Failed to move uploaded file'));
                }
            }
        } else {
            if (!@move_uploaded_file($file['tmp_name'], $new_path)) {
                wp_send_json_error(array('message' => 'Failed to move uploaded file'));
            }
        }
    } else {
        if (!@move_uploaded_file($file['tmp_name'], $new_path)) {
            wp_send_json_error(array('message' => 'Failed to move uploaded file'));
        }
    }
    
    @chmod($new_path, 0644);
    
    // Insert into patient_photos, linking to existing lesion at nearby coords
    ensure_patient_photos_table();
    
    $existing_lesion = $wpdb->get_var($wpdb->prepare(
        "SELECT lesion_id FROM {$wpdb->prefix}amelia_patient_photos 
         WHERE customer_id = %d 
         AND body_location = %s 
         AND ABS(marker_x - %f) < 15 
         AND ABS(marker_y - %f) < 15
         AND lesion_id IS NOT NULL AND lesion_id != ''
         ORDER BY upload_date DESC
         LIMIT 1",
        $customer_id, $body_location, $marker_x, $marker_y
    ));
    $lesion_id = $existing_lesion ?: wp_generate_uuid4();
    
    $inserted = $wpdb->insert($wpdb->prefix . 'amelia_patient_photos', array(
        'customer_id' => $customer_id,
        'appointment_id' => $appointment_id,
        'file_url' => $new_url,
        'body_location' => $body_location,
        'marker_x' => $marker_x,
        'marker_y' => $marker_y,
        'lesion_id' => $lesion_id,
        'notes' => '',
        'uploaded_by' => get_current_user_id()
    ));
    
    if (!$inserted) {
        @unlink($new_path);
        wp_send_json_error(array('message' => 'Failed to insert photo record: ' . $wpdb->last_error));
    }
    
    wp_send_json_success(array(
        'photo_id' => intval($wpdb->insert_id),
        'file_url' => $new_url,
        'lesion_id' => $lesion_id,
        'customer_id' => $customer_id,
        'marker_x' => $marker_x,
        'marker_y' => $marker_y,
        'body_location' => $body_location
    ));
}

/**
 * AJAX: Fetch marker photos for the current appointment+customer, grouped by body location.
 * Used by body-chart.php to render existing marker thumbnails on page load.
 */
add_action('wp_ajax_amelia_get_marker_photos', 'amelia_get_marker_photos_callback');
function amelia_get_marker_photos_callback()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
    }
    
    $appointment_id = intval($_POST['appointment_id'] ?? 0);
    if (!$appointment_id) {
        wp_send_json_error(array('message' => 'Missing appointment_id'));
    }
    
    global $wpdb;
    $customer_id = $wpdb->get_var($wpdb->prepare(
        "SELECT customerId FROM {$wpdb->prefix}amelia_customer_bookings WHERE appointmentId = %d LIMIT 1",
        $appointment_id
    ));
    if (!$customer_id) {
        wp_send_json_success(array('photos' => array()));
    }
    
    $photos = $wpdb->get_results($wpdb->prepare(
        "SELECT id, file_url, body_location, marker_x, marker_y, lesion_id, upload_date, appointment_id
         FROM {$wpdb->prefix}amelia_patient_photos
         WHERE customer_id = %d AND marker_x IS NOT NULL AND marker_y IS NOT NULL
         ORDER BY upload_date DESC",
        intval($customer_id)
    ));
    
    $result = array();
    foreach ($photos as $p) {
        $result[] = array(
            'id' => intval($p->id),
            'file_url' => $p->file_url,
            'body_location' => $p->body_location,
            'marker_x' => floatval($p->marker_x),
            'marker_y' => floatval($p->marker_y),
            'lesion_id' => $p->lesion_id,
            'upload_date' => $p->upload_date,
            'appointment_id' => intval($p->appointment_id),
            'is_current_appointment' => (intval($p->appointment_id) === $appointment_id)
        );
    }
    
    wp_send_json_success(array('photos' => $result, 'customer_id' => intval($customer_id)));
}

/**
 * AJAX: Delete a marker photo (removes both file and DB record)
 */
add_action('wp_ajax_amelia_delete_marker_photo', 'amelia_delete_marker_photo_callback');
function amelia_delete_marker_photo_callback()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
    }
    if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Permission denied'));
    }
    
    $photo_id = intval($_POST['photo_id'] ?? 0);
    if (!$photo_id) {
        wp_send_json_error(array('message' => 'Missing photo_id'));
    }
    
    global $wpdb;
    $photo = $wpdb->get_row($wpdb->prepare(
        "SELECT file_url FROM {$wpdb->prefix}amelia_patient_photos WHERE id = %d",
        $photo_id
    ));
    if (!$photo) {
        wp_send_json_error(array('message' => 'Photo not found'));
    }
    
    // Delete file
    $upload_dir = wp_upload_dir();
    $protected_url_prefix = content_url('/patient-photos/');
    if (strpos($photo->file_url, $protected_url_prefix) === 0) {
        $filename = basename($photo->file_url);
        $filepath = WP_CONTENT_DIR . '/patient-photos/' . $filename;
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
    }
    
    $wpdb->delete($wpdb->prefix . 'amelia_patient_photos', array('id' => $photo_id));
    
    wp_send_json_success(array('photo_id' => $photo_id));
}

/* ============================================================
 * Number Checker (v9.7.1)
 * Scans wp_amelia_users for customer phones that either:
 *   - are missing a country code (don't start with '+' or '00'), OR
 *   - contain non-digit characters (parens, dashes, commas, spaces,
 *     letters, etc.) that muddy the data.
 * Bulk-applies a '+XX' prefix where needed and normalises to clean
 * digits-only format (preserving the leading '+').
 * ============================================================ */

/**
 * A phone is considered to be missing a country code if it does NOT
 * start with '+' or '00' (after trimming). Empty strings are ignored.
 */
function amelia_nc_phone_missing_cc($phone) {
    $phone = trim((string) $phone);
    if ($phone === '') return false;
    if ($phone[0] === '+') return false;
    if (substr($phone, 0, 2) === '00') return false;
    return true;
}

/**
 * Returns true when the phone contains ANY non-digit character besides
 * a single leading '+'. Parens, dashes, spaces, commas, letters, etc.
 */
function amelia_nc_phone_has_junk($phone) {
    $phone = trim((string) $phone);
    if ($phone === '') return false;
    // Allow a single leading '+' only; everything else must be digits.
    $body = ($phone[0] === '+') ? substr($phone, 1) : $phone;
    return preg_match('/\D/', $body) === 1;
}

/**
 * Returns true when the phone needs cleaning for any reason.
 */
function amelia_nc_phone_needs_fix($phone) {
    return amelia_nc_phone_missing_cc($phone) || amelia_nc_phone_has_junk($phone);
}

/**
 * Normalise a phone number.
 *   - If it starts with '+'  →  keep the '+', strip all non-digits from body.
 *   - If it starts with '00' →  replace with '+', strip non-digits from body.
 *   - Otherwise              →  prepend '+<country_code>' and strip any leading
 *                                zeros / non-digits.
 * Returns the cleaned string, or null if a country code is required and
 * none was supplied.
 */
function amelia_nc_normalise_phone($raw, $country_code = '') {
    $raw = trim((string) $raw);
    if ($raw === '') return '';

    if ($raw[0] === '+') {
        $body = preg_replace('/\D/', '', substr($raw, 1));
        return $body === '' ? null : '+' . $body;
    }
    if (substr($raw, 0, 2) === '00') {
        $body = preg_replace('/\D/', '', substr($raw, 2));
        return $body === '' ? null : '+' . $body;
    }
    // No country code in the number — need one from the user.
    $cc = preg_replace('/\D/', '', (string) $country_code);
    if ($cc === '') return null;

    // Strip non-digits, then drop any leading zeros from the local portion.
    $digits = preg_replace('/\D/', '', $raw);
    $digits = preg_replace('/^0+/', '', $digits);
    return $digits === '' ? null : '+' . $cc . $digits;
}

add_action('wp_ajax_amelia_nc_scan', 'amelia_nc_scan_callback');
function amelia_nc_scan_callback() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied'));
    }

    global $wpdb;
    $users_table = $wpdb->prefix . 'amelia_users';

    if ($wpdb->get_var("SHOW TABLES LIKE '$users_table'") !== $users_table) {
        wp_send_json_error(array('message' => 'Amelia users table not found'));
    }

    // Pull every customer with a non-empty phone; filter in PHP so we catch
    // both "missing CC" and "contains junk" cases in one pass.
    $results = $wpdb->get_results(
        "SELECT id, firstName, lastName, email, phone
         FROM $users_table
         WHERE type = 'customer'
         AND phone IS NOT NULL
         AND phone != ''
         ORDER BY id DESC"
    );

    $rows = array();
    foreach ($results as $r) {
        $missing_cc = amelia_nc_phone_missing_cc($r->phone);
        $has_junk   = amelia_nc_phone_has_junk($r->phone);
        if (!$missing_cc && !$has_junk) continue;

        if ($missing_cc && $has_junk) {
            $reason = 'Missing country code + formatting';
        } elseif ($missing_cc) {
            $reason = 'Missing country code';
        } else {
            $reason = 'Formatting (non-digit characters)';
        }

        $rows[] = array(
            'id'         => intval($r->id),
            'name'       => trim($r->firstName . ' ' . $r->lastName),
            'email'      => $r->email,
            'phone'      => $r->phone,
            'reason'     => $reason,
            'missing_cc' => $missing_cc,
            'has_junk'   => $has_junk,
        );
    }

    wp_send_json_success(array('rows' => $rows, 'count' => count($rows)));
}

add_action('wp_ajax_amelia_nc_apply', 'amelia_nc_apply_callback');
function amelia_nc_apply_callback() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied'));
    }

    $code = preg_replace('/\D/', '', $_POST['country_code'] ?? '');
    if (strlen($code) > 4) {
        wp_send_json_error(array('message' => 'Country code must be 1–4 digits'));
    }

    $ids = isset($_POST['customer_ids']) ? (array) $_POST['customer_ids'] : array();
    $ids = array_values(array_filter(array_map('intval', $ids)));
    if (empty($ids)) {
        wp_send_json_error(array('message' => 'No customer IDs provided'));
    }

    global $wpdb;
    $users_table = $wpdb->prefix . 'amelia_users';

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $query = $wpdb->prepare(
        "SELECT id, phone FROM $users_table WHERE id IN ($placeholders)",
        ...$ids
    );
    $customers = $wpdb->get_results($query);

    $updated = array();
    $errors  = array();

    foreach ($customers as $c) {
        $raw = trim((string) $c->phone);

        // Skip already-clean numbers entirely.
        if (!amelia_nc_phone_needs_fix($raw)) {
            $errors[] = array('id' => intval($c->id), 'message' => 'Already clean — skipped');
            continue;
        }

        // If the number is missing a country code we MUST have one.
        if (amelia_nc_phone_missing_cc($raw) && $code === '') {
            $errors[] = array('id' => intval($c->id), 'message' => 'Needs a country code — enter one and reapply');
            continue;
        }

        $new_phone = amelia_nc_normalise_phone($raw, $code);
        if ($new_phone === null || $new_phone === '') {
            $errors[] = array('id' => intval($c->id), 'message' => 'Could not normalise (empty after cleaning)');
            continue;
        }

        if ($new_phone === $raw) {
            $errors[] = array('id' => intval($c->id), 'message' => 'No change needed');
            continue;
        }

        $res = $wpdb->update(
            $users_table,
            array('phone' => $new_phone),
            array('id' => intval($c->id))
        );

        if ($res === false) {
            $errors[] = array('id' => intval($c->id), 'message' => 'DB error: ' . $wpdb->last_error);
            continue;
        }

        $updated[] = array(
            'id'        => intval($c->id),
            'old_phone' => $raw,
            'new_phone' => $new_phone,
        );
    }

    wp_send_json_success(array(
        'updated'       => $updated,
        'errors'        => $errors,
        'applied_count' => count($updated),
    ));
}
function auto_save_body_chart_photo_callback()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    global $wpdb;
    ensure_patient_photos_table();

    $appointment_id = intval($_POST['appointment_id']);
    $file_url = esc_url_raw($_POST['file_url']);
    $body_location = sanitize_text_field($_POST['body_location']);
    $marker_x = isset($_POST['marker_x']) ? floatval($_POST['marker_x']) : null;
    $marker_y = isset($_POST['marker_y']) ? floatval($_POST['marker_y']) : null;

    // Get customer ID from appointment
    $customer_id = $wpdb->get_var($wpdb->prepare("
        SELECT cb.customerId 
        FROM {$wpdb->prefix}amelia_customer_bookings cb 
        WHERE cb.appointmentId = %d 
        LIMIT 1
    ", $appointment_id));

    if (!$customer_id) {
        wp_send_json_error('Customer not found for appointment');
    }

    // Check if this exact photo already exists
    $existing = $wpdb->get_var($wpdb->prepare("
        SELECT id FROM {$wpdb->prefix}amelia_patient_photos 
        WHERE customer_id = %d AND file_url = %s
        LIMIT 1
    ", $customer_id, $file_url));

    if ($existing) {
        wp_send_json_success(array('id' => $existing, 'message' => 'Photo already exists'));
        return;
    }

    // Auto-generate lesion ID if coordinates provided
    $lesion_id = '';
    if ($marker_x !== null && $marker_y !== null) {
        $existing_lesion = $wpdb->get_var($wpdb->prepare("
            SELECT lesion_id FROM {$wpdb->prefix}amelia_patient_photos 
            WHERE customer_id = %d 
            AND body_location = %s 
            AND ABS(marker_x - %f) < 15 
            AND ABS(marker_y - %f) < 15
            AND lesion_id IS NOT NULL AND lesion_id != ''
            ORDER BY upload_date DESC
            LIMIT 1
        ", $customer_id, $body_location, $marker_x, $marker_y));

        $lesion_id = $existing_lesion ?: wp_generate_uuid4();
    }

    $result = $wpdb->insert($wpdb->prefix . 'amelia_patient_photos', array(
        'customer_id' => $customer_id,
        'appointment_id' => $appointment_id,
        'file_url' => $file_url,
        'body_location' => $body_location,
        'marker_x' => $marker_x,
        'marker_y' => $marker_y,
        'lesion_id' => $lesion_id,
        'notes' => '',
        'uploaded_by' => get_current_user_id()
    ));

    if ($result) {
        wp_send_json_success(array(
            'id' => $wpdb->insert_id,
            'lesion_id' => $lesion_id
        ));
    } else {
        wp_send_json_error('Failed to save photo to history');
    }
}


/**
 * ==========================================
 * PHOTO MIGRATION TOOL
 * Migrate photos from Media Library to protected storage
 * @since 9.7.0
 * ==========================================
 */

/**
 * Create protected photos directory with .htaccess
 */
function ensure_protected_photos_directory()
{
    $protected_dir = WP_CONTENT_DIR . '/patient-photos/';
    
    if (!file_exists($protected_dir)) {
        wp_mkdir_p($protected_dir);
        
        // Create .htaccess to protect direct access
        $htaccess_content = "# Protect patient photos from direct access\n";
        $htaccess_content .= "Order Deny,Allow\n";
        $htaccess_content .= "Deny from all\n";
        $htaccess_content .= "\n";
        $htaccess_content .= "# Allow access from WordPress\n";
        $htaccess_content .= "<FilesMatch \"\.(jpg|jpeg|png|gif|webp)$\">\n";
        $htaccess_content .= "    Order Allow,Deny\n";
        $htaccess_content .= "    Allow from all\n";
        $htaccess_content .= "</FilesMatch>\n";
        
        file_put_contents($protected_dir . '.htaccess', $htaccess_content);
        
        // Create index.php to prevent directory listing
        file_put_contents($protected_dir . 'index.php', '<?php // Silence is golden');
    }
    
    return $protected_dir;
}

/**
 * AJAX: Scan for photos to migrate
 */
add_action('wp_ajax_pm_scan_photos', 'pm_scan_photos_callback');
function pm_scan_photos_callback()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    global $wpdb;
    $photos = array();
    $customer_ids = array();
    $appointment_ids = array();
    
    $upload_dir = wp_upload_dir();
    $uploads_url_part = str_replace(array('http://', 'https://'), '', $upload_dir['baseurl']);
    
    $bc_table = $wpdb->prefix . 'amelia_body_chart';
    $bcr_table = $wpdb->prefix . 'amelia_body_chart_ref';
    $cb_table = $wpdb->prefix . 'amelia_customer_bookings';
    
    // Helper: extract photo URLs from a body_chart JSON blob
    $extract_photos = function($row, $source_table, $appt_field_name) use (&$photos, &$customer_ids, &$appointment_ids) {
        if (empty($row->data)) return;
        $data = json_decode($row->data, true);
        if (!is_array($data)) return;
        
        $appt_id = isset($row->$appt_field_name) ? intval($row->$appt_field_name) : 0;
        $customer_id = isset($row->customerId) ? intval($row->customerId) : 0;
        
        // Fields that hold comma-separated photo URLs
        $fields = array(
            'uploaded_file_url' => 'frontCanvas',
            'uploaded_file_url2' => 'frontCanvas2',
            'uploaded_file_url_ref' => 'referral'
        );
        
        foreach ($fields as $field => $location) {
            if (empty($data[$field])) continue;
            $urls = array_filter(array_map('trim', explode(',', $data[$field])));
            
            foreach ($urls as $url) {
                // Only migrate URLs that live in wp-content/uploads/ (skip already-migrated)
                if (strpos($url, '/wp-content/uploads/') === false) continue;
                if (strpos($url, '/patient-photos/') !== false) continue;
                
                $photos[] = array(
                    'url' => $url,
                    'source_table' => $source_table,
                    'source_row_id' => intval($row->id),
                    'appointment_id' => $appt_id,
                    'customer_id' => $customer_id,
                    'field' => $field,
                    'body_location' => $location
                );
                
                if ($appt_id) $appointment_ids[$appt_id] = true;
                if ($customer_id) $customer_ids[$customer_id] = true;
            }
        }
    };
    
    // 1) Main body chart photos (note: column is "appoinment_id" — typo in original schema)
    if ($wpdb->get_var("SHOW TABLES LIKE '$bc_table'") === $bc_table) {
        $rows = $wpdb->get_results("
            SELECT bc.id, bc.appoinment_id, bc.user_id, bc.data, cb.customerId
            FROM $bc_table bc
            LEFT JOIN $cb_table cb ON bc.appoinment_id = cb.appointmentId
            WHERE bc.data LIKE '%uploaded_file_url%'
            GROUP BY bc.id
        ");
        foreach ($rows as $row) {
            $extract_photos($row, 'body_chart', 'appoinment_id');
        }
    }
    
    // 2) Referral body chart photos
    if ($wpdb->get_var("SHOW TABLES LIKE '$bcr_table'") === $bcr_table) {
        $rows = $wpdb->get_results("
            SELECT bcr.id, bcr.appointment_id, bcr.user_id, bcr.data, cb.customerId
            FROM $bcr_table bcr
            LEFT JOIN $cb_table cb ON bcr.appointment_id = cb.appointmentId
            WHERE bcr.data LIKE '%uploaded_file_url%'
            GROUP BY bcr.id
        ");
        foreach ($rows as $row) {
            $extract_photos($row, 'body_chart_ref', 'appointment_id');
        }
    }

    wp_send_json_success(array(
        'photos' => $photos,
        'total_photos' => count($photos),
        'total_customers' => count($customer_ids),
        'total_appointments' => count($appointment_ids)
    ));
}

/**
 * AJAX: Migrate a single photo
 */
add_action('wp_ajax_pm_migrate_single_photo', 'pm_migrate_single_photo_callback');
function pm_migrate_single_photo_callback()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    global $wpdb;
    $photo = json_decode(stripslashes($_POST['photo']), true);
    
    if (!$photo || empty($photo['url'])) {
        wp_send_json_error('Invalid photo data');
    }
    
    // Required fields
    $source_table = isset($photo['source_table']) ? $photo['source_table'] : '';
    $source_row_id = isset($photo['source_row_id']) ? intval($photo['source_row_id']) : 0;
    $field = isset($photo['field']) ? $photo['field'] : '';
    
    if (!in_array($source_table, array('body_chart', 'body_chart_ref'), true) || !$source_row_id || !$field) {
        wp_send_json_error('Missing source_table / source_row_id / field');
    }

    $protected_dir = ensure_protected_photos_directory();
    $protected_url = content_url('/patient-photos/');
    
    // Parse the original URL to get file path
    $upload_dir = wp_upload_dir();
    $original_url = $photo['url'];
    
    // Convert URL to file path (supports both http/https prefixed mismatches)
    $original_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $original_url);
    if ($original_path === $original_url) {
        // Try alternate scheme
        $alt_baseurl = (strpos($upload_dir['baseurl'], 'https://') === 0)
            ? str_replace('https://', 'http://', $upload_dir['baseurl'])
            : str_replace('http://', 'https://', $upload_dir['baseurl']);
        $original_path = str_replace($alt_baseurl, $upload_dir['basedir'], $original_url);
    }
    
    if (!file_exists($original_path)) {
        wp_send_json_error('Original file not found: ' . basename($original_path));
    }

    // Generate new filename with customer/appointment prefix
    $filename = basename($original_path);
    $customer_id = intval($photo['customer_id']);
    $appointment_id = intval($photo['appointment_id']);
    $new_filename = $customer_id . '_' . $appointment_id . '_' . time() . '_' . $filename;
    $new_path = $protected_dir . $new_filename;
    $new_url = $protected_url . $new_filename;

    if (!copy($original_path, $new_path)) {
        wp_send_json_error('Failed to copy file to protected directory');
    }

    // Update the source table's data JSON (swap old URL → new URL)
    $table_name = $wpdb->prefix . 'amelia_' . $source_table;
    $row_data = $wpdb->get_var($wpdb->prepare(
        "SELECT data FROM $table_name WHERE id = %d",
        $source_row_id
    ));
    
    if ($row_data) {
        $data = json_decode($row_data, true);
        if (is_array($data) && isset($data[$field])) {
            $data[$field] = str_replace($original_url, $new_url, $data[$field]);
            $wpdb->update(
                $table_name,
                array('data' => json_encode($data, JSON_UNESCAPED_UNICODE)),
                array('id' => $source_row_id)
            );
        }
    }

    // Add to patient photos table
    ensure_patient_photos_table();
    
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}amelia_patient_photos WHERE file_url = %s",
        $new_url
    ));
    
    if (!$existing) {
        $wpdb->insert($wpdb->prefix . 'amelia_patient_photos', array(
            'customer_id' => $customer_id,
            'appointment_id' => $appointment_id,
            'file_url' => $new_url,
            'body_location' => isset($photo['body_location']) ? $photo['body_location'] : '',
            'marker_x' => null,
            'marker_y' => null,
            'lesion_id' => '',
            'notes' => '',
            'uploaded_by' => get_current_user_id()
        ));
    }

    // Find and delete from Media Library (if it was a real attachment)
    $attachment_id = attachment_url_to_postid($original_url);
    if ($attachment_id) {
        wp_delete_attachment($attachment_id, true);
    } else {
        // Not an attachment — just delete the raw file
        @unlink($original_path);
    }

    wp_send_json_success(array(
        'new_url' => $new_url,
        'old_url' => $original_url,
        'customer_id' => $customer_id,
        'appointment_id' => $appointment_id
    ));
}

/**
 * AJAX: Complete migration
 */
add_action('wp_ajax_pm_complete_migration', 'pm_complete_migration_callback');function pm_complete_migration_callback()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    update_option('amelia_photo_migration_status', array(
        'completed' => true,
        'total_found' => intval($_POST['total']),
        'migrated' => intval($_POST['migrated']),
        'errors' => array(),
        'last_run' => current_time('mysql')
    ));

    wp_send_json_success('Migration completed');
}

/**
 * AJAX: Reset migration status (allows re-running after a prior completion)
 */
add_action('wp_ajax_pm_reset_migration', 'pm_reset_migration_callback');
function pm_reset_migration_callback()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    update_option('amelia_photo_migration_status', array(
        'completed' => false,
        'total_found' => 0,
        'migrated' => 0,
        'errors' => array(),
        'last_run' => null
    ));
    
    wp_send_json_success('Migration status reset');
}

/**
 * ==========================================
 * AI LESION CHANGE DETECTION
 * GPT-4o Vision integration for comparing photos
 * @since 9.7.0
 * ==========================================
 */

/**
 * AJAX: Analyze lesion changes with GPT-4o Vision
 */
add_action('wp_ajax_hp_ai_analyze_lesions', 'hp_ai_analyze_lesions_callback');
function hp_ai_analyze_lesions_callback()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    $photo_urls = isset($_POST['photo_urls']) ? $_POST['photo_urls'] : array();
    $photo_dates = isset($_POST['photo_dates']) ? $_POST['photo_dates'] : array();
    
    if (count($photo_urls) < 2) {
        wp_send_json_error('Please select at least 2 photos to compare');
    }

    // Get OpenAI API key from settings
    $api_key = get_option('openai_api_key', '');
    if (empty($api_key)) {
        wp_send_json_error('OpenAI API key not configured. Please add it in plugin settings.');
    }

    // Build the image content array for GPT-4o Vision
    $image_content = array();
    
    // Add system context
    $prompt = "You are a dermatology AI assistant helping clinicians track skin lesion changes over time. ";
    $prompt .= "Analyze the following " . count($photo_urls) . " photos of the same skin lesion taken at different times.\n\n";
    
    // Add date context
    $prompt .= "Photo dates (oldest to newest):\n";
    for ($i = 0; $i < count($photo_dates); $i++) {
        $prompt .= "- Photo " . ($i + 1) . ": " . $photo_dates[$i] . "\n";
    }
    
    $prompt .= "\nPlease analyze and provide:\n";
    $prompt .= "1. **Size Changes**: Has the lesion grown, shrunk, or stayed the same?\n";
    $prompt .= "2. **Color Changes**: Any changes in pigmentation, uniformity, or new colors?\n";
    $prompt .= "3. **Border Changes**: Are the borders becoming more or less defined/irregular?\n";
    $prompt .= "4. **Shape Changes**: Any asymmetry changes or shape evolution?\n";
    $prompt .= "5. **Surface Changes**: Changes in texture, elevation, or surface features?\n";
    $prompt .= "6. **ABCDE Assessment**: Brief assessment using the ABCDE criteria (Asymmetry, Border, Color, Diameter, Evolution)\n";
    $prompt .= "7. **Risk Assessment**: Low/Medium/High concern level with reasoning\n";
    $prompt .= "8. **Recommendation**: Clinical recommendation (monitor, follow-up timeframe, or urgent referral)\n\n";
    $prompt .= "Format your response with clear headers and bullet points. Be concise but thorough.";

    // Build messages array with images
    $messages = array(
        array(
            'role' => 'user',
            'content' => array()
        )
    );

    // Add text prompt first
    $messages[0]['content'][] = array(
        'type' => 'text',
        'text' => $prompt
    );

    // Add each image
    foreach ($photo_urls as $index => $url) {
        // For local URLs, we need to convert to base64
        if (strpos($url, 'http') === 0) {
            // Try to get image content
            $image_data = @file_get_contents($url);
            if ($image_data) {
                $base64 = base64_encode($image_data);
                $mime_type = 'image/jpeg'; // Default
                
                if (strpos($url, '.png') !== false) {
                    $mime_type = 'image/png';
                } elseif (strpos($url, '.webp') !== false) {
                    $mime_type = 'image/webp';
                }
                
                $messages[0]['content'][] = array(
                    'type' => 'image_url',
                    'image_url' => array(
                        'url' => 'data:' . $mime_type . ';base64,' . $base64,
                        'detail' => 'high'
                    )
                );
            }
        }
    }

    // Call OpenAI GPT-4o Vision API
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
        'timeout' => 120,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode(array(
            'model' => 'gpt-4o',
            'messages' => $messages,
            'max_tokens' => 2000,
            'temperature' => 0.3
        ))
    ));

    if (is_wp_error($response)) {
        wp_send_json_error('API request failed: ' . $response->get_error_message());
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body['error'])) {
        wp_send_json_error('OpenAI error: ' . $body['error']['message']);
    }

    if (!isset($body['choices'][0]['message']['content'])) {
        wp_send_json_error('Invalid response from OpenAI');
    }

    $analysis = $body['choices'][0]['message']['content'];

    // Parse risk level from response
    $risk_level = 'unknown';
    if (stripos($analysis, 'high concern') !== false || stripos($analysis, 'high risk') !== false) {
        $risk_level = 'high';
    } elseif (stripos($analysis, 'medium concern') !== false || stripos($analysis, 'moderate') !== false) {
        $risk_level = 'medium';
    } elseif (stripos($analysis, 'low concern') !== false || stripos($analysis, 'low risk') !== false) {
        $risk_level = 'low';
    }

    wp_send_json_success(array(
        'analysis' => $analysis,
        'risk_level' => $risk_level,
        'photos_analyzed' => count($photo_urls)
    ));
}

/**
 * AJAX: Save AI analysis to photo notes
 */
add_action('wp_ajax_hp_save_ai_analysis', 'hp_save_ai_analysis_callback');
function hp_save_ai_analysis_callback()
{
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    global $wpdb;
    $lesion_id = sanitize_text_field($_POST['lesion_id']);
    $analysis = sanitize_textarea_field($_POST['analysis']);
    $analysis_date = current_time('mysql');

    // Store analysis as a JSON object in the notes of the most recent photo
    $latest_photo = $wpdb->get_row($wpdb->prepare(
        "SELECT id, notes FROM {$wpdb->prefix}amelia_patient_photos 
         WHERE lesion_id = %s 
         ORDER BY upload_date DESC 
         LIMIT 1",
        $lesion_id
    ));

    if ($latest_photo) {
        $existing_notes = json_decode($latest_photo->notes, true);
        if (!is_array($existing_notes)) {
            $existing_notes = array('text' => $latest_photo->notes);
        }
        
        $existing_notes['ai_analysis'] = array(
            'content' => $analysis,
            'date' => $analysis_date,
            'analyzed_by' => get_current_user_id()
        );

        $wpdb->update(
            $wpdb->prefix . 'amelia_patient_photos',
            array('notes' => json_encode($existing_notes)),
            array('id' => $latest_photo->id)
        );

        wp_send_json_success('Analysis saved');
    } else {
        wp_send_json_error('No photos found for this lesion');
    }
}
