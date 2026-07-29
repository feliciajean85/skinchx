<?php
/**
 * Booking Slot Counter — Waitlist Dashboard (v9.7.4)
 *
 * Lists everyone who joined the waitlist via the in-panel form. Lets the
 * admin filter, change status, manually add / remove rows, bulk-import
 * from a CSV (per service), and export to CSV.
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) {
    wp_die('Permission denied');
}

require_once WPAMELIA_ADDON_PLUGIN_DIR . 'public/booking-counter-waitlist.php';
amelia_bc_waitlist_create_table(); // Idempotent — guarantees the table exists.

global $wpdb;
$table          = $wpdb->prefix . 'amelia_bc_waitlist';
$services_table = $wpdb->prefix . 'amelia_services';
$months         = amelia_bc_waitlist_months();
$statuses       = amelia_bc_waitlist_statuses();

// ---------------------------------------------------------------------
// CSV export — runs before any HTML so we can stream a clean download.
// ---------------------------------------------------------------------
if (isset($_POST['amelia_bc_wl_export']) && check_admin_referer('amelia_bc_wl_action', 'amelia_bc_wl_nonce')) {
    $where = array('1=1'); $params = array();
    if (!empty($_POST['filter_service'])) {
        $where[] = 'service_id = %d'; $params[] = intval($_POST['filter_service']);
    }
    if (!empty($_POST['filter_status']) && in_array($_POST['filter_status'], $statuses, true)) {
        $where[] = 'status = %s'; $params[] = sanitize_text_field($_POST['filter_status']);
    }
    if (!empty($_POST['filter_from'])) {
        $where[] = 'created_utc >= %s'; $params[] = sanitize_text_field($_POST['filter_from']) . ' 00:00:00';
    }
    if (!empty($_POST['filter_to'])) {
        $where[] = 'created_utc <= %s'; $params[] = sanitize_text_field($_POST['filter_to']) . ' 23:59:59';
    }
    $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY created_utc DESC";
    $rows = empty($params) ? $wpdb->get_results($sql) : $wpdb->get_results($wpdb->prepare($sql, ...$params));
    $ids_to_mark = array();

    while (ob_get_level()) ob_end_clean();
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="amelia-waitlist-' . date('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
    fputcsv($out, array('Created (UTC)', 'Service', 'Service ID', 'Name', 'Email', 'Phone', 'Preferred Months', 'Status', 'Notes', 'IP', 'Source'));
    foreach ($rows as $r) {
        $ids_to_mark[] = intval($r->id);
        fputcsv($out, array(
            $r->created_utc,
            $r->service_name_cache,
            $r->service_id,
            $r->name,
            $r->email,
            (string) ($r->phone ?? ''),
            $r->preferred_months,
            $r->status,
            (string) $r->notes,
            $r->ip_address,
            $r->source,
        ));
    }
    fclose($out);

    // Auto-mark exported rows (only those still in `new`) as `exported` so
    // the doctor can see at a glance which ones are fresh.
    if (!empty($ids_to_mark) && !empty($_POST['auto_mark_exported'])) {
        $placeholders = implode(',', array_fill(0, count($ids_to_mark), '%d'));
        $update_sql = "UPDATE $table SET status = 'exported', updated_utc = UTC_TIMESTAMP() WHERE status = 'new' AND id IN ($placeholders)";
        $wpdb->query($wpdb->prepare($update_sql, ...$ids_to_mark));
    }
    exit;
}

// ---------------------------------------------------------------------
// POST actions: add manual entry, bulk action, single delete, status edit
// ---------------------------------------------------------------------
$flash = '';
if (isset($_POST['amelia_bc_wl_nonce']) && wp_verify_nonce($_POST['amelia_bc_wl_nonce'], 'amelia_bc_wl_action')) {

    if (isset($_POST['amelia_bc_wl_add'])) {
        $name   = sanitize_text_field($_POST['name'] ?? '');
        $email  = sanitize_email($_POST['email'] ?? '');
        $phone  = sanitize_text_field($_POST['phone'] ?? '');
        $months_in = isset($_POST['preferred_months']) ? (array) $_POST['preferred_months'] : array();
        $months_csv = amelia_bc_waitlist_normalize_months($months_in);
        $sid    = intval($_POST['service_id'] ?? 0);
        $status = in_array($_POST['status'] ?? 'new', $statuses, true) ? $_POST['status'] : 'new';
        $notes  = sanitize_textarea_field($_POST['notes'] ?? '');

        if ($name === '' || !is_email($email) || $months_csv === '') {
            $flash = '<div class="notice notice-error"><p>Name, valid email, and at least one preferred month are required.</p></div>';
        } else {
            $service_name = '';
            if ($sid > 0 && $wpdb->get_var("SHOW TABLES LIKE '$services_table'") === $services_table) {
                $service_name = (string) $wpdb->get_var($wpdb->prepare("SELECT name FROM $services_table WHERE id = %d", $sid));
            }
            $wpdb->insert($table, array(
                'service_id'         => $sid ?: null,
                'service_name_cache' => $service_name ?: null,
                'name'               => $name,
                'email'               => $email,
                'phone'               => $phone ?: null,
                'preferred_months'   => $months_csv,
                'status'             => $status,
                'notes'              => $notes ?: null,
                'source'             => 'manual',
                'created_utc'        => current_time('mysql', true),
            ));
            $sent_msg = '';
            if (!empty($_POST['send_confirmation'])) {
                $sent = amelia_bc_waitlist_send_confirmation($email, $name, $service_name, $months_csv);
                $sent_msg = $sent
                    ? ' Confirmation email sent.'
                    : ' (Confirmation email could not be sent — check SMTP settings.)';
            }
            $flash = '<div class="notice notice-success"><p>Entry added.' . esc_html($sent_msg) . '</p></div>';
        }
    }

    // -----------------------------------------------------------------
    // Bulk import handler — paste from a spreadsheet OR upload a CSV file
    // and bulk-add waitlist entries scoped to one service.
    //
    // Each row = Name, Email, Phone, Months. Months can be 3-letter
    // abbreviations separated by ; | / or whitespace (so a CSV cell
    // doesn't need quoting). Comma-separated values inside quotes work
    // too (str_getcsv handles it). Skips invalid rows + duplicates
    // (same email already in this service's waitlist).
    // -----------------------------------------------------------------
    if (isset($_POST['amelia_bc_wl_import'])) {
        $import_sid = intval($_POST['import_service_id'] ?? 0);
        $import_status = in_array($_POST['import_status'] ?? 'new', $statuses, true) ? $_POST['import_status'] : 'new';
        $has_header = !empty($_POST['import_has_header']);
        $raw = (string) ($_POST['import_csv'] ?? '');

        // Allow uploaded files too (.csv / .txt). PHP's $_FILES is the
        // standard channel — pull it in if the textarea is empty.
        if ($raw === '' && !empty($_FILES['import_file']) && empty($_FILES['import_file']['error'])) {
            $raw = (string) file_get_contents($_FILES['import_file']['tmp_name']);
        }

        if ($import_sid <= 0) {
            $flash = '<div class="notice notice-error"><p>Please pick a service for the imported rows.</p></div>';
        } elseif (trim($raw) === '') {
            $flash = '<div class="notice notice-error"><p>Nothing to import — paste rows in the textarea or pick a CSV file.</p></div>';
        } else {
            // Resolve service name once.
            $import_service_name = '';
            if ($wpdb->get_var("SHOW TABLES LIKE '$services_table'") === $services_table) {
                $import_service_name = (string) $wpdb->get_var($wpdb->prepare("SELECT name FROM $services_table WHERE id = %d", $import_sid));
            }

            // Existing emails in this service's waitlist — used to skip dups.
            $existing_emails = array_map('strtolower', (array) $wpdb->get_col($wpdb->prepare(
                "SELECT email FROM $table WHERE service_id = %d", $import_sid
            )));
            $existing_emails = array_flip($existing_emails);

            // Normalise line endings, then split into lines.
            $raw_lines = preg_split('/\r\n|\r|\n/', trim($raw));
            $imported = 0; $dupes = 0; $invalid = 0; $row_idx = 0;

            foreach ($raw_lines as $line) {
                $row_idx++;
                if ($has_header && $row_idx === 1) continue;
                if (trim($line) === '') continue;

                // Auto-detect delimiter: prefer tab (spreadsheet paste),
                // fall back to comma. str_getcsv handles quoted commas.
                $delim = (strpos($line, "\t") !== false) ? "\t" : ',';
                $cols = str_getcsv($line, $delim);
                $name  = sanitize_text_field((string) ($cols[0] ?? ''));
                $email = sanitize_email((string) ($cols[1] ?? ''));
                $phone = sanitize_text_field((string) ($cols[2] ?? ''));
                $months_raw = (string) ($cols[3] ?? '');
                $months_csv = amelia_bc_waitlist_normalize_months($months_raw);

                if ($name === '' || !is_email($email)) { $invalid++; continue; }
                if (isset($existing_emails[strtolower($email)])) { $dupes++; continue; }

                $wpdb->insert($table, array(
                    'service_id'         => $import_sid,
                    'service_name_cache' => $import_service_name ?: null,
                    'name'               => $name,
                    'email'              => $email,
                    'phone'              => $phone ?: null,
                    'preferred_months'   => $months_csv,
                    'status'             => $import_status,
                    'source'             => 'import',
                    'created_utc'        => current_time('mysql', true),
                ));
                $existing_emails[strtolower($email)] = true; // guard against in-batch duplicates
                $imported++;
            }

            $flash = '<div class="notice notice-' . ($imported > 0 ? 'success' : 'warning') . '"><p>'
                   . 'Imported <strong>' . intval($imported) . '</strong> new waitlist entries'
                   . ($import_service_name !== '' ? ' for <strong>' . esc_html($import_service_name) . '</strong>' : '')
                   . '. Skipped ' . intval($dupes) . ' duplicate email(s) and ' . intval($invalid) . ' invalid row(s).'
                   . '</p></div>';
        }
    }

    if (isset($_POST['amelia_bc_wl_update_row']) && intval($_POST['row_id'] ?? 0) > 0) {
        $rid = intval($_POST['row_id']);
        $new_status = in_array($_POST['row_status'] ?? '', $statuses, true) ? $_POST['row_status'] : 'new';
        $notes = sanitize_textarea_field($_POST['row_notes'] ?? '');
        $wpdb->update($table, array(
            'status'      => $new_status,
            'notes'       => $notes ?: null,
            'updated_utc' => current_time('mysql', true),
        ), array('id' => $rid));
        $flash = '<div class="notice notice-success"><p>Entry updated.</p></div>';
    }

    if (isset($_POST['amelia_bc_wl_bulk']) && !empty($_POST['ids']) && is_array($_POST['ids'])) {
        $ids = array_filter(array_map('intval', $_POST['ids']));
        $action = $_POST['bulk_action'] ?? '';
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            if ($action === 'delete') {
                $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE id IN ($placeholders)", ...$ids));
                $flash = '<div class="notice notice-success"><p>' . count($ids) . ' entry(ies) deleted.</p></div>';
            } elseif (in_array($action, $statuses, true)) {
                $sql = "UPDATE $table SET status = %s, updated_utc = UTC_TIMESTAMP() WHERE id IN ($placeholders)";
                $wpdb->query($wpdb->prepare($sql, ...array_merge(array($action), $ids)));
                $flash = '<div class="notice notice-success"><p>Status set to <strong>' . esc_html($action) . '</strong> for ' . count($ids) . ' entry(ies).</p></div>';
            }
        }
    }

    if (isset($_POST['amelia_bc_wl_delete_one']) && intval($_POST['row_id'] ?? 0) > 0) {
        $wpdb->delete($table, array('id' => intval($_POST['row_id'])));
        $flash = '<div class="notice notice-success"><p>Entry removed.</p></div>';
    }
}

// ---------------------------------------------------------------------
// Filters & list query
// ---------------------------------------------------------------------
$filter_service = isset($_GET['service']) ? intval($_GET['service']) : 0;
$filter_status  = isset($_GET['status']) && in_array($_GET['status'], $statuses, true) ? $_GET['status'] : '';
$filter_from    = isset($_GET['from']) ? sanitize_text_field($_GET['from']) : '';
$filter_to      = isset($_GET['to'])   ? sanitize_text_field($_GET['to'])   : '';

$where = array('1=1'); $params = array();
if ($filter_service > 0) { $where[] = 'service_id = %d'; $params[] = $filter_service; }
if ($filter_status !== '') { $where[] = 'status = %s'; $params[] = $filter_status; }
if ($filter_from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_from)) { $where[] = 'created_utc >= %s'; $params[] = $filter_from . ' 00:00:00'; }
if ($filter_to   !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_to))   { $where[] = 'created_utc <= %s'; $params[] = $filter_to . ' 23:59:59'; }

// Pagination: WordPress-style per-page selector
$allowed_per_page = array(5, 10, 20, 50);
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
if (!in_array($per_page, $allowed_per_page, true)) $per_page = 10;
$paged = max(1, isset($_GET['paged']) ? intval($_GET['paged']) : 1);

$where_sql = implode(' AND ', $where);
$count_sql = "SELECT COUNT(*) FROM $table WHERE $where_sql";
$filtered_count = (int) (empty($params)
    ? $wpdb->get_var($count_sql)
    : $wpdb->get_var($wpdb->prepare($count_sql, ...$params)));

$total_pages = max(1, (int) ceil($filtered_count / $per_page));
if ($paged > $total_pages) $paged = $total_pages;
$offset = ($paged - 1) * $per_page;

$sql = "SELECT * FROM $table WHERE $where_sql ORDER BY created_utc DESC LIMIT %d OFFSET %d";
$page_params = array_merge($params, array($per_page, $offset));
$rows = $wpdb->get_results($wpdb->prepare($sql, ...$page_params));

// Counts per status (always unfiltered, so admin sees the big picture)
$counts = array_fill_keys($statuses, 0);
$total_count = 0;
foreach ($wpdb->get_results("SELECT status, COUNT(*) c FROM $table GROUP BY status") as $cr) {
    if (isset($counts[$cr->status])) $counts[$cr->status] = (int) $cr->c;
    $total_count += (int) $cr->c;
}

// ---------------------------------------------------------------------
// Demand mini-charts: count preferred-month picks per service.
//
// Preferred months are stored as a comma-separated string per row, so we
// pull the raw rows and tally in PHP. The chart honours every Filter
// card input — From/To, Service, and Status — so picking a single
// service narrows the chart to just that service's bars, and picking
// a status (e.g. `new`) shows demand from un-contacted people only.
// ---------------------------------------------------------------------
$chart_where = array('1=1'); $chart_params = array();
if ($filter_from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_from)) {
    $chart_where[] = 'created_utc >= %s'; $chart_params[] = $filter_from . ' 00:00:00';
}
if ($filter_to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_to)) {
    $chart_where[] = 'created_utc <= %s'; $chart_params[] = $filter_to . ' 23:59:59';
}
if ($filter_service > 0) {
    $chart_where[] = 'service_id = %d'; $chart_params[] = $filter_service;
}
if ($filter_status !== '') {
    $chart_where[] = 'status = %s'; $chart_params[] = $filter_status;
}
$chart_sql = "SELECT service_id, service_name_cache, preferred_months
              FROM $table WHERE " . implode(' AND ', $chart_where);
$chart_rows = empty($chart_params)
    ? $wpdb->get_results($chart_sql)
    : $wpdb->get_results($wpdb->prepare($chart_sql, ...$chart_params));

// Tally: $demand[service_key]['name'|'service_id'|'total'|'months'=>[Jan=>n,...]]
$demand = array();
$global_max = 0;
foreach ($chart_rows as $r) {
    $key = $r->service_id ? 's' . intval($r->service_id) : 'unknown';
    if (!isset($demand[$key])) {
        $demand[$key] = array(
            'name'       => $r->service_name_cache ?: ($r->service_id ? ('Service #' . intval($r->service_id)) : 'Unknown service'),
            'service_id' => $r->service_id ? intval($r->service_id) : 0,
            'total'      => 0,
            'months'     => array_fill_keys($months, 0),
        );
    }
    $demand[$key]['total']++;
    foreach (preg_split('/[,;\s]+/', (string) $r->preferred_months) as $m) {
        $m = ucfirst(strtolower(trim($m)));
        if ($m !== '' && isset($demand[$key]['months'][$m])) {
            $demand[$key]['months'][$m]++;
            if ($demand[$key]['months'][$m] > $global_max) $global_max = $demand[$key]['months'][$m];
        }
    }
}

// Sort services by total signups (descending) so the busiest services
// appear at the top of the chart card.
uasort($demand, function ($a, $b) { return $b['total'] - $a['total']; });

// Service dropdown
$services = array();
if ($wpdb->get_var("SHOW TABLES LIKE '$services_table'") === $services_table) {
    $services = $wpdb->get_results("SELECT id, name FROM $services_table WHERE status != 'disabled' ORDER BY name ASC");
}

// Convert UTC → site local for display
function amelia_bc_wl_local($utc_str) {
    if (!$utc_str) return '';
    try {
        $d = new DateTime($utc_str . ' UTC');
        $d->setTimezone(wp_timezone());
        return $d->format('Y-m-d H:i');
    } catch (Exception $e) { return esc_html($utc_str); }
}
?>
<style>
.bcwl-wrap { background: #D4F1E8; padding: 30px; border-radius: 16px; font-family: 'Quicksand', sans-serif; max-width: 1280px; margin-top: 20px; }
.bcwl-wrap h1 { font-family: 'Quicksand', sans-serif; font-weight: 700; font-size: 28px; color: #5A4A5A; margin-bottom: 24px; }
.bcwl-card { background: white; padding: 22px; border-radius: 16px; box-shadow: 0 4px 16px rgba(255, 181, 197, 0.12); margin-bottom: 18px; }
.bcwl-card h2 { font-family: 'Quicksand', sans-serif; font-weight: 700; font-size: 17px; color: #5A4A5A; margin: 0 0 14px 0; display: flex; align-items: center; gap: 10px; }
.bcwl-card h2 .dot { width: 10px; height: 10px; background: linear-gradient(135deg, #FFB5C5 0%, #E8829A 100%); border-radius: 50%; }
.bcwl-stats { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 6px; }
.bcwl-stat { padding: 8px 14px; border-radius: 16px; font-weight: 700; font-size: 13px; background: #FFF9F5; border: 2px solid #FFE4EC; color: #5A4A5A; }
.bcwl-stat strong { color: #E8829A; margin-right: 6px; }
.bcwl-stat.is-new strong { color: #5FBDA0; }
.bcwl-stat.is-exported strong { color: #8A4A1F; }
.bcwl-stat.is-contacted strong { color: #4A6FB5; }
.bcwl-stat.is-booked strong { color: #2C6B58; }
.bcwl-filters { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; align-items: end; }
@media (max-width: 900px) { .bcwl-filters { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.bcwl-filters label { display: block; font-size: 12px; font-weight: 700; color: #5A4A5A; margin-bottom: 4px; }
.bcwl-filters input, .bcwl-filters select { width: 100%; padding: 8px 10px; border: 2px solid #FFE4EC; border-radius: 8px; font-family: inherit; font-size: 13px; background: #FFF9F5; }
.bcwl-filters input:focus, .bcwl-filters select:focus { outline: none; border-color: #98D9C2; background: #fff; }
.bcwl-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 22px; border: none; cursor: pointer; font-weight: 700; font-size: 13px; font-family: inherit; }
.bcwl-btn.primary { background: linear-gradient(135deg, #98D9C2 0%, #5FBDA0 100%); color: white; }
.bcwl-btn.secondary { background: #FFE4EC; color: #842029; }
.bcwl-btn.danger { background: #FFD6DD; color: #842029; }
.bcwl-btn:hover { transform: translateY(-1px); }
.bcwl-table { width: 100%; border-collapse: collapse; }
.bcwl-table th, .bcwl-table td { padding: 10px; vertical-align: top; font-size: 13px; color: #5A4A5A; border-bottom: 1px solid #FFE4EC; text-align: left; }
.bcwl-table thead th { background: #FFF9F5; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.4px; }
.bcwl-table tr:hover { background: #FFF9F5; }
.bcwl-pill { display: inline-block; padding: 3px 10px; font-size: 11px; font-weight: 700; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.3px; }
.bcwl-pill.s-new { background: #D4F1E8; color: #2C6B58; }
.bcwl-pill.s-exported { background: #FFE9D6; color: #8A4A1F; }
.bcwl-pill.s-contacted { background: #DCE7FF; color: #29487D; }
.bcwl-pill.s-booked { background: #FFD6DD; color: #842029; }
.bcwl-add { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px 12px; align-items: start; }
@media (max-width: 1100px) { .bcwl-add { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 600px) { .bcwl-add { grid-template-columns: 1fr; } }
.bcwl-add label { font-size: 12px; font-weight: 700; color: #5A4A5A; }
.bcwl-add input, .bcwl-add select, .bcwl-add textarea { width: 100%; padding: 7px 10px; border: 2px solid #FFE4EC; border-radius: 8px; font-family: inherit; font-size: 13px; background: #FFF9F5; box-sizing: border-box; }
.bcwl-add textarea { min-height: 38px; resize: vertical; }
.bcwl-add .full { grid-column: 1 / -1; }
.bcwl-add .sn-row { grid-column: 1 / -1; display: grid; grid-template-columns: minmax(140px, 180px) 1fr; gap: 12px; align-items: start; }
@media (max-width: 800px) { .bcwl-add .sn-row { grid-template-columns: 1fr; } }
.bcwl-month-chips { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 4px; }
.bcwl-month-chips label { display: inline-flex; align-items: center; gap: 4px; padding: 4px 9px; background: #FFF9F5; border: 2px solid #FFE4EC; border-radius: 14px; font-size: 11.5px; font-weight: 600; cursor: pointer; user-select: none; line-height: 1.1; transition: border-color 0.15s ease, background 0.15s ease; }
.bcwl-month-chips label:hover { border-color: #98D9C2; }
.bcwl-month-chips label.is-on { background: #D4F1E8; border-color: #5FBDA0; color: #2C6B58; }
.bcwl-month-chips input { margin: 0; cursor: pointer; }

/* Import panel ------------------------------------------------------- */
.bcwl-import { margin-top: 14px; padding-top: 14px; border-top: 2px dashed #FFE4EC; }
.bcwl-import > summary { cursor: pointer; display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; border-radius: 22px; background: #FFE4EC; color: #842029; font-weight: 700; font-size: 13px; list-style: none; user-select: none; }
.bcwl-import > summary::-webkit-details-marker { display: none; }
.bcwl-import > summary:hover { background: #FFD6DD; }
.bcwl-import[open] > summary { background: linear-gradient(135deg, #98D9C2 0%, #5FBDA0 100%); color: white; }
.bcwl-import-howto { background: #FFF9F5; border: 2px solid #FFE4EC; border-radius: 12px; padding: 12px 14px; margin: 12px 0; font-size: 12.5px; line-height: 1.55; color: #5A4A5A; }
.bcwl-import-howto code { background: #FFE4EC; padding: 1px 6px; border-radius: 4px; font-size: 12px; }
.bcwl-import-grid { display: grid; grid-template-columns: minmax(160px, 220px) minmax(160px, 220px) 1fr; gap: 10px 12px; align-items: end; margin-bottom: 10px; }
@media (max-width: 800px) { .bcwl-import-grid { grid-template-columns: 1fr; } }
.bcwl-import textarea { width: 100%; min-height: 110px; padding: 8px 10px; border: 2px solid #FFE4EC; border-radius: 8px; font-family: 'SF Mono', Menlo, Consolas, monospace; font-size: 12px; background: #fff; box-sizing: border-box; resize: vertical; }
.bcwl-import .or-divider { text-align: center; font-size: 11px; font-weight: 700; color: #8A7A8A; letter-spacing: 0.4px; margin: 8px 0; text-transform: uppercase; }
.bcwl-empty { padding: 30px; text-align: center; color: #8A7A8A; font-style: italic; }
.bcwl-row-form { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: 4px; }
.bcwl-row-form select, .bcwl-row-form input[type="text"] { padding: 4px 8px; font-size: 12px; border: 1px solid #FFE4EC; border-radius: 6px; }
.bcwl-row-form button { padding: 4px 10px; font-size: 11px; font-weight: 700; border-radius: 12px; border: none; cursor: pointer; }
.bcwl-row-form .save { background: #98D9C2; color: white; }
.bcwl-row-form .del  { background: #FFD6DD; color: #842029; }
.bcwl-actions-bar { display: flex; gap: 10px; align-items: center; margin: 14px 0 10px 0; flex-wrap: wrap; }
.bcwl-pagination { display: flex; justify-content: space-between; align-items: center; gap: 14px; margin-top: 14px; flex-wrap: wrap; padding: 10px 14px; background: #FFF9F5; border: 2px solid #FFE4EC; border-radius: 12px; }
.bcwl-pag-summary { font-size: 12.5px; color: #5A4A5A; }
.bcwl-pag-controls { display: inline-flex; gap: 6px; align-items: center; }
.bcwl-pag-link { display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 30px; padding: 0 8px; border-radius: 18px; background: #fff; border: 2px solid #FFE4EC; color: #5A4A5A; font-weight: 700; text-decoration: none; font-size: 13px; }
.bcwl-pag-link:hover { background: #D4F1E8; border-color: #98D9C2; color: #2C6B58; }
.bcwl-pag-link.is-disabled { pointer-events: none; opacity: 0.4; }
.bcwl-pag-current { padding: 0 10px; font-size: 13px; font-weight: 700; color: #2C6B58; }

/* Demand mini-charts ------------------------------------------------- */
.bcwl-demand-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
@media (max-width: 900px) { .bcwl-demand-grid { grid-template-columns: 1fr; } }
.bcwl-demand-card { background: #FFF9F5; border: 2px solid #FFE4EC; border-radius: 12px; padding: 14px 16px; }
.bcwl-demand-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px; gap: 8px; }
.bcwl-demand-name { font-weight: 700; color: #5A4A5A; font-size: 14px; line-height: 1.3; }
.bcwl-demand-total { font-weight: 700; color: #5FBDA0; background: #D4F1E8; padding: 3px 10px; border-radius: 12px; font-size: 12px; white-space: nowrap; }
.bcwl-demand-bars { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 6px; align-items: end; height: 90px; }
.bcwl-demand-col { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: 4px; cursor: default; }
.bcwl-demand-bar { width: 100%; background: linear-gradient(180deg, #FFB5C5 0%, #E8829A 100%); border-radius: 4px 4px 0 0; min-height: 2px; transition: filter 0.15s ease; }
.bcwl-demand-col.is-zero .bcwl-demand-bar { background: #FFE4EC; min-height: 2px; }
.bcwl-demand-col.is-top .bcwl-demand-bar { background: linear-gradient(180deg, #98D9C2 0%, #5FBDA0 100%); }
.bcwl-demand-col:hover .bcwl-demand-bar { filter: brightness(1.08); }
.bcwl-demand-count { font-size: 10px; font-weight: 700; color: #5A4A5A; min-height: 12px; }
.bcwl-demand-label { font-size: 10.5px; font-weight: 600; color: #8A7A8A; text-transform: uppercase; letter-spacing: 0.3px; }
.bcwl-demand-empty { padding: 30px; text-align: center; color: #8A7A8A; font-style: italic; }
.bcwl-demand-foot { font-size: 11.5px; color: #8A7A8A; margin-top: 8px; }
</style>

<div class="wrap">
    <div class="bcwl-wrap">
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#5FBDA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="8.5" cy="7" r="4"/>
                <line x1="20" y1="8" x2="20" y2="14"/>
                <line x1="23" y1="11" x2="17" y2="11"/>
            </svg>
            Booking Waitlist
        </h1>

        <?php echo $flash; ?>

        <div class="bcwl-card">
            <h2><span class="dot"></span>At a Glance</h2>
            <div class="bcwl-stats">
                <div class="bcwl-stat"><strong><?php echo intval($total_count); ?></strong>Total signups</div>
                <div class="bcwl-stat is-new"><strong><?php echo intval($counts['new']); ?></strong>New</div>
                <div class="bcwl-stat is-exported"><strong><?php echo intval($counts['exported']); ?></strong>Exported</div>
                <div class="bcwl-stat is-contacted"><strong><?php echo intval($counts['contacted']); ?></strong>Contacted</div>
                <div class="bcwl-stat is-booked"><strong><?php echo intval($counts['booked']); ?></strong>Booked</div>
            </div>
        </div>

        <div class="bcwl-card">
            <h2><span class="dot"></span>Add Manual Entry</h2>
            <form method="post">
                <?php wp_nonce_field('amelia_bc_wl_action', 'amelia_bc_wl_nonce'); ?>
                <div class="bcwl-add">
                    <div>
                        <label>Name *</label>
                        <input type="text" name="name" required maxlength="190">
                    </div>
                    <div>
                        <label>Email *</label>
                        <input type="email" name="email" required maxlength="190">
                    </div>
                    <div>
                        <label>Phone (optional)</label>
                        <input type="text" name="phone" maxlength="60" placeholder="+1 555-1234">
                    </div>
                    <div>
                        <label>Service (optional)</label>
                        <select name="service_id">
                            <option value="0">— None —</option>
                            <?php foreach ($services as $svc): ?>
                                <option value="<?php echo intval($svc->id); ?>"><?php echo esc_html($svc->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="full">
                        <label>Preferred months *</label>
                        <div class="bcwl-month-chips">
                            <?php foreach ($months as $m): ?>
                                <label><input type="checkbox" name="preferred_months[]" value="<?php echo esc_attr($m); ?>"><?php echo esc_html($m); ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="sn-row">
                        <div>
                            <label>Status</label>
                            <select name="status">
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?php echo esc_attr($s); ?>"><?php echo esc_html(ucfirst($s)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Notes (optional)</label>
                            <textarea name="notes" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <p style="margin-top: 12px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                    <button type="submit" name="amelia_bc_wl_add" value="1" class="bcwl-btn primary">+ Add entry</button>
                    <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #5A4A5A;">
                        <input type="checkbox" name="send_confirmation" value="1" checked>
                        Send "you're on the waitlist" confirmation email
                    </label>
                </p>
            </form>

            <details class="bcwl-import">
                <summary>📥 Import existing waitlist from CSV</summary>
                <div class="bcwl-import-howto">
                    <strong>How to import:</strong> pick the service these people are waiting for, then paste rows from a spreadsheet (Excel / Google Sheets / Numbers — copy &amp; paste straight in) <em>or</em> upload a CSV file.<br>
                    Each row needs <strong>4 columns</strong> in this order: <code>Name, Email, Phone, Months</code>.<br>
                    Use 3-letter month abbreviations for the last column (e.g. <code>Jan</code>, <code>Feb</code>, <code>Mar</code>). Multiple months? Separate them with semicolons or spaces — <code>Jan;Feb;Mar</code> or <code>Jan Feb Mar</code> both work. Phone is optional (leave blank or use <code>-</code>).<br>
                    <strong>Skips:</strong> duplicates (same email already on this service's waitlist) and rows missing a name or valid email. Imported rows are tagged with source <code>import</code>.
                </div>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('amelia_bc_wl_action', 'amelia_bc_wl_nonce'); ?>
                    <div class="bcwl-import-grid">
                        <div>
                            <label style="font-size: 12px; font-weight: 700; color: #5A4A5A;">Service *</label>
                            <select name="import_service_id" required style="width: 100%; padding: 7px 10px; border: 2px solid #FFE4EC; border-radius: 8px; font-family: inherit; font-size: 13px; background: #FFF9F5; box-sizing: border-box;">
                                <option value="0">— Pick a service —</option>
                                <?php foreach ($services as $svc): ?>
                                    <option value="<?php echo intval($svc->id); ?>"><?php echo esc_html($svc->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="font-size: 12px; font-weight: 700; color: #5A4A5A;">Default status</label>
                            <select name="import_status" style="width: 100%; padding: 7px 10px; border: 2px solid #FFE4EC; border-radius: 8px; font-family: inherit; font-size: 13px; background: #FFF9F5; box-sizing: border-box;">
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?php echo esc_attr($s); ?>" <?php selected($s, 'new'); ?>><?php echo esc_html(ucfirst($s)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: #5A4A5A; padding-top: 8px;">
                                <input type="checkbox" name="import_has_header" value="1">
                                First row is a header (skip it)
                            </label>
                        </div>
                    </div>
                    <label style="font-size: 12px; font-weight: 700; color: #5A4A5A; display: block; margin-top: 4px;">Paste rows here</label>
                    <textarea name="import_csv" placeholder="Sarah Lee, sarah@example.com, +1 555-1234, Jan;Feb;Mar
Mike Park, mike@example.com, , Apr May Jun
..."></textarea>
                    <div class="or-divider">— or —</div>
                    <label style="font-size: 12px; font-weight: 700; color: #5A4A5A; display: block;">Upload a CSV file</label>
                    <input type="file" name="import_file" accept=".csv,.txt,text/csv,text/plain" style="margin-top: 4px;">
                    <p style="margin-top: 14px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                        <button type="submit" name="amelia_bc_wl_import" value="1" class="bcwl-btn primary"
                                onclick="return confirm('Import these rows into the selected service\'s waitlist? Duplicate emails will be skipped.');">⬆ Import rows</button>
                        <span style="font-size: 11.5px; color: #8A7A8A;">Imported rows do <strong>not</strong> trigger confirmation emails — they're treated as historical data.</span>
                    </p>
                </form>
            </details>
        </div>

        <div class="bcwl-card">
            <h2><span class="dot"></span>Filter</h2>
            <form method="get">
                <input type="hidden" name="page" value="amelia-waitlist">
                <input type="hidden" name="per_page" value="<?php echo intval($per_page); ?>">
                <div class="bcwl-filters">
                    <div>
                        <label>Service</label>
                        <select name="service">
                            <option value="0">All services</option>
                            <?php foreach ($services as $svc): ?>
                                <option value="<?php echo intval($svc->id); ?>" <?php selected($filter_service, intval($svc->id)); ?>><?php echo esc_html($svc->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Status</label>
                        <select name="status">
                            <option value="">Any</option>
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?php echo esc_attr($s); ?>" <?php selected($filter_status, $s); ?>><?php echo esc_html(ucfirst($s)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>From (UTC date)</label>
                        <input type="date" name="from" value="<?php echo esc_attr($filter_from); ?>">
                    </div>
                    <div>
                        <label>To (UTC date)</label>
                        <input type="date" name="to" value="<?php echo esc_attr($filter_to); ?>">
                    </div>
                    <div>
                        <label>&nbsp;</label>
                        <button type="submit" class="bcwl-btn primary">Apply</button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=amelia-waitlist')); ?>" class="bcwl-btn secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="bcwl-card">
            <h2><span class="dot"></span>Top Demanded Months — by Service</h2>
            <?php if (empty($demand)): ?>
                <div class="bcwl-demand-empty">No waitlist signups yet<?php
                    $why = array();
                    if ($filter_service > 0) $why[] = 'this service';
                    if ($filter_status !== '') $why[] = 'status "' . esc_html($filter_status) . '"';
                    if ($filter_from || $filter_to) $why[] = 'the selected date range';
                    if (!empty($why)) echo ' for ' . implode(' + ', $why);
                ?>. Once people start joining the list, you'll see a per-service month-demand bar chart here.</div>
            <?php else: ?>
                <div class="bcwl-demand-grid">
                    <?php foreach ($demand as $key => $svc):
                        // Pick the top month(s) for highlight colour
                        $svc_max = 0;
                        foreach ($svc['months'] as $cnt) if ($cnt > $svc_max) $svc_max = $cnt;
                    ?>
                        <div class="bcwl-demand-card">
                            <div class="bcwl-demand-head">
                                <span class="bcwl-demand-name"><?php echo esc_html($svc['name']); ?></span>
                                <span class="bcwl-demand-total"><?php echo intval($svc['total']); ?> signup<?php echo $svc['total'] === 1 ? '' : 's'; ?></span>
                            </div>
                            <div class="bcwl-demand-bars" role="img" aria-label="Month demand chart for <?php echo esc_attr($svc['name']); ?>">
                                <?php foreach ($months as $m):
                                    $cnt = (int) $svc['months'][$m];
                                    $pct = ($svc_max > 0) ? round(($cnt / $svc_max) * 100) : 0;
                                    $cls = 'bcwl-demand-col';
                                    if ($cnt === 0)                       $cls .= ' is-zero';
                                    elseif ($svc_max > 0 && $cnt === $svc_max) $cls .= ' is-top';
                                ?>
                                    <div class="<?php echo $cls; ?>" title="<?php echo esc_attr($m . ': ' . $cnt . ' request' . ($cnt === 1 ? '' : 's')); ?>">
                                        <span class="bcwl-demand-count"><?php echo $cnt > 0 ? intval($cnt) : ''; ?></span>
                                        <div class="bcwl-demand-bar" style="height: <?php echo intval($pct); ?>%;"></div>
                                        <span class="bcwl-demand-label"><?php echo esc_html($m); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($svc['service_id'] > 0): ?>
                                <div class="bcwl-demand-foot">
                                    <a href="<?php echo esc_url(add_query_arg(array('page' => 'amelia-waitlist', 'service' => $svc['service_id']), admin_url('admin.php'))); ?>">View this service's entries →</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p style="font-size: 12px; color: #8A7A8A; margin: 14px 0 0 0;">
                    Demand chart now honours <strong>every</strong> Filter input above — From/To, Service, and Status. Pick a service to narrow the chart to one card, or pick a status (e.g. <em>new</em>) to see demand from people you haven't contacted yet.
                </p>
            <?php endif; ?>
        </div>

        <div class="bcwl-card">
            <h2><span class="dot"></span>Entries (<?php echo intval($filtered_count); ?> total<?php
                if ($filtered_count > $per_page) {
                    echo ' — showing ' . intval($offset + 1) . '–' . intval(min($offset + $per_page, $filtered_count));
                }
            ?>)</h2>
            <form method="post">
                <?php wp_nonce_field('amelia_bc_wl_action', 'amelia_bc_wl_nonce'); ?>
                <div class="bcwl-actions-bar">
                    <select name="bulk_action">
                        <option value="">Bulk action…</option>
                        <option value="delete">Delete</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?php echo esc_attr($s); ?>">Mark as <?php echo esc_html(ucfirst($s)); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="amelia_bc_wl_bulk" value="1" class="bcwl-btn secondary">Apply</button>
                    <button type="submit" name="amelia_bc_wl_export" value="1" class="bcwl-btn primary" formnovalidate
                            onclick="return confirm('Export the currently filtered entries as CSV?');">⬇ Export CSV (filtered)</button>
                    <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #5A4A5A;">
                        <input type="checkbox" name="auto_mark_exported" value="1" checked> Auto-mark new rows as <em>exported</em>
                    </label>
                    <input type="hidden" name="filter_service" value="<?php echo esc_attr($filter_service); ?>">
                    <input type="hidden" name="filter_status"  value="<?php echo esc_attr($filter_status); ?>">
                    <input type="hidden" name="filter_from"    value="<?php echo esc_attr($filter_from); ?>">
                    <input type="hidden" name="filter_to"      value="<?php echo esc_attr($filter_to); ?>">
                    <span style="flex: 1;"></span>
                    <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #5A4A5A;">
                        Per page:
                        <select id="bcwl-per-page" data-base="<?php
                            // Build a base URL preserving every filter except per_page + paged
                            echo esc_attr(add_query_arg(array(
                                'page'    => 'amelia-waitlist',
                                'service' => $filter_service ?: false,
                                'status'  => $filter_status ?: false,
                                'from'    => $filter_from ?: false,
                                'to'      => $filter_to ?: false,
                            ), admin_url('admin.php')));
                        ?>">
                            <?php foreach ($allowed_per_page as $pp): ?>
                                <option value="<?php echo intval($pp); ?>" <?php selected($per_page, $pp); ?>><?php echo intval($pp); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <?php if (empty($rows)): ?>
                    <div class="bcwl-empty">No waitlist entries found for the current filter.</div>
                <?php else: ?>
                    <table class="bcwl-table">
                        <thead>
                            <tr>
                                <th style="width: 28px;"><input type="checkbox" id="bcwl-check-all"></th>
                                <th style="width: 130px;">Created (local)</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Service</th>
                                <th>Months</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th>Source</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="<?php echo intval($r->id); ?>" class="bcwl-row-check"></td>
                                <td><?php echo esc_html(amelia_bc_wl_local($r->created_utc)); ?><br>
                                    <small style="color: #8A7A8A;">UTC: <?php echo esc_html($r->created_utc); ?></small></td>
                                <td><?php echo esc_html($r->name); ?></td>
                                <td><a href="mailto:<?php echo esc_attr($r->email); ?>"><?php echo esc_html($r->email); ?></a></td>
                                <td><?php
                                    $phone_val = isset($r->phone) ? trim((string) $r->phone) : '';
                                    if ($phone_val !== '') {
                                        $tel = preg_replace('/[^0-9+]/', '', $phone_val);
                                        echo '<a href="tel:' . esc_attr($tel) . '">' . esc_html($phone_val) . '</a>';
                                    } else {
                                        echo '<em style="color:#8A7A8A;">—</em>';
                                    }
                                ?></td>
                                <td><?php echo $r->service_name_cache ? esc_html($r->service_name_cache) : '<em style="color:#8A7A8A;">—</em>'; ?>
                                    <?php if ($r->service_id): ?><br><small style="color:#8A7A8A;">#<?php echo intval($r->service_id); ?></small><?php endif; ?></td>
                                <td><?php echo esc_html($r->preferred_months); ?></td>
                                <td><span class="bcwl-pill s-<?php echo esc_attr($r->status); ?>"><?php echo esc_html($r->status); ?></span></td>
                                <td style="max-width: 220px; word-wrap: break-word;"><?php echo $r->notes ? esc_html($r->notes) : '<em style="color:#8A7A8A;">—</em>'; ?></td>
                                <td><?php echo esc_html($r->source); ?></td>
                                <td>
                                    <details>
                                        <summary style="cursor: pointer; color: #5FBDA0; font-weight: 700;">Edit</summary>
                                        <div class="bcwl-row-form">
                                            <select name="row_status" form="bcwl-edit-<?php echo intval($r->id); ?>">
                                                <?php foreach ($statuses as $s): ?>
                                                    <option value="<?php echo esc_attr($s); ?>" <?php selected($r->status, $s); ?>><?php echo esc_html(ucfirst($s)); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="text" name="row_notes" placeholder="Notes" value="<?php echo esc_attr((string) $r->notes); ?>" form="bcwl-edit-<?php echo intval($r->id); ?>">
                                            <button class="save" type="submit" name="amelia_bc_wl_update_row" value="1" form="bcwl-edit-<?php echo intval($r->id); ?>">Save</button>
                                            <button class="del" type="submit" name="amelia_bc_wl_delete_one" value="1" form="bcwl-del-<?php echo intval($r->id); ?>"
                                                    onclick="return confirm('Delete this entry?');">Delete</button>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </form>

            <?php
            // Pagination nav (WordPress-style prev/next + page numbers).
            if ($filtered_count > $per_page):
                $base = add_query_arg(array(
                    'page'     => 'amelia-waitlist',
                    'service'  => $filter_service ?: false,
                    'status'   => $filter_status ?: false,
                    'from'     => $filter_from ?: false,
                    'to'       => $filter_to ?: false,
                    'per_page' => $per_page,
                ), admin_url('admin.php'));
                $page_url = function ($p) use ($base) { return esc_url(add_query_arg('paged', max(1, intval($p)), $base)); };
            ?>
                <nav class="bcwl-pagination" aria-label="Waitlist pagination">
                    <span class="bcwl-pag-summary">Page <strong><?php echo intval($paged); ?></strong> of <strong><?php echo intval($total_pages); ?></strong> &middot; <?php echo intval($filtered_count); ?> total</span>
                    <span class="bcwl-pag-controls">
                        <a class="bcwl-pag-link <?php echo $paged <= 1 ? 'is-disabled' : ''; ?>" href="<?php echo $page_url(1); ?>" aria-label="First page">&laquo;</a>
                        <a class="bcwl-pag-link <?php echo $paged <= 1 ? 'is-disabled' : ''; ?>" href="<?php echo $page_url($paged - 1); ?>" aria-label="Previous page">&lsaquo;</a>
                        <span class="bcwl-pag-current"><?php echo intval($paged); ?> / <?php echo intval($total_pages); ?></span>
                        <a class="bcwl-pag-link <?php echo $paged >= $total_pages ? 'is-disabled' : ''; ?>" href="<?php echo $page_url($paged + 1); ?>" aria-label="Next page">&rsaquo;</a>
                        <a class="bcwl-pag-link <?php echo $paged >= $total_pages ? 'is-disabled' : ''; ?>" href="<?php echo $page_url($total_pages); ?>" aria-label="Last page">&raquo;</a>
                    </span>
                </nav>
            <?php endif; ?>

            <?php // Hidden per-row forms so the inline edit/delete buttons don't conflict with the bulk form. ?>
            <?php foreach ($rows as $r): ?>
                <form id="bcwl-edit-<?php echo intval($r->id); ?>" method="post" style="display:none;">
                    <?php wp_nonce_field('amelia_bc_wl_action', 'amelia_bc_wl_nonce'); ?>
                    <input type="hidden" name="row_id" value="<?php echo intval($r->id); ?>">
                </form>
                <form id="bcwl-del-<?php echo intval($r->id); ?>" method="post" style="display:none;">
                    <?php wp_nonce_field('amelia_bc_wl_action', 'amelia_bc_wl_nonce'); ?>
                    <input type="hidden" name="row_id" value="<?php echo intval($r->id); ?>">
                </form>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
(function(){
    var all = document.getElementById('bcwl-check-all');
    if (all) all.addEventListener('change', function(){
        document.querySelectorAll('.bcwl-row-check').forEach(function(c){ c.checked = all.checked; });
    });
    var pp = document.getElementById('bcwl-per-page');
    if (pp) pp.addEventListener('change', function(){
        var base = pp.getAttribute('data-base');
        var sep = base.indexOf('?') > -1 ? '&' : '?';
        window.location.href = base + sep + 'per_page=' + encodeURIComponent(pp.value) + '&paged=1';
    });
    // Visual on/off for the manual-entry month chips (mirrors the frontend
    // waitlist form's behaviour so the admin gets the same nice feedback).
    document.querySelectorAll('.bcwl-month-chips input[type="checkbox"]').forEach(function(chk){
        var apply = function(){ chk.closest('label').classList.toggle('is-on', chk.checked); };
        chk.addEventListener('change', apply);
        apply();
    });
})();
</script>
