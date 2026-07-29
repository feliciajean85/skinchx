<?php
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_script('amelia-login-helper-js', WPAMELIA_ADDON_PLUGIN_URL . 'form-autocomplete/amelia-login-helper.js', ['jquery'], '1.0', true);
  wp_enqueue_style('custom-style', WPAMELIA_ADDON_PLUGIN_URL . 'form-autocomplete/loader.css');
  wp_localize_script('amelia-login-helper-js', 'ameliaLoginHelper', [
    'ajax_url'   => admin_url('admin-ajax.php'),
    'nonce'      => wp_create_nonce('amelia_login_nonce'),
    'is_logged'  => is_user_logged_in(),
  ]);
});

// === AJAX HANDLERS ===

/** AJAX: check if email exists (customer/user) */
add_action('wp_ajax_nopriv_amelia_check_customer', 'amelia_check_customer');
add_action('wp_ajax_amelia_check_customer', 'amelia_check_customer');
function amelia_check_customer()
{
  check_ajax_referer('amelia_login_nonce', 'nonce');
  $email = sanitize_email($_POST['email'] ?? '');
  if (empty($email) || !is_email($email)) {
    wp_send_json_error(['message' => 'Invalid email']);
  }
  $user = get_user_by('email', $email);
  if ($user) {
    wp_send_json_success(['exists' => true, 'display_name' => $user->display_name]);
  }
  wp_send_json_success(['exists' => false]);
}

/** AJAX: login */

add_action('wp_ajax_amelia_user_details', 'amelia_user_details');
function amelia_user_details()
{
  $service_id = (int)($_POST['service'] ?? '');
  $user_id = get_current_user_id();

  $data = get_appoinment_booking_details_service_wise_latest($service_id, $user_id);
  wp_send_json_success(['details' => $data, 'userdata' => $user_id]);
}

add_action('wp_ajax_nopriv_amelia_user_login', 'amelia_user_login');
function amelia_user_login()
{
    $email    = sanitize_email($_POST['email'] ?? '');
  $password = (string)($_POST['password'] ?? '');
  $service_id = (int)($_POST['service'] ?? '');
  if(is_user_logged_in()){
    $serviceDetails = get_appoinment_booking_details_service_wise_latest($service_id, get_current_user_id());
  wp_send_json_success(['message' => 'Login successful', 'details' => $serviceDetails, 'userdata' => $user_data]);
  }
  check_ajax_referer('amelia_login_nonce', 'nonce');

  if (!$email || !$password) wp_send_json_error(['message' => 'Email and password are required']);

  $user = wp_signon([
    'user_login'    => $email,
    'user_password' => $password,
    'remember'      => true,
  ]);
  if (is_wp_error($user)) {
    wp_send_json_error(['message' => 'Invalid email or password']);
  }
  $user_data['service_id'] = $service_id;
  $user_data['user_id'] = $user->ID;
  $serviceDetails = get_appoinment_booking_details_service_wise_latest($service_id, $user->ID);
  wp_send_json_success(['message' => 'Login successful', 'details' => $serviceDetails, 'userdata' => $user_data]);
}

/** AJAX: send password setup (lost password) link */
add_action('wp_ajax_nopriv_amelia_send_password_link', 'amelia_send_password_link');
function amelia_send_password_link()
{
  check_ajax_referer('amelia_login_nonce', 'nonce');
  $email = sanitize_email($_POST['email'] ?? '');
  if (!$email || !is_email($email)) wp_send_json_error(['message' => 'Enter a valid email']);
  $user = get_user_by('email', $email);
  if (!$user) wp_send_json_error(['message' => 'No user found with that email']);

  $key = get_password_reset_key($user);
  if (is_wp_error($key)) wp_send_json_error(['message' => 'Could not generate reset key']);

  $reset_url = wp_lostpassword_url() . (strpos(wp_lostpassword_url(), '?') === false ? '?' : '&')
    . 'key=' . rawurlencode($key) . '&login=' . rawurlencode($user->user_login);

  // You can customize the email template here
  wp_mail($email, 'Set Your Password', "Click to set your password:\n\n$reset_url");

  wp_send_json_success(['message' => 'Password setup link sent to your email']);
}

function amelia_email_credentials($email)
{
  if (empty($email) || !is_email($email)) {
    return new WP_Error('invalid_email', 'Invalid email');
  }

  // Try to find user
  $user = get_user_by('email', $email);

  // If no user, create one
  if (!$user) {
    // Prefer WooCommerce customer if available
    if (function_exists('wc_create_new_customer')) {
      $password = wp_generate_password(12, true);
      $user_id  = wc_create_new_customer($email, '', $password);
      if (is_wp_error($user_id)) return $user_id;
      $user = get_user_by('id', $user_id);
    } else {
      // Create username from email local-part; ensure uniqueness
      $base = sanitize_user(current(explode('@', $email)), true);
      if ($base === '') $base = 'user';
      $username = $base;
      $i = 1;
      while (username_exists($username)) {
        $username = $base . $i++;
      }
      $password = wp_generate_password(12, true);
      $user_id = wp_insert_user([
        'user_login' => $username,
        'user_pass'  => $password,
        'user_email' => $email,
        'role'       => 'subscriber',
      ]);
      if (is_wp_error($user_id)) return $user_id;
      $user = get_user_by('id', $user_id);
      // If WooCommerce is active later, you can upgrade role
      if (class_exists('WooCommerce')) {
        $user->set_role('customer');
      }
    }
  } else {
    // User exists: set a new random password
    $password = wp_generate_password(12, true);
    reset_password($user, $password);
  }

  // Email credentials
  $login = $user->user_login;
  $site  = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
  $body  = "Hello,\n\nHere are your login details for {$site}:\n\nUsername: {$login}\nPassword: {$password}\n\n(For security, please change your password after logging in.)\n";
  wp_mail($email, "Your {$site} login details", $body);

  return ['username' => $login];
}

/** AJAX: send username + random password (create user if needed) */
add_action('wp_ajax_nopriv_amelia_send_username_password', 'amelia_send_username_password');
function amelia_send_username_password()
{
  check_ajax_referer('amelia_login_nonce', 'nonce');
  $email = sanitize_email($_POST['email'] ?? '');
  if (!$email || !is_email($email)) {
    wp_send_json_error(['message' => 'Please enter a valid email address.']);
  }

  $result = amelia_email_credentials($email);
  if (is_wp_error($result)) {
    wp_send_json_error(['message' => $result->get_error_message()]);
  }
  wp_send_json_success(['message' => 'An email has been sent with your username and password.']);
}
// In your theme's functions.php or a small mu-plugin
add_filter('do_shortcode_tag', function ($output, $tag, $attr, $m) {
  if ($tag !== 'ameliastepbooking') {
    return $output;
  }

  // Accept both serviceid="1" and service="1" just in case
  $service_id = isset($attr['service']) ? $attr['service'] : (isset($attr['service']) ? $attr['service'] : '');
  $service_id = sanitize_text_field($service_id);

  // Your custom hidden field (outside Amelia, so it’s yours to read)
  $hidden = sprintf(
    '<input type="hidden" name="amelia_service_id" class="amelia-service-id" value="%s" />',
    esc_attr($service_id)
  );

  // Wrap for easier DOM targeting
  return '<div class="ameliaform-wrap" data-service-id="' . esc_attr($service_id) . '">' . $hidden . $output . '</div>';
}, 10, 4);
