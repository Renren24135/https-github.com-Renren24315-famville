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

    document.querySelectorAll('[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
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
