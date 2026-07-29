<?php
add_action('wp_ajax_load_booking_list', 'load_booking_list');

function load_booking_list()
{
  check_ajax_referer('ajax-nonce', 'nonce');
  global $wpdb;

  $page     = max(1, intval($_POST['page'] ?? 1));
  $mode     = sanitize_text_field($_POST['mode'] ?? 'list');
  $search   = sanitize_text_field($_POST['search'] ?? '');
  $customer = intval($_POST['customer'] ?? 0);

  $limit  = 20;
  $offset = ($page - 1) * $limit;

  $a = $wpdb->prefix . 'amelia_appointments';
  $cb = $wpdb->prefix . 'amelia_customer_bookings';
  $u = $wpdb->prefix . 'amelia_users';

  $where = '1=1';

  if ($search) {
    // $where .= $wpdb->prepare(
    //   " AND (u.firstName LIKE %s OR u.lastName LIKE %s)",
    //   "%$search%",
    //   "%$search%"
    // );
    $where .= $wpdb->prepare(
      " AND (CONCAT(u.firstName, ' ', u.lastName) LIKE %s OR u.firstName LIKE %s OR u.lastName LIKE %s)",
      "%$search%",
      "%$search%",
      "%$search%"
    );
  }
  $date = sanitize_text_field($_POST['date'] ?? '');
  // ✅ Date range
  $date_from = sanitize_text_field($_POST['date_from'] ?? '');
  $date_to   = sanitize_text_field($_POST['date_to'] ?? '');

  // If only one is set, treat it as single-day range
  if ($date_from && !$date_to) $date_to = $date_from;
  if ($date_to && !$date_from) $date_from = $date_to;

  if ($date_from && $date_to) {
    // Amelia stores bookingStart in UTC. The date_from/date_to values come from
    // the user in the site's local timezone, so convert the local day boundaries
    // to UTC before comparing. This ensures that, e.g. a 7am local appointment
    // falls on the correct calendar day rather than leaking to the previous day.
    try {
      $site_tz = wp_timezone();
      $start_local = new DateTime($date_from . ' 00:00:00', $site_tz);
      $end_local   = new DateTime($date_to   . ' 23:59:59', $site_tz);
      $start_local->setTimezone(new DateTimeZone('UTC'));
      $end_local->setTimezone(new DateTimeZone('UTC'));
      $start_dt = $start_local->format('Y-m-d H:i:s');
      $end_dt   = $end_local->format('Y-m-d H:i:s');
    } catch (Exception $e) {
      // Fallback to raw literals if datetime parsing ever fails
      $start_dt = $date_from . ' 00:00:00';
      $end_dt   = $date_to   . ' 23:59:59';
    }
    $where .= $wpdb->prepare(" AND a.bookingStart BETWEEN %s AND %s", $start_dt, $end_dt);
  }
  // if ($date) {
  //   $where .= $wpdb->prepare(" AND DATE(a.bookingStart) = %s", $date);
  // }
  if ($mode === 'history' && $customer) {
    $where .= $wpdb->prepare(" AND u.id = %d", $customer);
  }

  $total = $wpdb->get_var("
        SELECT COUNT(DISTINCT a.id)
        FROM $a a
        INNER JOIN $cb cb ON cb.appointmentId = a.id
        INNER JOIN $u u ON u.id = cb.customerId
        WHERE $where
    ");
  $per_page = $limit;
  $total_pages = ceil($total  / $per_page);
  $current_page = $page;
  $results = $wpdb->get_results($wpdb->prepare("
        SELECT 
            a.id,
            a.bookingStart,
            a.status,
            a.bookingEnd,
            CONCAT(u.firstName,' ',u.lastName) customer,
            u.id customer_id
        FROM $a a
        INNER JOIN $cb cb ON cb.appointmentId = a.id
        INNER JOIN $u u ON u.id = cb.customerId
        WHERE $where
        ORDER BY a.bookingStart ASC
        LIMIT %d OFFSET %d
    ", $limit, $offset));

  ob_start();
  $html = '';
  if (!empty($results)) {
    foreach ($results as $r) {

      // ✅ Correct Amelia-compatible time
      $start = strtotime($r->bookingStart);
      $end   = strtotime($r->bookingEnd);

      $html .= "
        <tr>
            <td>" . wp_date('d/m/Y', $start) . "</td>
            <td>
                <strong>" . wp_date('g:i a', $start) . " - " . wp_date('g:i a', $end) . "</strong>
                <div class='sub-id'>" . round(($end - $start) / 60) . " min</div>
            </td>
            <td><a class='customer-name-link' href='" . admin_url('admin.php?page=wpamelia-customers#/manage/' . $r->customer_id . '/details') . "'><strong>{$r->customer}</strong><svg class='customer-link-icon' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6'/><polyline points='15 3 21 3 21 9'/><line x1='10' y1='14' x2='21' y2='3'/></svg></a>
              <div class='sub-id'>#ID{$r->id}</div>
            </td>
             <td><span class='status-badge status-{$r->status}'>" . ucfirst($r->status) . "</span></td>
         
        <td class='action-cell'>
          " . render_appointment_action_buttons($r->id, $r->customer_id, $r->customer) . "
      </td>
        </tr>";
    }
  } else {
    $html .= '<tr class="no-data">
    <td colspan="5" style="text-align:center; padding:60px 20px;">
        <div style="display:inline-block; text-align:center;">
            <svg width="70" height="70" viewBox="0 0 24 24" fill="none" stroke="#FFB5C5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M8 15s1.5 2 4 2 4-2 4-2"></path>
                <line x1="9" y1="9" x2="9.01" y2="9"></line>
                <line x1="15" y1="9" x2="15.01" y2="9"></line>
            </svg>
            <h3 style="margin:16px 0 8px; color:#5A4A5A; font-family:Quicksand, sans-serif; font-weight:600;">No Bookings Found</h3>
            <p style="margin:0; color:#8A7A8A; font-size:14px; font-family:Quicksand, sans-serif;">Try adjusting your search or date filters~</p>
        </div>
    </td>
</tr>';
  }
  $pagination = render_ajax_pagination($total_pages, $current_page);
  wp_send_json_success([
    'html'  => $html,
    'pages' => ceil($total / $limit),
    'pagination' => $pagination,
    'page'  => $page
  ]);
}
function render_appointment_action_buttons($appointment_id, $customer_id, $name)
{
  $data = get_appointment_ref_btn($appointment_id);
  $plugin_url = WPAMELIA_ADDON_PLUGIN_URL;
  $totalBooking = countBookingsByCustomerId($customer_id);
  $returningCustomer = returningCustomerCount($totalBooking);
  $historyTitle = isset($returningCustomer['Name']) ? $returningCustomer['Name'] . ' ' . $returningCustomer['Count'] : 'View History';
  $Countbooking = isset($returningCustomer['Count']) ? $returningCustomer['Count'] : '0';
  
  /* ---------- KAWAII SVG ICONS ---------- */
  $icon_history = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/><path d="M3 12a9 9 0 0 1 .5-3"/><path d="M6.5 4.5L4 7h3"/></svg>';
  
  $icon_view = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M2 12c2-4 6-7 10-7s8 3 10 7c-2 4-6 7-10 7s-8-3-10-7"/></svg>';
  
  $icon_body = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="3"/><path d="M12 8v4"/><path d="M8 12l4 2 4-2"/><path d="M9 22l3-8 3 8"/></svg>';
  
  $icon_attend = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="3"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M9 12l2 2 4-4"/></svg>';
  
  $icon_referral = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="3"/><circle cx="17" cy="17" r="3"/><path d="M9 10v4c0 2 1 3 3 3h2"/><path d="M14 17h0"/></svg>';

  /* ---------- ATTEND BUTTON ---------- */
  $attend_url = admin_url('admin.php?page=wpamelia-bookings#/book-appointment/' . $appointment_id . '/details');
  if ($data['attend'] === 'Yes') {
    $attend_btn = '<a title="Attended" href="' . $attend_url . '"
            class="action_refered amelia-btn-primary el-button el-button--primary attended">
            ' . $icon_attend . '</a>';
  } elseif ($data['attend'] === 'No' || $data['attend'] === 'Sick or Away') {
    $attend_btn = '<a title="Not Attended" href="' . $attend_url . '"
            class="action_refered amelia-btn-primary el-button el-button--primary no-attend">
            ' . $icon_attend . '</a>';
  } else {
    $attend_btn = '<a title="Empty" href="' . $attend_url . '"
            class="action_refered amelia-btn-primary el-button el-button--primary no-data">
            ' . $icon_attend . '</a>';
  }

  /* ---------- REFERRAL BUTTON ---------- */
  $referral_url = admin_url('admin.php?page=wpamelia-bodychart&id=' . $appointment_id . '&referal=true');
  if ($data['referral'] === 'Yes') {
    $ref_btn = '<a title="Referred" href="' . $referral_url . '"
            class="action_refered amelia-btn-primary el-button el-button--primary referred">
            ' . $icon_referral . '</a>';
  } elseif ($data['referral'] === 'No') {
    $ref_btn = '<a title="Not Referred" href="' . $referral_url . '"
            class="action_refered amelia-btn-primary el-button el-button--primary no-referred">
            ' . $icon_referral . '</a>';
  } elseif ($data['referral'] === 'Info') {
    $ref_btn = '<a title="Info Only" href="' . $referral_url . '"
            class="action_refered amelia-btn-primary el-button el-button--primary info">
            ' . $icon_referral . '</a>';
  } else {
    $ref_btn = '<a title="Empty" href="' . $referral_url . '"
            class="action_refered amelia-btn-primary el-button el-button--primary no-data">
            ' . $icon_referral . '</a>';
  }

  /* ---------- FINAL HTML (KAWAII STYLE) ---------- */
  // Add count-multiple class if booking count > 1
  $countClass = '';
  if ($Countbooking !== '1') {
    $countClass = ' count-multiple';
  }
  
  return '
    <div class="addon_amelia_btns_ref d-flex">
        <button title="' . $historyTitle . '" data-id="' . $appointment_id . '" data-customer="' . $customer_id . '" data-customer-name="' . $name . '"
            class="view-history amelia-btn-primary el-button el-button--primary"
            data-id="' . $appointment_id . '" data-customer="' . $customer_id . '" data-customer-name="' . $name . '">
            ' . $icon_history . '
            <span class="booking-count' . $countClass . '">' . $Countbooking . '</span>
        </button>

        <button title="View Details" data-id="' . $appointment_id . '"
            class="view_appoinment amelia-btn-primary el-button el-button--primary">
            ' . $icon_view . '
        </button>

        <a title="Body Chart"
            href="' . admin_url('admin.php?page=wpamelia-bodychart&id=' . $appointment_id) . '"
            class="body_chart amelia-btn-primary el-button el-button--primary">
            ' . $icon_body . '
        </a>

        ' . $attend_btn . '
        ' . $ref_btn . '
    </div>';
}
function countBookingsByCustomerId($customerId)
{
  global $wpdb;

  // Table name (prefix is dynamic in WordPress)
  $table_name = $wpdb->prefix . 'amelia_customer_bookings';

  // Query to count the bookings for the given customerId
  $query = $wpdb->prepare(
    "SELECT COUNT(*) FROM $table_name WHERE customerId = %d",
    $customerId
  );

  // Execute the query and fetch the result
  $total_bookings = $wpdb->get_var($query);

  return $total_bookings;
}
function returningCustomerCount($total_count)
{
  // Array to store categories with values
  // Initialize an array to hold results
  $result = [];

  // Determine name, count, and color code based on total_count
  if ($total_count == 1) {
    $result = [
      "Name" => "New",
      "Count" => "1",
      "Color" => "Green"
    ];
  } elseif ($total_count >= 2 && $total_count <= 4) {
    $result = [
      "Name" => "Returning",
      "Count" => "2+",
      "Color" => "Blue"
    ];
  } elseif ($total_count >= 5) {
    $result = [
      "Name" => "LongStanding",
      "Count" => "5+",
      "Color" => "Orange"
    ];
  } else {
    return array();
  }

  return $result;
}


function render_ajax_pagination($total_pages, $current_page)
{
  if ($total_pages <= 1) return '';

  $html = '<div class="pagination">';

  $range = 2;

  if ($current_page > 1) {
    $html .= '<a href="#" data-page="' . ($current_page - 1) . '">&laquo;</a>';
  }

  for ($i = 1; $i <= $total_pages; $i++) {
    if (
      $i == 1 ||
      $i == $total_pages ||
      ($i >= $current_page - $range && $i <= $current_page + $range)
    ) {
      $active = ($i == $current_page) ? 'active' : '';
      $html .= '<a href="#" class="' . $active . '" data-page="' . $i . '">' . $i . '</a>';
    } elseif (
      $i == $current_page - $range - 1 ||
      $i == $current_page + $range + 1
    ) {
      $html .= '<span class="dots">...</span>';
    }
  }

  if ($current_page < $total_pages) {
    $html .= '<a href="#" data-page="' . ($current_page + 1) . '">&raquo;</a>';
  }

  $html .= '</div>';
  return $html;
}
