<?php
/**
 * Number Checker
 * Scans Amelia customers for phone numbers without a country code
 * and allows bulk-applying a country code (+XX) to selected entries.
 *
 * @since 9.7.1
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<style>
.nc-wrap {
    background: #D4F1E8;
    padding: 30px;
    border-radius: 16px;
    font-family: 'Quicksand', sans-serif;
    max-width: 1100px;
    margin-top: 20px;
}
.nc-wrap h1 {
    font-family: 'Quicksand', sans-serif;
    font-weight: 700;
    font-size: 28px;
    color: #5A4A5A;
    margin-bottom: 24px;
}
.nc-card {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(255, 181, 197, 0.12);
    margin-bottom: 20px;
}
.nc-card h2 {
    font-family: 'Quicksand', sans-serif;
    font-weight: 700;
    font-size: 18px;
    color: #5A4A5A;
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.nc-card h2 .dot {
    width: 10px;
    height: 10px;
    background: linear-gradient(135deg, #FFB5C5 0%, #E8829A 100%);
    border-radius: 50%;
}
.nc-info {
    background: #FFF9F5;
    border: 2px solid #FFE4EC;
    border-radius: 12px;
    padding: 14px 18px;
    color: #5A4A5A;
    font-size: 13.5px;
    line-height: 1.6;
    margin-bottom: 18px;
}
.nc-info code { background: #fff; padding: 1px 6px; border-radius: 4px; color: #E8829A; }
.nc-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #98D9C2 0%, #5FBDA0 100%);
    color: white;
    font-family: 'Quicksand', sans-serif;
    font-weight: 700;
    font-size: 14px;
    border-radius: 25px;
    border: none;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 4px 16px rgba(152, 217, 194, 0.35);
}
.nc-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(152, 217, 194, 0.45); }
.nc-btn:disabled { background: #ccc; cursor: not-allowed; transform: none; box-shadow: none; }
.nc-btn.danger { background: linear-gradient(135deg, #E8829A 0%, #D63384 100%); }
.nc-stat-row {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-top: 16px;
}
.nc-stat {
    flex: 1 1 140px;
    background: #FFF9F5;
    border: 2px solid #FFE4EC;
    border-radius: 12px;
    padding: 14px;
    text-align: center;
}
.nc-stat .n { font-size: 24px; font-weight: 700; color: #5FBDA0; }
.nc-stat .l { font-size: 12px; color: #8A7A8A; margin-top: 4px; }
.nc-apply-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    background: #FFF9F5;
    border: 2px solid #FFE4EC;
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 16px;
}
.nc-apply-bar label { font-weight: 600; color: #5A4A5A; }
.nc-apply-bar input[type="text"] {
    width: 90px;
    font-size: 15px;
    padding: 8px 10px;
    border: 2px solid #98D9C2;
    border-radius: 8px;
    text-align: center;
    font-family: 'Quicksand', sans-serif;
    font-weight: 600;
}
.nc-table { width: 100%; border-collapse: collapse; background: white; }
.nc-table th, .nc-table td {
    padding: 10px 12px;
    text-align: left;
    border-bottom: 1px solid #FFE4EC;
    font-size: 14px;
    color: #5A4A5A;
}
.nc-table thead th { background: #FFF9F5; font-weight: 700; color: #5A4A5A; }
.nc-table tbody tr:hover { background: #FFF9F5; }
.nc-table .chk { width: 44px; text-align: center; }
.nc-table .phone-raw { font-family: Consolas, monospace; color: #E8829A; font-weight: 600; }
.nc-preview { font-family: Consolas, monospace; color: #5FBDA0; font-weight: 600; }
.nc-meta { color: #8A7A8A; font-size: 12px; margin-top: 2px; }
.nc-empty {
    text-align: center;
    padding: 40px 20px;
    color: #8A7A8A;
    font-size: 14px;
}
.nc-log {
    background: #1E1E1E;
    color: #98D9C2;
    font-family: Consolas, monospace;
    font-size: 12px;
    padding: 12px 16px;
    border-radius: 8px;
    max-height: 200px;
    overflow-y: auto;
    margin-top: 14px;
    display: none;
}
.nc-log.active { display: block; }
.nc-log .err { color: #FF8A8A; }
.nc-log .ok { color: #98D9C2; }
</style>

<div class="wrap">
    <div class="nc-wrap">
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#5FBDA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
            </svg>
            Number Checker
        </h1>

        <div class="nc-card">
            <h2><span class="dot"></span>About This Tool</h2>
            <div class="nc-info">
                Scans the Amelia customers table for phone numbers that need cleaning.
                A number is flagged if it <strong>does not start with <code>+</code> or <code>00</code></strong>
                (missing country code) OR if it contains <strong>any non-digit characters</strong> — parentheses,
                dashes, commas, spaces, letters, etc.
                <br><br>
                Enter a country code (without the <code>+</code> sign) only if some of your numbers are missing one.
                For numbers that already start with <code>+</code> (and just need formatting cleaned), the country
                code field can be left blank. Apply will:
                <ul style="margin: 6px 0 0 18px; padding: 0;">
                    <li>Strip every non-digit character from the number (preserving the leading <code>+</code>).</li>
                    <li>Prepend <code>+XX</code> to any number that is missing a country code (using the code you entered).</li>
                    <li>Convert leading <code>00</code> to <code>+</code>.</li>
                </ul>
                <br>
                <strong>Preventing this in the future:</strong>
                <ul style="margin: 6px 0 0 18px; padding: 0;">
                    <li>On your public booking form: v9.7.1 already adds a visible red warning AND disables the
                        <em>Continue</em> button until the customer picks a country code — no bare numbers can get through.</li>
                    <li>For staff-entered customers via the Amelia admin: switch the phone field to the same
                        intl-tel-input style so you always get a <code>+</code> prefix.</li>
                </ul>
            </div>

            <button type="button" class="nc-btn" id="nc-scan-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                Scan Now
            </button>

            <div class="nc-stat-row" id="nc-stats" style="display: none;">
                <div class="nc-stat"><div class="n" id="nc-total-rows">0</div><div class="l">Missing Country Code</div></div>
                <div class="nc-stat"><div class="n" id="nc-total-selected">0</div><div class="l">Selected</div></div>
                <div class="nc-stat"><div class="n" id="nc-total-applied">0</div><div class="l">Applied</div></div>
            </div>
        </div>

        <div class="nc-card" id="nc-results-card" style="display: none;">
            <h2><span class="dot"></span>Numbers Missing Country Code</h2>

            <div class="nc-apply-bar">
                <label for="nc-country-code">Country code (digits only, optional for formatting-only fixes):</label>
                <span style="font-weight: 700; color: #5FBDA0; font-size: 18px;">+</span>
                <input type="text" id="nc-country-code" placeholder="61" maxlength="4" inputmode="numeric" pattern="[0-9]*">
                <button type="button" class="nc-btn" id="nc-select-all">Select All</button>
                <button type="button" class="nc-btn" id="nc-select-none" style="background: linear-gradient(135deg, #E0E0E0 0%, #BDBDBD 100%);">Clear</button>
                <button type="button" class="nc-btn danger" id="nc-apply-btn" style="margin-left: auto;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Clean &amp; Apply
                </button>
            </div>

            <table class="nc-table" id="nc-table">
                <thead>
                    <tr>
                        <th class="chk"><input type="checkbox" id="nc-head-chk"></th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Current Phone</th>
                        <th>Reason</th>
                        <th>Preview After Apply</th>
                    </tr>
                </thead>
                <tbody id="nc-tbody">
                    <tr><td colspan="6" class="nc-empty">Click "Scan Now" to find customers with phone numbers that need cleaning.</td></tr>
                </tbody>
            </table>

            <div class="nc-log" id="nc-log"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var rows = [];
    var appliedCount = 0;

    function logLine(msg, cls) {
        var $l = $('#nc-log').addClass('active');
        $l.append('<div class="' + (cls || '') + '">[' + new Date().toLocaleTimeString() + '] ' + msg + '</div>');
        $l[0].scrollTop = $l[0].scrollHeight;
    }

    function updateSelectedCount() {
        $('#nc-total-selected').text($('.nc-row-chk:checked').length);
    }

    function updatePreviews() {
        var code = ($('#nc-country-code').val() || '').replace(/\D/g, '');
        $('.nc-row').each(function() {
            var $row = $(this);
            var raw = String($row.data('phone') || '').trim();
            var missing = $row.data('missing-cc') === true || $row.data('missing-cc') === 'true' || $row.data('missing-cc') === 1;
            var $prev = $row.find('.nc-preview');
            if (!raw) { $prev.text(''); return; }

            var firstChar = raw.charAt(0);
            var newVal;
            if (firstChar === '+') {
                // Preserve '+', strip everything non-digit from the rest
                newVal = '+' + raw.slice(1).replace(/\D/g, '');
            } else if (raw.slice(0, 2) === '00') {
                newVal = '+' + raw.slice(2).replace(/\D/g, '');
            } else {
                // Missing country code — need one from the input
                if (!code) {
                    $prev.text('(enter country code above)');
                    return;
                }
                var digits = raw.replace(/\D/g, '').replace(/^0+/, '');
                newVal = '+' + code + digits;
            }
            $prev.text(newVal);
        });
    }

    $('#nc-scan-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Scanning…');
        rows = [];

        $.post(ajax.url, {
            action: 'amelia_nc_scan',
            nonce: ajax.nonce
        }, function(resp) {
            $btn.prop('disabled', false).html(
                '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg> Scan Again'
            );

            if (!resp || !resp.success) {
                alert('Scan failed: ' + ((resp && resp.data && resp.data.message) || 'unknown error'));
                return;
            }

            rows = resp.data.rows || [];
            $('#nc-total-rows').text(rows.length);
            $('#nc-stats').css('display', 'flex');
            $('#nc-results-card').show();

            var $tbody = $('#nc-tbody').empty();

            if (rows.length === 0) {
                $tbody.append('<tr><td colspan="6" class="nc-empty">No customers with phone numbers that need cleaning. Everyone looks good.</td></tr>');
                return;
            }

            rows.forEach(function(r) {
                var reasonColor = r.missing_cc && r.has_junk
                    ? '#E8829A'
                    : (r.missing_cc ? '#E8829A' : '#F5A623');
                var $tr = $(
                    '<tr class="nc-row" data-id="' + r.id + '" data-phone="' + $('<div/>').text(r.phone).html() + '" data-missing-cc="' + (r.missing_cc ? 'true' : 'false') + '">' +
                        '<td class="chk"><input type="checkbox" class="nc-row-chk" value="' + r.id + '"></td>' +
                        '<td><strong>' + $('<div/>').text(r.name || '').html() + '</strong><div class="nc-meta">#ID ' + r.id + '</div></td>' +
                        '<td>' + $('<div/>').text(r.email || '').html() + '</td>' +
                        '<td><span class="phone-raw">' + $('<div/>').text(r.phone).html() + '</span></td>' +
                        '<td><span style="color: ' + reasonColor + '; font-weight: 600;">' + $('<div/>').text(r.reason || '').html() + '</span></td>' +
                        '<td><span class="nc-preview">(enter country code above)</span></td>' +
                    '</tr>'
                );
                $tbody.append($tr);
            });

            updatePreviews();
            updateSelectedCount();
        });
    });

    $('#nc-head-chk').on('change', function() {
        $('.nc-row-chk').prop('checked', this.checked);
        updateSelectedCount();
    });

    $(document).on('change', '.nc-row-chk', updateSelectedCount);

    $('#nc-select-all').on('click', function() {
        $('.nc-row-chk, #nc-head-chk').prop('checked', true);
        updateSelectedCount();
    });
    $('#nc-select-none').on('click', function() {
        $('.nc-row-chk, #nc-head-chk').prop('checked', false);
        updateSelectedCount();
    });

    $('#nc-country-code').on('input', function() {
        this.value = this.value.replace(/\D/g, '');
        updatePreviews();
    });

    $('#nc-apply-btn').on('click', function() {
        var code = ($('#nc-country-code').val() || '').replace(/\D/g, '');
        var $selected = $('.nc-row-chk:checked');
        var ids = $selected.map(function() { return $(this).val(); }).get();
        if (!ids.length) { alert('Select at least one number.'); return; }

        // Check if any selected row is missing a country code — if so, code is required
        var anyMissingCC = false;
        $selected.each(function() {
            var $row = $(this).closest('.nc-row');
            var missing = $row.data('missing-cc') === true || $row.data('missing-cc') === 'true' || $row.data('missing-cc') === 1;
            if (missing) anyMissingCC = true;
        });
        if (anyMissingCC && !code) {
            alert('Some selected numbers are missing a country code. Enter one in the field above (digits only) before applying.');
            return;
        }

        var msg = anyMissingCC
            ? 'Clean ' + ids.length + ' number(s) and prefix any missing ones with +' + code + '?'
            : 'Clean formatting on ' + ids.length + ' number(s)?';
        if (!confirm(msg + ' This will modify the Amelia customers table.')) return;

        var $btn = $(this);
        $btn.prop('disabled', true).text('Applying…');
        logLine('Cleaning ' + ids.length + ' customer(s)' + (code ? ' (country code +' + code + ' for missing ones)' : '') + '…', 'ok');

        $.post(ajax.url, {
            action: 'amelia_nc_apply',
            nonce: ajax.nonce,
            country_code: code,
            customer_ids: ids
        }, function(resp) {
            $btn.prop('disabled', false).html(
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Clean &amp; Apply'
            );

            if (!resp || !resp.success) {
                logLine('Apply failed: ' + ((resp && resp.data && resp.data.message) || 'unknown error'), 'err');
                alert('Apply failed: ' + ((resp && resp.data && resp.data.message) || 'unknown error'));
                return;
            }

            var updated = resp.data.updated || [];
            appliedCount += updated.length;
            $('#nc-total-applied').text(appliedCount);

            updated.forEach(function(u) {
                var $row = $('.nc-row[data-id="' + u.id + '"]');
                $row.find('.phone-raw').text(u.new_phone);
                $row.data('phone', u.new_phone);
                $row.find('.nc-row-chk').prop('checked', false);
                // Animate row fading to green then removing from the list
                $row.css('transition', 'background 0.4s ease, opacity 0.6s ease').css('background', '#D4F1E8');
                setTimeout(function() {
                    $row.fadeOut(500, function() { $(this).remove(); updateRowCount(); });
                }, 900);
                logLine('Customer #' + u.id + ' → ' + u.new_phone, 'ok');
            });
            (resp.data.errors || []).forEach(function(e) {
                logLine('Customer #' + e.id + ': ' + e.message, 'err');
            });

            updateSelectedCount();
        });
    });

    function updateRowCount() {
        var remaining = $('.nc-row').length;
        $('#nc-total-rows').text(remaining);
        if (remaining === 0) {
            $('#nc-tbody').append('<tr><td colspan="6" class="nc-empty">All selected numbers have been updated. Click "Scan Again" to refresh.</td></tr>');
        }
    }
});
</script>
