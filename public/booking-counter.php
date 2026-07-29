<?php
/**
 * Amelia Booking Counter (v9.7.2 — manual total mode)
 *
 * Each service can be configured with a manual "Total upcoming slots" value
 * representing how many bookable slots the admin has planned out ahead. The
 * addon counts how many appointments already exist in `wp_amelia_appointments`
 * for that service in the future, then displays:
 *
 *   "X of Y slots booked — Z available"
 *
 * When booked >= total, OR the per-service "Mark as Fully Booked" toggle is
 * on, the configurable waitlist message is shown instead.
 *
 * Auto-injection covers [ameliabooking] and [ameliastepbooking]. Per-page
 * opt-out via hide_counter="1". Manual placement: [amelia_booking_full_message].
 *
 * @since 9.7.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Purge any popular page-cache plugin so the Booking Slot Counter
 * reflects fresh totals immediately after the admin saves settings.
 *
 * Hooked from booking-counter-settings.php right after each
 * update_option() call. Idempotent — safe to fire multiple times.
 *
 * Covers: LiteSpeed Cache, WP Rocket, W3 Total Cache, WP Super Cache,
 * SG Optimizer (SiteGround), Cloudflare-via-plugin, and the WP object cache.
 */
function amelia_bc_purge_caches() {
    $purged = array();

    // LiteSpeed Cache (current versions use action hooks)
    if (defined('LSCWP_V') || class_exists('LiteSpeed\Core') || class_exists('LiteSpeed_Cache_API')) {
        do_action('litespeed_purge_all');
        if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all')) {
            LiteSpeed_Cache_API::purge_all();
        }
        $purged[] = 'LiteSpeed';
    }

    // WP Rocket
    if (function_exists('rocket_clean_domain')) {
        rocket_clean_domain();
        $purged[] = 'WP Rocket';
    }

    // W3 Total Cache
    if (function_exists('w3tc_pgcache_flush')) {
        w3tc_pgcache_flush();
        $purged[] = 'W3 Total Cache';
    }

    // WP Super Cache
    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
        $purged[] = 'WP Super Cache';
    }

    // SG Optimizer (SiteGround)
    if (function_exists('sg_cachepress_purge_cache')) {
        sg_cachepress_purge_cache();
        $purged[] = 'SG Optimizer';
    }

    // Cloudflare via official plugin
    if (has_action('cloudflare_purge_everything')) {
        do_action('cloudflare_purge_everything');
        $purged[] = 'Cloudflare';
    }

    // Generic WordPress object cache
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }

    return $purged;
}

/**
 * Resolve a list of integer service IDs from a shortcode args array.
 */
function amelia_bc_resolve_service_ids($args) {
    global $wpdb;
    $services_table = $wpdb->prefix . 'amelia_services';
    $service_ids = array();

    $svc_keys = array('service', 'services', 'service_ids');
    foreach ($svc_keys as $k) {
        if (!empty($args[$k])) {
            foreach (preg_split('/[,\s]+/', (string) $args[$k]) as $part) {
                $id = intval($part);
                if ($id > 0) $service_ids[] = $id;
            }
        }
    }

    $cat_keys = array('category', 'categories', 'category_id', 'category_ids');
    $cat_ids = array();
    foreach ($cat_keys as $k) {
        if (!empty($args[$k])) {
            foreach (preg_split('/[,\s]+/', (string) $args[$k]) as $part) {
                $id = intval($part);
                if ($id > 0) $cat_ids[] = $id;
            }
        }
    }

    if (!empty($cat_ids) && $wpdb->get_var("SHOW TABLES LIKE '$services_table'") === $services_table) {
        $placeholders = implode(',', array_fill(0, count($cat_ids), '%d'));
        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM $services_table WHERE categoryId IN ($placeholders) AND status = 'visible'",
            ...$cat_ids
        ));
        foreach ($rows as $r) $service_ids[] = intval($r);
    }

    return array_values(array_unique(array_filter($service_ids)));
}

/**
 * Count how many appointments are already booked in the future for the
 * given service IDs. Excludes canceled / rejected appointments.
 *
 * Returns an int; zero when no service IDs are supplied or the table
 * is missing.
 */
function amelia_bc_count_booked($service_ids) {
    global $wpdb;
    $appts = $wpdb->prefix . 'amelia_appointments';

    if (empty($service_ids)) return 0;
    if ($wpdb->get_var("SHOW TABLES LIKE '$appts'") !== $appts) return 0;

    $now_utc = gmdate('Y-m-d H:i:s');
    $placeholders = implode(',', array_fill(0, count($service_ids), '%d'));

    $sql_args = array_merge(array($now_utc), $service_ids);
    $count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*)
         FROM $appts a
         WHERE a.bookingStart >= %s
           AND a.serviceId IN ($placeholders)
           AND (a.status IS NULL OR a.status NOT IN ('canceled', 'rejected'))",
        ...$sql_args
    ));

    return $count;
}

/**
 * Sum the configured "Total upcoming slots" across the given service IDs.
 * Services without a configured total contribute 0.
 */
function amelia_bc_sum_total_slots($service_ids) {
    if (empty($service_ids)) return 0;
    $totals = (array) get_option('amelia_bc_total_slots', array());
    $sum = 0;
    foreach ($service_ids as $sid) {
        if (!empty($totals[$sid]) && intval($totals[$sid]) > 0) {
            $sum += intval($totals[$sid]);
        }
    }
    return $sum;
}

/**
 * Look up service names for display.
 */
function amelia_bc_get_service_names($service_ids) {
    global $wpdb;
    $services = $wpdb->prefix . 'amelia_services';
    if (empty($service_ids) || $wpdb->get_var("SHOW TABLES LIKE '$services'") !== $services) {
        return array();
    }
    $placeholders = implode(',', array_fill(0, count($service_ids), '%d'));
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name FROM $services WHERE id IN ($placeholders)",
        ...$service_ids
    ));
    $out = array();
    foreach ($rows as $r) $out[intval($r->id)] = $r->name;
    return $out;
}

/**
 * Per-service "Mark as Fully Booked" toggle is on for at least one
 * targeted service?
 */
function amelia_bc_manual_full_on($service_ids) {
    $per_service = (array) get_option('amelia_bc_full_toggles', array());
    foreach ($service_ids as $sid) {
        if (!empty($per_service[$sid])) return true;
    }
    return false;
}

/**
 * Returns true when EVERY targeted service has either no decay date set OR
 * its decay date is still in the future. If at least one service's decay
 * date has passed, treat the whole panel as expired.
 */
function amelia_bc_decay_expired($service_ids) {
    if (empty($service_ids)) return false;
    $decay_dates = (array) get_option('amelia_bc_decay_dates', array());
    if (empty($decay_dates)) return false;

    try {
        $today = new DateTime('now', wp_timezone());
        $today->setTime(0, 0, 0);
    } catch (Exception $e) {
        return false;
    }

    foreach ($service_ids as $sid) {
        $d = isset($decay_dates[$sid]) ? trim((string) $decay_dates[$sid]) : '';
        if ($d === '') continue;
        try {
            $dt = new DateTime($d . ' 23:59:59', wp_timezone());
            if ($dt < $today) return true; // any service with passed decay → expired
        } catch (Exception $e) { /* ignore */ }
    }
    return false;
}

/**
 * Resolve the "fully booked" message — per-service override > default.
 */
function amelia_bc_resolve_full_message($service_ids) {
    $per_service = get_option('amelia_bc_full_messages', array());
    if (!is_array($per_service)) $per_service = array();

    foreach ($service_ids as $sid) {
        if (!empty($per_service[$sid])) {
            return $per_service[$sid];
        }
    }
    return get_option(
        'amelia_bc_full_message_default',
        'Sorry — every upcoming session is fully booked right now. Please check back soon or contact us to be added to the waitlist.'
    );
}

/**
 * Resolve the counter-label template — per-service override > default.
 * Variables: {booked}, {total}, {remaining}, {service}.
 */
function amelia_bc_resolve_counter_label($service_ids, $stats) {
    $per_service = get_option('amelia_bc_counter_labels', array());
    if (!is_array($per_service)) $per_service = array();

    $template = '';
    foreach ($service_ids as $sid) {
        if (!empty($per_service[$sid])) { $template = $per_service[$sid]; break; }
    }
    if ($template === '') {
        $template = get_option(
            'amelia_bc_counter_label_default',
            '{booked} of {total} slots booked — {remaining} available'
        );
    }

    $service_label = '';
    if (!empty($stats['service_names'])) {
        $service_label = implode(', ', $stats['service_names']);
    }

    return strtr($template, array(
        '{booked}'    => (string) $stats['booked'],
        '{total}'     => (string) $stats['total_slots'],
        '{remaining}' => (string) $stats['remaining'],
        '{service}'   => $service_label,
    ));
}

/**
 * Render the panel HTML for a given set of service IDs.
 * Returns empty string when there's nothing to show.
 */
function amelia_bc_render_html($service_ids = array(), $opts = array()) {
    $force_full = !empty($opts['force_full']);
    $manual_full = amelia_bc_manual_full_on($service_ids);
    $decay_expired = amelia_bc_decay_expired($service_ids);
    $total_slots = amelia_bc_sum_total_slots($service_ids);

    // Force-full path (manual toggle, manual shortcode override, decay date passed)
    if ($force_full || $manual_full || $decay_expired) {
        return amelia_bc_render_full_message($service_ids);
    }

    if ($total_slots <= 0) {
        // Nothing to show — admin hasn't configured a total for any of the
        // targeted services and hasn't manually marked them full.
        return '';
    }

    $booked = amelia_bc_count_booked($service_ids);
    $remaining = max(0, $total_slots - $booked);

    if ($booked >= $total_slots) {
        return amelia_bc_render_full_message($service_ids);
    }

    $stats = array(
        'booked' => $booked,
        'total_slots' => $total_slots,
        'remaining' => $remaining,
        'service_names' => amelia_bc_get_service_names($service_ids),
    );

    $bar_pct = $total_slots > 0 ? min(100, round(($booked / $total_slots) * 100)) : 0;
    $label = amelia_bc_resolve_counter_label($service_ids, $stats);

    ob_start();
    ?>
    <div class="amelia-bc-panel"
         data-bc-services="<?php echo esc_attr(implode(',', $service_ids)); ?>"
         role="status" aria-live="polite">
        <div class="amelia-bc-counter">
            <div class="amelia-bc-bar"><span style="width: <?php echo intval($bar_pct); ?>%;"></span></div>
            <div class="amelia-bc-meta">
                <strong><?php echo intval($booked); ?></strong>
                <span> / </span>
                <strong><?php echo intval($total_slots); ?></strong>
                <span class="amelia-bc-meta-suffix"> slots booked &middot; <strong><?php echo intval($remaining); ?></strong> available</span>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function amelia_bc_render_full_message($service_ids) {
    $message = amelia_bc_resolve_full_message($service_ids);
    $waitlist_html = function_exists('amelia_bc_waitlist_render_form')
        ? amelia_bc_waitlist_render_form($service_ids)
        : '';
    $waitlist_assets = function_exists('amelia_bc_waitlist_inline_assets')
        ? amelia_bc_waitlist_inline_assets()
        : '';
    ob_start();
    ?>
    <div class="amelia-bc-panel amelia-bc-full"
         data-bc-services="<?php echo esc_attr(implode(',', $service_ids)); ?>"
         role="status" aria-live="polite">
        <div class="amelia-bc-full-msg">
            <span class="amelia-bc-full-icon" aria-hidden="true">⚠</span>
            <?php echo wp_kses_post($message); ?>
        </div>
        <?php echo $waitlist_html; ?>
    </div>
    <?php echo $waitlist_assets; ?>
    <?php
    return ob_get_clean();
}

function amelia_bc_inject_styles() {
    static $done = false;
    if ($done) return '';
    $done = true;
    return '<style id="amelia-bc-styles">
        .amelia-bc-panel { box-sizing: border-box !important; width: 50vw !important; max-width: 50vw !important; margin-left: auto !important; margin-right: auto !important; background: #FFF9F5; border: 2px solid #FFE4EC; border-radius: 12px; padding: 16px 20px; margin-top: 0; margin-bottom: 18px; font-family: "Quicksand", -apple-system, sans-serif; color: #5A4A5A; text-align: center; box-shadow: 0 2px 12px rgba(255,181,197,0.15); }
        @media (max-width: 768px) { .amelia-bc-panel { width: 90vw !important; max-width: 90vw !important; } }
        .amelia-bc-panel.amelia-bc-full { background: #FFF1F3; border-color: #E8829A; color: #842029; }
        .amelia-bc-counter { display: flex; flex-direction: column; gap: 8px; align-items: center; }
        .amelia-bc-bar { width: 100%; height: 10px; background: #FFE4EC; border-radius: 6px; overflow: hidden; }
        .amelia-bc-bar > span { display: block; height: 100%; background: linear-gradient(90deg, #98D9C2 0%, #5FBDA0 100%); transition: width 0.4s ease; }
        .amelia-bc-meta { font-size: 13.5px; color: #8A7A8A; text-align: center; }
        .amelia-bc-meta strong { color: #5FBDA0; font-size: 16px; }
        .amelia-bc-full-msg { display: flex; align-items: center; justify-content: center; gap: 12px; font-size: 14.5px; line-height: 1.5; font-weight: 500; text-align: center; }
        .amelia-bc-full-icon { font-size: 22px; line-height: 1; color: #E8829A; flex-shrink: 0; }
        .amelia-bc-injected-wrap { width: 100%; }
    </style>';
}

/**
 * Auto-inject above [ameliabooking] / [ameliastepbooking] shortcode output.
 *
 * This works for plain WP pages and Elementor's "Shortcode" widget. It does
 * NOT cover Amelia's own Elementor / Divi / Beaver widgets which mount the
 * Vue app directly without going through `do_shortcode()` — those are
 * handled by the JS DOM-injector at the bottom of this file.
 */
add_filter('pre_do_shortcode_tag', 'amelia_bc_pre_shortcode', 10, 4);
function amelia_bc_pre_shortcode($return, $tag, $attr, $m) {
    $supported_tags = array('ameliabooking', 'ameliastepbooking');
    if (!in_array($tag, $supported_tags, true)) return $return;
    if (!empty($attr['hide_counter'])) return $return;
    if ((int) get_option('amelia_bc_enabled', 1) !== 1) return $return;

    $service_ids = amelia_bc_resolve_service_ids((array) $attr);
    $panel_html = amelia_bc_render_html($service_ids);
    if ($panel_html === '') return $return;

    remove_filter('pre_do_shortcode_tag', 'amelia_bc_pre_shortcode', 10);
    $reconstructed = '[' . $tag;
    foreach ((array) $attr as $k => $v) {
        if (is_int($k)) {
            $reconstructed .= ' ' . esc_attr($v);
        } else {
            $reconstructed .= ' ' . $k . '="' . esc_attr($v) . '"';
        }
    }
    $reconstructed .= ']';
    $original_output = do_shortcode($reconstructed);
    add_filter('pre_do_shortcode_tag', 'amelia_bc_pre_shortcode', 10, 4);

    // Mark that the PHP-side injection already covered this page so the JS
    // injector can stand down and avoid double-rendering.
    if (!defined('AMELIA_BC_PHP_INJECTED')) {
        define('AMELIA_BC_PHP_INJECTED', true);
    }

    return amelia_bc_inject_styles() . $panel_html . $original_output;
}

/**
 * Resolve the default set of service IDs to use when no shortcode attributes
 * are present (e.g. Amelia's Elementor / Divi widget renders the calendar
 * directly without a shortcode). We return every service that has either a
 * configured Total upcoming slots > 0, a manual full toggle, or a decay date.
 */
function amelia_bc_default_service_ids() {
    $totals  = (array) get_option('amelia_bc_total_slots', array());
    $toggles = (array) get_option('amelia_bc_full_toggles', array());
    $decays  = (array) get_option('amelia_bc_decay_dates', array());

    $ids = array();
    foreach ($totals as $sid => $val)  if (intval($val) > 0)        $ids[] = intval($sid);
    foreach ($toggles as $sid => $val) if (!empty($val))            $ids[] = intval($sid);
    foreach ($decays as $sid => $val)  if (trim((string) $val) !== '') $ids[] = intval($sid);

    return array_values(array_unique(array_filter($ids)));
}

/**
 * JS-based DOM injector — runs on every frontend page. Finds Amelia mount
 * points (Vue containers from any embed method: shortcode, Elementor widget,
 * Divi widget, Beaver Builder, Gutenberg block) and prepends the booking
 * counter panel HTML directly before the first one it sees.
 *
 * The PHP shortcode filter above marks pages it already handled via the
 * AMELIA_BC_PHP_INJECTED constant so we don't render twice on the same page.
 */
add_action('wp_footer', 'amelia_bc_js_injector', 99);
function amelia_bc_js_injector() {
    if ((int) get_option('amelia_bc_enabled', 1) !== 1) return;
    if (defined('AMELIA_BC_PHP_INJECTED') && AMELIA_BC_PHP_INJECTED) return;

    $service_ids = amelia_bc_default_service_ids();
    $panel_html  = amelia_bc_render_html($service_ids);
    if ($panel_html === '') return;

    $payload = amelia_bc_inject_styles() . $panel_html;
    $payload_json = wp_json_encode($payload);
    if (!$payload_json) return;
    ?>
    <script id="amelia-bc-injector">
    (function () {
        var html = <?php echo $payload_json; ?>;
        // Selectors covering: shortcode mount, Elementor widget, Divi widget,
        // Beaver Builder, Gutenberg block. Amelia mounts on a div whose id
        // starts with "amelia-app-booking" or class contains "amelia-".
        var selectors = [
            '#amelia-app-booking',
            '#amelia-app-step-booking',
            '[id^="amelia-app-booking"]',
            '[id^="amelia-app-step-booking"]',
            '.amelia-frontend-container',
            '.amelia-app-booking',
            '.amelia-elementor-booking',
            '[data-amelia-booking]'
        ];
        var injected = false;

        function findTargets() {
            var found = [];
            for (var i = 0; i < selectors.length; i++) {
                var nodes = document.querySelectorAll(selectors[i]);
                for (var j = 0; j < nodes.length; j++) {
                    if (found.indexOf(nodes[j]) === -1) found.push(nodes[j]);
                }
            }
            return found;
        }

        function inject() {
            if (injected) return;
            var targets = findTargets();
            if (!targets.length) return;
            // Inject once before the OUTERMOST target only (avoid duplicates
            // when nested mount points exist).
            var top = targets[0];
            for (var i = 1; i < targets.length; i++) {
                if (targets[i].contains(top)) top = targets[i];
            }
            // Walk up to a top-level page section so the panel can break out
            // of narrow page-builder columns and centre on the viewport.
            // Stop at the first ancestor that's a direct child of <body> or <main>.
            var anchor = top;
            while (
                anchor.parentNode &&
                anchor.parentNode !== document.body &&
                anchor.parentNode.tagName !== 'MAIN' &&
                anchor.parentNode.nodeType === 1
            ) {
                anchor = anchor.parentNode;
            }
            var insertParent = anchor.parentNode || document.body;

            // Skip if we've already injected an amelia-bc-panel right above it.
            var prev = anchor.previousElementSibling;
            if (prev && prev.classList && (prev.classList.contains('amelia-bc-injected-wrap') || prev.classList.contains('amelia-bc-panel'))) {
                injected = true;
                return;
            }
            var wrap = document.createElement('div');
            wrap.className = 'amelia-bc-injected-wrap';
            wrap.innerHTML = html;
            insertParent.insertBefore(wrap, anchor);
            injected = true;
        }

        // Try immediately, then poll for late-mounting Vue widgets (Amelia
        // can take 1-3 seconds to mount, especially in page builders).
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', inject);
        } else {
            inject();
        }
        var attempts = 0;
        var poll = setInterval(function () {
            attempts++;
            inject();
            if (injected || attempts > 30) clearInterval(poll); // ~15s max
        }, 500);

        // Also re-check on any DOM mutation (covers tab/accordion-revealed widgets)
        if (window.MutationObserver) {
            var mo = new MutationObserver(function () { if (!injected) inject(); });
            try { mo.observe(document.body, { childList: true, subtree: true }); } catch (e) {}
        }
    })();
    </script>
    <?php
}

/**
 * Manual placement: render the full-booked message regardless of toggles.
 *   [amelia_booking_full_message service_ids="1,2" force="1"]
 * Or render the counter on demand:
 *   [amelia_booking_counter service_ids="1,2"]
 */
add_shortcode('amelia_booking_counter', 'amelia_bc_manual_counter_shortcode');
function amelia_bc_manual_counter_shortcode($atts) {
    $atts = shortcode_atts(array(
        'service_ids' => '',
        'category_ids' => '',
    ), $atts, 'amelia_booking_counter');

    $service_ids = amelia_bc_resolve_service_ids(array(
        'service_ids' => $atts['service_ids'],
        'category_ids' => $atts['category_ids']
    ));
    $html = amelia_bc_render_html($service_ids);
    return $html === '' ? '' : (amelia_bc_inject_styles() . $html);
}

add_shortcode('amelia_booking_full_message', 'amelia_bc_manual_fullmsg_shortcode');
function amelia_bc_manual_fullmsg_shortcode($atts) {
    $atts = shortcode_atts(array(
        'service_ids' => '',
        'category_ids' => '',
        'force' => '0',
    ), $atts, 'amelia_booking_full_message');

    $service_ids = amelia_bc_resolve_service_ids(array(
        'service_ids' => $atts['service_ids'],
        'category_ids' => $atts['category_ids']
    ));

    $force = intval($atts['force']) === 1;
    $html = amelia_bc_render_html($service_ids, array('force_full' => $force));
    return $html === '' ? '' : (amelia_bc_inject_styles() . $html);
}
