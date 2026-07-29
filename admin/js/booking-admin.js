jQuery(function ($) {

  let state = {
    page: 1,
    mode: 'list',
    customer: 0,
    search: '',
    date_from: '',
    date_to: ''
  };

  // Default today
  const today = $.datepicker.formatDate('yy-mm-dd', new Date());
  state.date_from = today;
  state.date_to = today;
  $('#booking-date-range').val(today + ' → ' + today);

  function loadBookings() {
    $.post(ajax.url, {
      action: 'load_booking_list',
      nonce: ajax.nonce,
      page: state.page,
      mode: state.mode,
      customer: state.customer,
      date_from: state.date_from,
      date_to: state.date_to,
      search: state.search
    }, function (res) {
      if (!res.success) return;
      $('#booking-table-body').html(res.data.html);
      $('#booking-pagination').html(res.data.pagination);
    });
  }

  let isSelectingRange = false;

  /* ===================================================
   🔥 HARD OVERRIDE — STOP AUTO CLOSE
  =================================================== */
  const _hideDatepicker = $.datepicker._hideDatepicker;
  $.datepicker._hideDatepicker = function (input) {
    if (isSelectingRange) return;
    _hideDatepicker.call(this, input);
  };

  /* ===================================================
     DATE RANGE PICKER
  =================================================== */
  $('#booking-date-range').datepicker({
    dateFormat: 'yy-mm-dd',
    numberOfMonths: 2,
    changeMonth: true,
    changeYear: true,
    showButtonPanel: true,

    onSelect: function (dateText) {

      // START DATE
      if (!state.date_from || state.date_to) {
        state.date_from = dateText;
        state.date_to = '';
        isSelectingRange = true;

        $(this).val(state.date_from + ' → ');
      }
      // END DATE
      else {
        state.date_to = dateText;
        isSelectingRange = false;

        if (new Date(state.date_to) < new Date(state.date_from)) {
          [state.date_from, state.date_to] = [state.date_to, state.date_from];
        }

        $(this).val(state.date_from + ' → ' + state.date_to);

        loadBookings();

        // ✅ now allow close
        $.datepicker._hideDatepicker(this);
      }
    }
  });

  /* ===================================================
     CLEAR DATE
  =================================================== */
  $('#booking-date-clear').on('click', function () {
    state.date_from = '';
    state.date_to = '';
    $('#booking-date-range').val('');
    loadBookings();
  });

  /* ===================================================
     SEARCH
  =================================================== */
  $('#booking-search').on('keyup', function () {
    state.search = this.value;
    state.page = 1;
    loadBookings();
  });

  /* ===================================================
     PAGINATION
  =================================================== */
  $(document).on('click', '.pagination a', function (e) {
    e.preventDefault();
    state.page = $(this).data('page');
    loadBookings();
  });

  /* ===================================================
     HISTORY MODE
  =================================================== */
  $(document).on('click', '.view-history', function () {
    state.mode = 'history';
    state.customer = $(this).data('customer');
    state.page = 1;
    $('#booking-search').val($(this).data('customer-name'));
    $('#back-to-list').show();
    loadBookings();
  });

  $('#back-to-list').on('click', function () {
    state.mode = 'list';
    state.customer = 0;
    $('#booking-search').val('');
    state.page = 1;
    $(this).hide();
    loadBookings();
  });

  loadBookings();

  //tooltip
  document.addEventListener('click', function (e) {
    const el = e.target.closest('[data-tooltip], [title]');
    if (!el) return;

    // remove existing tooltip
    document.querySelectorAll('.mobile-global-tooltip').forEach(t => t.remove());

    const text = el.getAttribute('data-tooltip') || el.getAttribute('title');
    if (!text) return;

    const tooltip = document.createElement('div');
    tooltip.className = 'mobile-global-tooltip show';
    tooltip.innerText = text;

    document.body.appendChild(tooltip);

    const rect = el.getBoundingClientRect();
    tooltip.style.left = rect.left + rect.width / 2 + 'px';
    tooltip.style.top = rect.top - 10 + window.scrollY + 'px';
    tooltip.style.transform = 'translate(-50%, -100%)';

    // auto remove
    setTimeout(() => {
        tooltip.remove();
    }, 2000);
});

});
