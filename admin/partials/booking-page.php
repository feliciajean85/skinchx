<div class="wrap amelia-booking-wrap">
  <h1 class="wp-heading-inline">Bookings</h1>
  <div class="d-flex justify-content-between">
    <input type="text" id="booking-search" placeholder="Search customer..." />
    <!-- <input type="date" id="booking-date" placeholder="Select date" /> -->
    <div class="d-flex" style="gap:10px; align-items:center;">
      <input
        type="text"
        id="booking-date-range"
        placeholder="Select date range"
        readonly />
      <input type="hidden" id="date-from">
      <input type="hidden" id="date-to">
      <button type="button" id="booking-date-clear" class="button">
        Clear
      </button>
    </div>
  </div>

  <button id="back-to-list" class="button" style="display:none;">← Back</button>
  <table class="amelia-table">
    <thead>
      <tr>
        <th>DATE</th>
        <th>TIME</th>
        <th>CUSTOMER</th>
        <th>STATUS</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody id="booking-table-body">
      <tr>
        <td colspan="5">Loading...</td>
      </tr>
    </tbody>
  </table>

  <div id="booking-pagination" class="amelia-pagination"></div>
</div>