<?php
/**
 * Booking Slot Counter — Waitlist (v9.7.3)
 *
 * Adds a tiny lead-capture form inside every fully-booked panel rendered by
 * the Booking Slot Counter. Customers leave their name + email + the months
 * they'd like to be considered for; entries land in `wp_amelia_bc_waitlist`
 * for the doctor to review on a dedicated admin page.
 *
 * Public surface:
 *   - amelia_bc_waitlist_create_table()        Schema migration (idempotent)
 *   - amelia_bc_waitlist_render_form($svc_ids) HTML for the in-panel form
 *   - amelia_bc_waitlist_inline_assets()       <style> + <script> for the form
 *   - AJAX action `amelia_bc_join_waitlist`    Form submission handler
 *
 * Admin page lives at /app/admin/partials/booking-counter-waitlist.php.
 *
 * @since 9.7.3
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Allowed three-letter month abbreviations. Order matters — used as the
 * canonical sort order in CSV output and the admin table.
 */
function amelia_bc_waitlist_months() {
    return array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
}

/**
 * Allowed status values for a waitlist row. `new` is the default.
 */
function amelia_bc_waitlist_statuses() {
    return array('new', 'exported', 'contacted', 'booked');
}

/**
 * Idempotent schema migration. Safe to call on every admin_init.
 */
function amelia_bc_waitlist_create_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'amelia_bc_waitlist';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        service_id INT UNSIGNED NULL,
        service_name_cache VARCHAR(255) NULL,
        name VARCHAR(190) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(60) NULL,
        preferred_months VARCHAR(80) NOT NULL DEFAULT '',
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        notes TEXT NULL,
        ip_address VARCHAR(64) NULL,
        user_agent VARCHAR(255) NULL,
        source VARCHAR(20) NOT NULL DEFAULT 'frontend',
        created_utc DATETIME NOT NULL,
        updated_utc DATETIME NULL,
        PRIMARY KEY (id),
        KEY idx_service (service_id),
        KEY idx_status (status),
        KEY idx_created (created_utc),
        KEY idx_email (email)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
add_action('admin_init', 'amelia_bc_waitlist_create_table');

/**
 * Sanitize a user-supplied list of month abbreviations into a canonical CSV
 * (e.g. "Jan,Feb,Mar"). Returns '' when nothing valid is supplied.
 */
function amelia_bc_waitlist_normalize_months($input) {
    $valid = amelia_bc_waitlist_months();
    if (is_array($input)) {
        $list = $input;
    } else {
        $list = preg_split('/[,;\s]+/', (string) $input);
    }
    $picked = array();
    foreach ($list as $m) {
        $m = ucfirst(strtolower(trim($m)));
        if ($m !== '' && in_array($m, $valid, true) && !in_array($m, $picked, true)) {
            $picked[] = $m;
        }
    }
    // Re-sort by canonical month order
    $sorted = array();
    foreach ($valid as $canon) {
        if (in_array($canon, $picked, true)) $sorted[] = $canon;
    }
    return implode(',', $sorted);
}

/**
 * Render the waitlist form HTML. Service IDs are encoded as a comma-separated
 * string so the AJAX handler can save the (first) service for analytics.
 */
function amelia_bc_waitlist_render_form($service_ids = array()) {
    $service_ids = array_map('intval', (array) $service_ids);
    $service_ids = array_values(array_filter($service_ids));
    $months = amelia_bc_waitlist_months();
    $svc_attr = esc_attr(implode(',', $service_ids));
    $nonce    = wp_create_nonce('amelia_bc_waitlist');
    $ajax_url = esc_url(admin_url('admin-ajax.php'));
    ob_start();
    ?>
    <form class="amelia-bc-waitlist-form" data-bc-services="<?php echo $svc_attr; ?>"
          data-bc-ajax="<?php echo $ajax_url; ?>" data-bc-nonce="<?php echo esc_attr($nonce); ?>"
          autocomplete="on" novalidate>
        <h4 class="amelia-bc-waitlist-title">Join the waitlist</h4>
        <p class="amelia-bc-waitlist-sub">We'll get in touch when slots open up for the months you choose.</p>
        <div class="amelia-bc-waitlist-row">
            <label>Name<span aria-hidden="true">*</span>
                <input type="text" name="name" required maxlength="190" autocomplete="name">
            </label>
        </div>
        <div class="amelia-bc-waitlist-row">
            <label>Email<span aria-hidden="true">*</span>
                <input type="email" name="email" required maxlength="190" autocomplete="email">
            </label>
        </div>
        <div class="amelia-bc-waitlist-row">
            <span class="amelia-bc-waitlist-label">Preferred month(s)<span aria-hidden="true">*</span></span>
            <div class="amelia-bc-waitlist-months" role="group" aria-label="Preferred months">
                <?php foreach ($months as $m): ?>
                    <label class="amelia-bc-waitlist-month">
                        <input type="checkbox" name="preferred_months[]" value="<?php echo esc_attr($m); ?>">
                        <span><?php echo esc_html($m); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <small class="amelia-bc-waitlist-hint">Pick one or more.</small>
        </div>
        <div class="amelia-bc-waitlist-row amelia-bc-waitlist-actions">
            <button type="submit" class="amelia-bc-waitlist-submit">Add me to the waitlist</button>
        </div>
        <div class="amelia-bc-waitlist-msg" role="status" aria-live="polite"></div>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * Inline CSS + JS for the waitlist form. Outputted once per page next to the
 * panel to avoid an extra HTTP request.
 */
function amelia_bc_waitlist_inline_assets() {
    static $done = false;
    if ($done) return '';
    $done = true;
    ob_start();
    ?>
    <style id="amelia-bc-waitlist-styles">
        .amelia-bc-waitlist-form { margin-top: 14px; padding: 14px 0 4px 0; border-top: 1px dashed #E8829A; font-family: "Quicksand", -apple-system, sans-serif; color: #5A4A5A; text-align: center; }
        .amelia-bc-waitlist-title { font-size: 15px; font-weight: 700; margin: 0 0 4px 0; color: #842029; text-align: center; }
        .amelia-bc-waitlist-sub { font-size: 13px; margin: 0 0 12px 0; color: #5A4A5A; text-align: center; }
        .amelia-bc-waitlist-row { margin-bottom: 10px; text-align: center; }
        .amelia-bc-waitlist-row label { display: block; font-size: 13px; font-weight: 600; text-align: center; }
        .amelia-bc-waitlist-row input[type="text"], .amelia-bc-waitlist-row input[type="email"] {
            width: 100%; box-sizing: border-box; margin-top: 4px; padding: 8px 10px; border: 2px solid #FFE4EC; border-radius: 8px; background: #FFF9F5; font-size: 14px; color: #5A4A5A; font-family: inherit; text-align: center;
        }
        .amelia-bc-waitlist-row input[type="text"]:focus, .amelia-bc-waitlist-row input[type="email"]:focus { outline: none; border-color: #98D9C2; background: #fff; }
        .amelia-bc-waitlist-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; text-align: center; }
        .amelia-bc-waitlist-months { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; }
        .amelia-bc-waitlist-month { display: inline-flex; align-items: center; gap: 5px; padding: 5px 10px; background: #FFF9F5; border: 2px solid #FFE4EC; border-radius: 8px; font-size: 12.5px; font-weight: 600; cursor: pointer; user-select: none; width: auto; flex: 0 0 auto; }
        .amelia-bc-waitlist-month input { margin: 0; cursor: pointer; }
        .amelia-bc-waitlist-month:hover { border-color: #98D9C2; }
        .amelia-bc-waitlist-month.is-on { background: #D4F1E8; border-color: #5FBDA0; color: #2C6B58; }
        .amelia-bc-waitlist-hint { display: block; margin-top: 4px; color: #8A7A8A; font-size: 12px; text-align: center; }
        .amelia-bc-waitlist-actions { text-align: center; }
        .amelia-bc-waitlist-submit { display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; background: linear-gradient(135deg, #98D9C2 0%, #5FBDA0 100%); color: white; font-family: inherit; font-weight: 700; font-size: 13.5px; border-radius: 22px; border: none; cursor: pointer; box-shadow: 0 4px 14px rgba(152, 217, 194, 0.35); }
        .amelia-bc-waitlist-submit:hover { transform: translateY(-1px); }
        .amelia-bc-waitlist-submit[disabled] { opacity: 0.6; cursor: progress; transform: none; }
        .amelia-bc-waitlist-msg { margin-top: 10px; font-size: 13px; min-height: 1em; text-align: center; }
        .amelia-bc-waitlist-msg.is-success { color: #2C6B58; font-weight: 600; }
        .amelia-bc-waitlist-msg.is-error { color: #842029; font-weight: 600; }
    </style>
    <script id="amelia-bc-waitlist-script">
    (function () {
        function bind(form) {
            if (!form || form.__bcWaitlistBound) return;
            form.__bcWaitlistBound = true;

            // Visual on/off for month checkboxes
            form.querySelectorAll('.amelia-bc-waitlist-month input').forEach(function (chk) {
                chk.addEventListener('change', function () {
                    chk.closest('.amelia-bc-waitlist-month').classList.toggle('is-on', chk.checked);
                });
            });

            form.addEventListener('submit', function (ev) {
                ev.preventDefault();
                var msg = form.querySelector('.amelia-bc-waitlist-msg');
                msg.className = 'amelia-bc-waitlist-msg';
                msg.textContent = '';

                var name = (form.querySelector('input[name="name"]').value || '').trim();
                var email = (form.querySelector('input[name="email"]').value || '').trim();
                var months = Array.prototype.map.call(
                    form.querySelectorAll('input[name="preferred_months[]"]:checked'),
                    function (n) { return n.value; }
                );

                if (!name) { msg.classList.add('is-error'); msg.textContent = 'Please enter your name.'; return; }
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { msg.classList.add('is-error'); msg.textContent = 'Please enter a valid email.'; return; }
                if (!months.length) { msg.classList.add('is-error'); msg.textContent = 'Please pick at least one preferred month.'; return; }

                var submit = form.querySelector('.amelia-bc-waitlist-submit');
                submit.disabled = true;
                var origText = submit.textContent;
                submit.textContent = 'Adding...';

                var fd = new FormData();
                fd.append('action', 'amelia_bc_join_waitlist');
                fd.append('nonce', form.getAttribute('data-bc-nonce'));
                fd.append('name', name);
                fd.append('email', email);
                fd.append('preferred_months', months.join(','));
                fd.append('service_ids', form.getAttribute('data-bc-services') || '');

                fetch(form.getAttribute('data-bc-ajax'), {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: fd
                }).then(function (r) { return r.json(); }).then(function (j) {
                    if (j && j.success) {
                        form.innerHTML = '<div class="amelia-bc-waitlist-msg is-success" role="status">' +
                            (j.data && j.data.message ? j.data.message : "You're on the list — we'll be in touch soon.") +
                            '</div>';
                    } else {
                        msg.classList.add('is-error');
                        msg.textContent = (j && j.data && j.data.message) ? j.data.message : 'Something went wrong, please try again.';
                        submit.disabled = false; submit.textContent = origText;
                    }
                }).catch(function () {
                    msg.classList.add('is-error');
                    msg.textContent = 'Network error — please try again.';
                    submit.disabled = false; submit.textContent = origText;
                });
            });
        }
        function bindAll() {
            document.querySelectorAll('.amelia-bc-waitlist-form').forEach(bind);
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindAll);
        } else {
            bindAll();
        }
        if (window.MutationObserver) {
            try {
                new MutationObserver(bindAll).observe(document.body, { childList: true, subtree: true });
            } catch (e) {}
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Frontend AJAX handler — saves a waitlist signup.
 */
add_action('wp_ajax_amelia_bc_join_waitlist',        'amelia_bc_waitlist_ajax');
add_action('wp_ajax_nopriv_amelia_bc_join_waitlist', 'amelia_bc_waitlist_ajax');
function amelia_bc_waitlist_ajax() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amelia_bc_waitlist')) {
        wp_send_json_error(array('message' => 'Security check failed — please refresh the page and try again.'));
    }

    $name   = isset($_POST['name'])  ? sanitize_text_field(wp_unslash($_POST['name']))  : '';
    $email  = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email']))      : '';
    $months_raw = isset($_POST['preferred_months']) ? wp_unslash($_POST['preferred_months']) : '';
    $months = amelia_bc_waitlist_normalize_months($months_raw);
    $svc_raw = isset($_POST['service_ids']) ? sanitize_text_field(wp_unslash($_POST['service_ids'])) : '';

    if ($name === '')                  wp_send_json_error(array('message' => 'Please enter your name.'));
    if (!is_email($email))             wp_send_json_error(array('message' => 'Please enter a valid email.'));
    if ($months === '')                wp_send_json_error(array('message' => 'Please pick at least one preferred month.'));

    // Service id (first one, used for analytics + service_name_cache)
    $service_id = 0;
    foreach (preg_split('/[,\s]+/', $svc_raw) as $part) {
        $sid = intval($part);
        if ($sid > 0) { $service_id = $sid; break; }
    }
    $service_name = '';
    if ($service_id > 0) {
        global $wpdb;
        $services = $wpdb->prefix . 'amelia_services';
        if ($wpdb->get_var("SHOW TABLES LIKE '$services'") === $services) {
            $service_name = (string) $wpdb->get_var($wpdb->prepare(
                "SELECT name FROM $services WHERE id = %d", $service_id
            ));
        }
    }

    // Cheap rate-limit: same email+ip in last 60s → reject.
    $ip = isset($_SERVER['REMOTE_ADDR']) ? substr((string) $_SERVER['REMOTE_ADDR'], 0, 64) : '';
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
    global $wpdb;
    $table = $wpdb->prefix . 'amelia_bc_waitlist';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
        amelia_bc_waitlist_create_table();
    }
    $recent = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE email = %s AND created_utc > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 60 SECOND)",
        $email
    ));
    if ((int) $recent > 0) {
        wp_send_json_success(array('message' => "You're already on the list — we'll be in touch soon."));
    }

    $ok = $wpdb->insert($table, array(
        'service_id'         => $service_id ?: null,
        'service_name_cache' => $service_name ?: null,
        'name'               => $name,
        'email'              => $email,
        'preferred_months'   => $months,
        'status'             => 'new',
        'notes'              => null,
        'ip_address'         => $ip ?: null,
        'user_agent'         => $ua ?: null,
        'source'             => 'frontend',
        'created_utc'        => current_time('mysql', true),
    ));

    if (!$ok) {
        wp_send_json_error(array('message' => 'Could not save right now — please try again in a minute.'));
    }

    // Customer confirmation email — best-effort, non-blocking.
    amelia_bc_waitlist_send_confirmation($email, $name, $service_name, $months);

    wp_send_json_success(array(
        'message' => "You're on the waitlist — we'll email " . $email . " when slots for your preferred months open up.",
    ));
}

/**
 * Customer-facing "you're on the waitlist" confirmation email.
 * Best-effort — failures (SMTP / wp_mail issues) are logged but never block
 * the signup flow. Returns true on send success, false otherwise.
 */
function amelia_bc_waitlist_send_confirmation($email, $name, $service_name, $months_csv) {
    if (!is_email($email)) return false;

    $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    $admin_email = get_option('admin_email');

    $subject = '[' . $site_name . "] You're on the waitlist";

    $service_line = $service_name
        ? '<strong>' . esc_html($service_name) . '</strong>'
        : 'your selected service';

    $months_pretty = $months_csv
        ? str_replace(',', ', ', $months_csv)
        : '';

    $body = '<div style="font-family: Arial, Helvetica, sans-serif; color: #333; line-height: 1.55; max-width: 560px;">'
        . '<p>Hi ' . esc_html($name) . ',</p>'
        . '<p>Thanks for joining the waitlist for ' . $service_line . '. We\'ve got you down.</p>';
    if ($months_pretty !== '') {
        $body .= '<p><strong>Preferred month(s):</strong> ' . esc_html($months_pretty) . '</p>';
    }
    $body .= '<p>We\'ll be in touch by email as soon as new slots open up that match what you picked. '
          . 'No further action needed from you for now.</p>'
          . '<p>Talk soon,<br>The ' . esc_html($site_name) . ' team</p>'
          . '<hr style="border: none; border-top: 1px solid #eee; margin: 18px 0;">'
          . '<p style="font-size: 11px; color: #999;">If this email landed in your inbox by mistake, you can safely ignore it — we\'ll never use your address for anything other than letting you know about open slots.</p>'
          . '</div>';

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <' . $admin_email . '>',
    );

    $sent = wp_mail($email, $subject, $body, $headers);
    if (!$sent && function_exists('error_log')) {
        error_log('[amelia_bc_waitlist] wp_mail failed for ' . $email);
    }
    return (bool) $sent;
}
