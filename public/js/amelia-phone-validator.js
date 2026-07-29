/**
 * Amelia Phone Helper Note (v9.7.1)
 *
 * Adds a small static helper note underneath every phone field on the
 * Amelia booking form. Does NOT block progression or flag invalid state.
 *
 * Note text: "The country code is required to receive SMS notifications."
 *
 * Amelia's booking form is Vue-rendered after page load, so we use a
 * MutationObserver to detect when phone inputs appear / reappear.
 */
(function() {
    'use strict';

    var NOTE_CLASS = 'apv-phone-note';
    var NOTE_TEXT = 'The country code is required to receive SMS notifications.';

    // Inject CSS once
    (function injectCss() {
        if (document.getElementById('apv-phone-note-css')) return;
        var style = document.createElement('style');
        style.id = 'apv-phone-note-css';
        style.textContent =
            '.' + NOTE_CLASS + ' {' +
            '  display: block;' +
            '  color: #5A4A5A;' +
            '  font-size: 12.5px;' +
            '  font-weight: 500;' +
            '  margin: 6px 0 0 0;' +
            '  padding: 6px 10px;' +
            '  background: #FFF9F5;' +
            '  border: 1px solid #FFE4EC;' +
            '  border-radius: 6px;' +
            '  line-height: 1.4;' +
            '}';
        document.head.appendChild(style);
    })();

    function findRow(input) {
        var iti = input.closest ? input.closest('.iti') : null;
        if (iti) return iti.parentElement || iti;
        var formItem = input.closest ? input.closest('.el-form-item, .amelia-form-item, .amelia-input-group, .amelia-field') : null;
        if (formItem) return formItem;
        return input.parentElement || input;
    }

    function addNote(input) {
        if (input.dataset.apvNoted === '1') return;
        var row = findRow(input);
        if (!row) return;
        if (row.querySelector('.' + NOTE_CLASS)) {
            input.dataset.apvNoted = '1';
            return;
        }
        var div = document.createElement('div');
        div.className = NOTE_CLASS;
        div.textContent = NOTE_TEXT;
        row.appendChild(div);
        input.dataset.apvNoted = '1';
    }

    function scanScope(root) {
        var scopes;
        if (root.querySelectorAll) {
            scopes = root.querySelectorAll('.amelia-app-booking, .amelia-v2-booking');
        } else {
            scopes = [];
        }
        if (!scopes.length && root.matches &&
            (root.matches('.amelia-app-booking') || root.matches('.amelia-v2-booking'))) {
            scopes = [root];
        }
        scopes.forEach(function(scope) {
            scope.querySelectorAll('input[type="tel"]').forEach(addNote);
        });
    }

    function boot() {
        scanScope(document);

        var observer = new MutationObserver(function(mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var m = mutations[i];
                for (var j = 0; j < m.addedNodes.length; j++) {
                    var n = m.addedNodes[j];
                    if (n.nodeType !== 1) continue;
                    if ((n.matches && (n.matches('input[type="tel"]') || n.matches('.amelia-app-booking') || n.matches('.amelia-v2-booking'))) ||
                        (n.querySelector && n.querySelector('input[type="tel"]'))) {
                        scanScope(n);
                    }
                }
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
