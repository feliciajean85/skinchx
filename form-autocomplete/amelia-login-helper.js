jQuery(function ($) {
  var WRAP_SELECTOR = '.amelia-v2-booking';
  var EMAIL_SELECTOR = WRAP_SELECTOR + ' input.el-input__inner[type="email"], ' +
    WRAP_SELECTOR + ' input.el-input__inner[name="email"], ' +
    WRAP_SELECTOR + ' input.el-input__inner[placeholder="Enter email"]';
  var FIELD_SELECTOR = WRAP_SELECTOR + ' input.el-input__inner[type="email"], ' +
    WRAP_SELECTOR + ' input, ' +
    WRAP_SELECTOR + ' select';
  var DEBOUNCE_MS = 400;
  var applyAnswered = false;
  if (ameliaLoginHelper.is_logged) {
    $(document).on('click', '.el-form input, .el-form select, .el-form textarea, .el-form button, .el-form file', function () {
      if (!applyAnswered) {
        applyAnswered = true;

        applyAnswers(); // Trigger the AJAX call

      }
    });
  }

  // Function to bind click event for all types of fields (input, select, textarea, radio, checkbox)
  // function bindAnyFieldListener() {
  //   const $fields = $('.am-fs__info-form :input');
  //   alert('ds')
  //   if (!$fields.length) return;

  //   $fields.each(function () {
  //     const $el = $(this);
  //     if ($el.data('amelia-any-bound')) return;

  //     $el.data('amelia-any-bound', 1);

  //     $el.on('focus click change', function () {
  //       if (window.__ameliaAppliedOnce) return;
  //       window.__ameliaAppliedOnce = true;
  //       applyAnswers();
  //     });
  //   });
  // }
  if (ameliaLoginHelper.is_logged) {
    if ($(WRAP_SELECTOR).length) {
      var ameliaserviceid = $('.amelia-service-id').val();

      $.post(ameliaLoginHelper.ajax_url, {
        action: 'amelia_user_details',

        service: ameliaserviceid

      }).done(function (resp) {
        if (resp && resp.success && resp.data && resp.data.details && resp.data.details.question_answer) {
          $('input[name="firstName"]').val(resp.data.details.first_name);
          $('input[name="lastName"]').val(resp.data.details.last_name);
          $('input[name="phone"]').val(resp.data.details.phone);
          autoFillAmelia(resp.data.details.question_answer);
          //setTimeout(function(){ location.reload(); }, 900);
        } else {

        }
      });
    }
  } else {
    function debounce(fn, wait) {
      var t; return function () { clearTimeout(t); var a = arguments, c = this; t = setTimeout(function () { fn.apply(c, a); }, wait); };
    }

    function ensurePopup() {
      if ($('#amelia-login-popup').length) return;

      var $popup = $(`
      <div id="amelia-login-popup" class="amelia-popup-overlay" style="display:none">
        <div class="amelia-popup" role="dialog" aria-modal="true" aria-labelledby="amelia-login-title">
          <button type="button" class="amelia-close" aria-label="Close">&times;</button>
          <h3 id="amelia-login-title" style="margin:0 0 8px">Welcome back!</h3>
          <p style="margin:0 0 12px">Would you like to login and autofill this form?</p>

          <input type="email" id="amelia-email" placeholder="Email address" required>
          <input type="password" id="amelia-password" placeholder="Password" required>

          <div class="amelia-actions" style="display:flex; gap:8px; margin-top:10px">
            <button id="amelia-login-btn" class="button button-primary" style="flex:1">Login</button>
            <button id="amelia-send-link" class="button" style="flex:1">Set Password via Email</button>
          </div>

          <div class="amelia-extra" style="margin-top:12px; font-size:13px; line-height:1.5;">
            Didn’t create an account, or forgot your password?
            <a href="#" id="amelia-send-username-pass">Click here</a>
            to get an email with your username and a new password.
          </div>

          <span class="amelia-message" style="display:block;margin-top:10px;font-size:13px;color:#d33;"></span>
        </div>
      </div>
    `);
      $('body').append($popup);

      var css = `
      #amelia-login-popup.amelia-popup-overlay{
        position:fixed; inset:0; background:rgba(0,0,0,.5);
        display:flex; align-items:center; justify-content:center; z-index:9999;
      }
      #amelia-login-popup .amelia-popup{
        position:relative;
        background:#fff; padding:20px 24px; border-radius:12px;
        width:min(380px, 92%); box-shadow:0 10px 30px rgba(0,0,0,.25);
      }
      #amelia-login-popup input{
        width:100%; margin:6px 0; padding:10px; border:1px solid #ddd; border-radius:8px;
      }
      #amelia-login-popup .button{ cursor:pointer; border:none; padding:10px; border-radius:8px; }
      #amelia-login-popup .button-primary{ background:#0073aa; color:#fff; }
      #amelia-login-popup .amelia-close{
        position:absolute; top:8px; right:8px; font-size:20px; line-height:20px;
        width:28px; height:28px; border:0; border-radius:50%; background:#f3f4f6; cursor:pointer;
      }
    `;
      $('head').append('<style id="amelia-login-helper-style">' + css + '</style>');
    }

    function openPopup(prefilledEmail) {
      ensurePopup();
      var $popup = $('#amelia-login-popup');
      $popup.show();
      if (prefilledEmail) $('#amelia-email').val(prefilledEmail);
      $('.amelia-message').css('color', '#d33').text('');
      $('#amelia-password').val('');
      $('#amelia-password').trigger('focus');
    }
    function closePopup() { $('#amelia-login-popup').hide(); }

    // close on X, overlay click, or ESC
    $(document).on('click', '#amelia-login-popup .amelia-close', function (e) { e.preventDefault(); closePopup(); });
    $(document).on('click', '#amelia-login-popup', function (e) { if (e.target.id === 'amelia-login-popup') closePopup(); });
    $(document).on('keydown', function (e) { if ($('#amelia-login-popup').is(':visible') && e.key === 'Escape') closePopup(); });

    // Listen on Amelia email input
    function bindEmailListener() {
      var $email = $(EMAIL_SELECTOR).first();
      if (!$email.length) return;

      $email.on('input', debounce(checkEmail, DEBOUNCE_MS));
      $email.on('change blur', checkEmail);

      function checkEmail() {
        var email = ($email.val() || '').replace(/\s+/g, '').trim();
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return;

        $.post(ameliaLoginHelper.ajax_url, {
          action: 'amelia_check_customer',
          nonce: ameliaLoginHelper.nonce,
          email: email
        }).done(function (resp) {
          if (!resp || resp.success !== true) return;
          if (resp.data && resp.data.exists === true) {
            if (!$('#amelia-login-popup').is(':visible')) openPopup(email);
          }
        });
      }
    }


    // Popup actions
    $(document).on('click', '#amelia-login-btn', function (e) {
      e.preventDefault();
      var email = ($('#amelia-email').val() || '').trim();
      var pass = ($('#amelia-password').val() || '').trim();
      var $msg = $('.amelia-message');
      var ameliaserviceid = $('.amelia-service-id').val();

      if (!email || !pass) { $msg.text('Please enter both email and password.'); return; }

      $msg.css('color', '#333').text('Logging in...');
      $.post(ameliaLoginHelper.ajax_url, {
        action: 'amelia_user_login',
        nonce: ameliaLoginHelper.nonce,
        service: ameliaserviceid,
        email: email,
        password: pass
      }).done(function (resp) {
        if (resp && resp.success) {
          $msg.css('color', 'green').text('Login successful! Reloading...');

          if (resp.data.details.question_answer) {
            $('input[name="firstName"]').val(resp.data.details.first_name);
            $('input[name="lastName"]').val(resp.data.details.last_name);
            $('input[name="phone"]').val(resp.data.details.phone);
            autoFillAmelia(resp.data.details.question_answer);

          }
          closePopup();
          //setTimeout(function(){ location.reload(); }, 900);
        } else {
          $msg.css('color', '#d33').text(resp?.data?.message || 'Login failed.');
        }
      });
    });


    $(document).on('click', '#amelia-send-link', function (e) {
      e.preventDefault();
      var email = ($('#amelia-email').val() || '').trim();
      var $msg = $('.amelia-message');
      if (!email) { $msg.text('Please enter your email first.'); return; }

      $msg.css('color', '#333').text('Sending password setup link...');
      $.post(ameliaLoginHelper.ajax_url, {
        action: 'amelia_send_password_link',
        nonce: ameliaLoginHelper.nonce,
        email: email
      }).done(function (resp) {
        if (resp && resp.success) {
          $msg.css('color', 'green').text(resp.data.message || 'Password setup link sent!');
        } else {
          $msg.css('color', '#d33').text(resp?.data?.message || 'Failed to send link.');
        }
      });
    });

    // NEW: Send username + random password (create user if needed)
    $(document).on('click', '#amelia-send-username-pass', function (e) {
      e.preventDefault();
      var email = ($('#amelia-email').val() || '').trim();
      var $msg = $('.amelia-message');
      if (!email) { $msg.text('Please enter your email first.'); return; }

      $msg.css('color', '#333').text('Sending username & new password...');
      $.post(ameliaLoginHelper.ajax_url, {
        action: 'amelia_send_username_password',
        nonce: ameliaLoginHelper.nonce,
        email: email
      }).done(function (resp) {
        if (resp && resp.success) {
          $msg.css('color', 'green').text(resp.data.message || 'Check your email for login details.');
        } else {
          $msg.css('color', '#d33').text(resp?.data?.message || 'Could not send credentials.');
        }
      });
    });

    // Init
    if ($(WRAP_SELECTOR).length) {

      bindEmailListener();

      // rebind if SPA injects the field later
      var observer = new MutationObserver(function () {
        if ($(EMAIL_SELECTOR).length && !$(EMAIL_SELECTOR).first().data('amelia-email-bound')) {
          $(EMAIL_SELECTOR).first().data('amelia-email-bound', '1');
          bindEmailListener();

        }
      });
      observer.observe(document.body, { childList: true, subtree: true });
    }

  }

  function autoCompleteFormData(data) {
    console.log(data);
  }
  //autofille
  const norm = s => String(s || '')
    .replace(/[–—−]/g, '-')
    .toLowerCase()
    .replace(/\s+/g, ' ')
    .trim();

  const fire = (el, type) => el && el.dispatchEvent(new Event(type, { bubbles: true }));

  // Find the closest .el-form-item by label text (label is inside .el-form-item__label)
  function findItemByLabelText(labelText) {
    const wanted = norm(labelText);
    const items = document.querySelectorAll('.el-form-item');
    for (const it of items) {
      const lbl = it.querySelector('.el-form-item__label');
      if (!lbl) continue;
      const txt = norm(lbl.innerText || lbl.textContent || '');
      if (!txt) continue;
      // exact or contains in either direction
      if (txt === wanted || txt.includes(wanted) || wanted.includes(txt)) {
        return it;
      }
    }
    return null;
  }

  // Set a text or textarea value
  function setTextLike(itemEl, value) {
    console.log(itemEl);
    const ta = itemEl.querySelector('textarea');
    const inp = itemEl.querySelector('input[type="text"], input[type="email"], input[type="number"], input:not([type])');
    const target = ta || inp;
    if (!target) return false;
    target.focus();
    target.value = value;
    fire(target, 'input');
    fire(target, 'change');
    target.blur();
    return true;
  }

  // Set a datepicker (Amelia/Element UI usually uses a text input + picker)
  function setDate(itemEl, value) {
    // Expecting YYYY-MM-DD from your payload
    const inp = itemEl.querySelector('input[type="text"], input[role="spinbutton"], input');
    if (!inp) return false;
    inp.focus();
    inp.value = value;
    fire(inp, 'input');
    fire(inp, 'change');
    inp.blur();
    return true;
  }

  // Click a radio by matching either input[value] or the visible option label
  function setRadio(itemEl, value) {
    const wanted = norm(value);
    const radios = itemEl.querySelectorAll('.el-radio, .am-radio');
    let picked = false;

    // 1) try value attribute match
    for (const r of radios) {
      const input = r.querySelector('input[type="radio"]');
      if (!input) continue;
      if (norm(input.value) === wanted) {
        input.click(); // element-ui will handle model updates
        picked = true; break;
      }
    }
    if (picked) return true;

    // 2) try visible label match
    for (const r of radios) {
      const lbl = r.querySelector('.el-radio__label');
      const input = r.querySelector('input[type="radio"]');
      const ltxt = norm(lbl ? (lbl.innerText || lbl.textContent) : '');
      if (ltxt === wanted || ltxt.includes(wanted) || wanted.includes(ltxt)) {
        (input || r).click();
        picked = true; break;
      }
    }

    // 3) fallback for ranges that don't exactly match: pick closest containing hyphen
    if (!picked && wanted.includes('-')) {
      for (const r of radios) {
        const lbl = r.querySelector('.el-radio__label');
        const ltxt = norm(lbl ? (lbl.innerText || lbl.textContent) : '');
        if (ltxt.match(/\d/) && (ltxt.includes('-') || ltxt.includes('+'))) {
          // loose overlap heuristic
          const numsWanted = wanted.replace(/[^\d+ -]/g, '');
          const numsLbl = ltxt.replace(/[^\d+ -]/g, '');
          if (numsLbl.split(' ').some(p => numsWanted.includes(p))) {
            (r.querySelector('input[type="radio"]') || r).click();
            picked = true; break;
          }
        }
      }
    }
    return picked;
  }

  // Click multiple checkboxes by matching value or label text
  function setCheckboxes(itemEl, values) {
    const want = Array.isArray(values) ? values.map(norm) : [norm(values)];
    const boxes = itemEl.querySelectorAll('.el-checkbox, .am-checkbox');
    let changed = false;

    for (const w of want) {
      // try value attribute first
      let matched = false;
      for (const c of boxes) {
        const input = c.querySelector('input[type="checkbox"]');
        if (!input) continue;
        if (norm(input.value) === w) {
          if (!input.checked) { input.click(); changed = true; }
          matched = true; break;
        }
      }
      if (matched) continue;

      // label text match
      for (const c of boxes) {
        const lbl = c.querySelector('.el-checkbox__label');
        const input = c.querySelector('input[type="checkbox"]');
        const ltxt = norm(lbl ? (lbl.innerText || lbl.textContent) : '');
        if (ltxt === w || ltxt.includes(w) || w.includes(ltxt)) {
          if (input && !input.checked) { input.click(); changed = true; }
          matched = true; break;
        }
      }
    }
    return changed;
  }

  // Fill one item based on type
  function fillByType(itemEl, type, value) {
    switch (String(type).toLowerCase()) {
      case 'text':
      case 'text-area':
        return setTextLike(itemEl, value);
      case 'datepicker':
      case 'date':
        return setDate(itemEl, value);
      case 'radio':
        return setRadio(itemEl, value);
      case 'checkbox':
        return setCheckboxes(itemEl, value);
      default:
        // try generic text fallback
        return setTextLike(itemEl, value);
    }
  }

  function fillFromAnswers(answersObj) {
    // answersObj is like { "2": {label, type, value}, ... }
    for (const key of Object.keys(answersObj)) {
      const { label, type, value } = answersObj[key] || {};
      if (!label) continue;
      const item = findItemByLabelText(label);
      if (!item) continue;
      fillByType(item, type, value);
    }
  }

  // Wait for Amelia form DOM (rendered dynamically) then fill
  function whenAmeliaReady(cb) {
    const hasForm = () => document.querySelector('.amelia-v2-booking, .am-fs__info-form, .el-form-item');
    if (hasForm()) {
      cb(); return;
    }
    const obs = new MutationObserver(() => {
      if (hasForm()) {
        obs.disconnect();
        cb();
      }
    });
    obs.observe(document.documentElement, { childList: true, subtree: true });
  }

  // Public entry to use AFTER your AJAX success
  window.autoFillAmelia = function (questionAnswerJsonString) {
    let answers;
    try {
      answers = JSON.parse(questionAnswerJsonString || '{}');
      console.log(answers); console.log('answers');
    } catch (e) {
      console.warn('Invalid question_answer JSON', e);
      return;
    }
    whenAmeliaReady(() => fillFromAnswers(answers));
  };
  //apply answer


  function applyAnswers() {
    if ($(WRAP_SELECTOR).length) {
      var ameliaserviceid = $('.amelia-service-id').val();
      var loadingMessage = `
      <div id="loading-popup" class="loading-popup-overlay">
        <div class="loading-popup">
          <p>Loading data, please wait...</p>
        </div>
      </div>
    `;
      $('body').append(loadingMessage);
      $.post(ameliaLoginHelper.ajax_url, {
        action: 'amelia_user_details',

        service: ameliaserviceid

      }).done(function (resp) {
        if (resp && resp.success && resp.data && resp.data.details && resp.data.details.question_answer) {
          $('input[name="firstName"]').val(resp.data.details.first_name);
          $('input[name="lastName"]').val(resp.data.details.last_name);
          $('input[name="phone"]').val(resp.data.details.phone);
          console.log(resp.data.details.first_name); console.log(resp.data.details.last_name); console.log(resp.data.details.phone)
          autoFillAmelia(resp.data.details.question_answer);
          $('#loading-popup').remove();
        } else {
          $('#loading-popup').remove();
        }
      }).fail(function (xhr, status, error) {
        console.error('AJAX error:', status, error);
      })
        .always(function () {
          $('#loading-popup').remove();
        });
    }
  }

  //ajax detection
  const XHR = window.XMLHttpRequest;
  if (!XHR || XHR.__patched) return;

  const open = XHR.prototype.open, send = XHR.prototype.send;
  XHR.prototype.open = function (method, url) { this.__url = url || ''; return open.apply(this, arguments); };
  XHR.prototype.send = function () {
    this.addEventListener('load', () => {
      if ((this.__url || '').includes('wpamelia_api') && this.__url.includes('call=/stats')) {
        applyAnswers(); // <-- your fetch+fill function
      }
    });
    return send.apply(this, arguments);
  };
  XHR.__patched = true;
});

// (function () {
//   const XHR = window.XMLHttpRequest;
//   if (!XHR || XHR.__patched) return;

//   const open = XHR.prototype.open, send = XHR.prototype.send;
//   XHR.prototype.open = function(method, url){ this.__url = url || ''; return open.apply(this, arguments); };
//   XHR.prototype.send = function(){
//     this.addEventListener('load', () => {
//       if ((this.__url || '').includes('wpamelia_api') && this.__url.includes('call=/stats')) {
//         applyAnswers(); // <-- your fetch+fill function
//       }
//     });
//     return send.apply(this, arguments);
//   };
//   XHR.__patched = true;
// })();
