(function () {
    'use strict';

    function pad(n) {
        return n.toString().padStart(2, '0');
    }

    var TIMER_COLORS = ['success', 'warning', 'danger'];

    function applyTimerColor(el, color) {
        var badge = el.classList.contains('badge');

        TIMER_COLORS.forEach(function (c) {
            el.classList.remove(badge ? 'bg-' + c : 'text-' + c);
        });
        el.classList.remove('text-white', 'text-dark');

        el.classList.add(badge ? 'bg-' + color : 'text-' + color);
        if (badge) {
            el.classList.add(color === 'warning' ? 'text-dark' : 'text-white');
        }
    }

    function tickCountdowns() {
        document.querySelectorAll('[data-countdown]').forEach(function (el) {
            var end = new Date(el.getAttribute('data-countdown')).getTime();
            var startAttr = el.getAttribute('data-session-start');
            var now = Date.now();
            var diff = Math.max(0, Math.floor((end - now) / 1000));

            var h = Math.floor(diff / 3600);
            var m = Math.floor((diff % 3600) / 60);
            var s = diff % 60;

            el.textContent = (h > 0 ? h + ':' : '') + pad(m) + ':' + pad(s);

            if (diff <= 0) {
                el.textContent = 'Time up';
                applyTimerColor(el, 'danger');
                return;
            }

            // Green while over half the session remains, yellow at the
            // halfway point, red as the session nears its end.
            var percentRemaining;
            if (startAttr) {
                var start = new Date(startAttr).getTime();
                var total = Math.max(1, Math.floor((end - start) / 1000));
                percentRemaining = diff / total;
            } else {
                percentRemaining = diff > 900 ? 1 : (diff > 300 ? 0.4 : 0.1);
            }

            if (percentRemaining > 0.5) {
                applyTimerColor(el, 'success');
            } else if (percentRemaining > 0.15) {
                applyTimerColor(el, 'warning');
            } else {
                applyTimerColor(el, 'danger');
            }
        });
    }

    // A centered Bootstrap modal standing in for window.confirm(), which
    // renders top-left and unstyled in some browsers. Built once and reused
    // for every [data-confirm] form on the page.
    var confirmModalEl = null;
    var confirmModal = null;
    var pendingConfirmForm = null;

    function ensureConfirmModal() {
        if (confirmModalEl) {
            return;
        }

        confirmModalEl = document.createElement('div');
        confirmModalEl.className = 'modal fade';
        confirmModalEl.tabIndex = -1;
        confirmModalEl.innerHTML =
            '<div class="modal-dialog modal-dialog-centered">' +
                '<div class="modal-content">' +
                    '<div class="modal-body py-4" data-confirm-message></div>' +
                    '<div class="modal-footer">' +
                        '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>' +
                        '<button type="button" class="btn btn-primary" data-confirm-ok>OK</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
        document.body.appendChild(confirmModalEl);
        confirmModal = new bootstrap.Modal(confirmModalEl);

        confirmModalEl.querySelector('[data-confirm-ok]').addEventListener('click', function () {
            var form = pendingConfirmForm;
            pendingConfirmForm = null;
            confirmModal.hide();
            if (form) {
                form.dataset.confirmBypass = '1';
                // requestSubmit() (not submit()) so the loading-spinner
                // listener below still sees this as a real submit event.
                form.requestSubmit();
            }
        });

        confirmModalEl.addEventListener('hidden.bs.modal', function () {
            pendingConfirmForm = null;
        });
    }

    document.querySelectorAll('[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.confirmBypass) {
                delete form.dataset.confirmBypass;
                return;
            }

            e.preventDefault();
            ensureConfirmModal();
            pendingConfirmForm = form;
            confirmModalEl.querySelector('[data-confirm-message]').textContent = form.getAttribute('data-confirm');
            confirmModal.show();
        });
    });

    // A centered Bootstrap modal standing in for window.alert(), so error/
    // info popups get the same icon + card treatment as the rest of the UI
    // instead of the browser's plain top-left dialog. Exposed globally so
    // Blade views can trigger it inline.
    var alertModalEl = null;
    var alertModal = null;

    function ensureAlertModal() {
        if (alertModalEl) {
            return;
        }

        alertModalEl = document.createElement('div');
        alertModalEl.className = 'modal fade';
        alertModalEl.tabIndex = -1;
        alertModalEl.innerHTML =
            '<div class="modal-dialog modal-dialog-centered">' +
                '<div class="modal-content text-center">' +
                    '<div class="modal-body py-4 px-4">' +
                        '<div class="mb-3" data-alert-icon><i class="bi bi-exclamation-circle-fill text-danger" style="font-size: 2.75rem;"></i></div>' +
                        '<div class="fw-semibold mb-1" data-alert-title></div>' +
                        '<div class="text-muted small" data-alert-message style="white-space: pre-line;"></div>' +
                    '</div>' +
                    '<div class="modal-footer justify-content-center border-top-0 pt-0">' +
                        '<button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">OK</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
        document.body.appendChild(alertModalEl);
        alertModal = new bootstrap.Modal(alertModalEl);
    }

    window.famvilleShowAlert = function (message, options) {
        options = options || {};

        ensureAlertModal();
        alertModalEl.querySelector('[data-alert-title]').textContent = options.title || 'Please fix the following';
        alertModalEl.querySelector('[data-alert-message]').textContent = message;
        alertModal.show();
    };

    // Instant feedback on every real form submission (approve/void/pay/etc.)
    // so a slow request never looks like the click did nothing — also
    // guards against a double-click firing the same action twice. Runs in
    // the bubble phase so it sees e.defaultPrevented set by data-confirm
    // above when the user cancels.
    document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) {
            return;
        }

        var form = e.target;
        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-loading-state')) {
            return;
        }

        var submitter = e.submitter || form.querySelector('button[type="submit"]:not([disabled])');
        if (!submitter) {
            return;
        }

        form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
            btn.disabled = true;
        });

        submitter.dataset.originalHtml = submitter.innerHTML;
        submitter.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + (submitter.dataset.loadingText || 'Please wait…');
    });

    document.querySelectorAll('.alert[data-autohide]').forEach(function (alertEl) {
        setTimeout(function () {
            var alert = bootstrap.Alert.getOrCreateInstance(alertEl);
            alert.close();
        }, 5000);
    });

    var refreshEl = document.querySelector('[data-auto-refresh]');
    if (refreshEl) {
        var seconds = parseInt(refreshEl.getAttribute('data-auto-refresh'), 10) || 30;
        setTimeout(function () {
            window.location.reload();
        }, seconds * 1000);
    }

    setInterval(tickCountdowns, 1000);
    tickCountdowns();
})();
