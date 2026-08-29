/* Samrat UI behaviour: theme, sidebar, reveals, counters, rings, toasts.
 *
 * Everything here is an enhancement layered on server-rendered HTML - with JS
 * off the pages still read and submit correctly. */
(function () {
    'use strict';

    var root      = document.documentElement;
    var THEME_KEY = 'samrat.theme';
    var MINI_KEY  = 'samrat.sidebar.mini';
    var calm      = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- storage that survives private mode -------------------- */

    function read(key) {
        try { return window.localStorage.getItem(key); } catch (e) { return null; }
    }

    function write(key, value) {
        try { window.localStorage.setItem(key, value); } catch (e) { /* quota or blocked */ }
    }

    /* ---------- theme ------------------------------------------------- */

    function applyTheme(name) {
        root.setAttribute('data-theme', name);
        document.querySelectorAll('[data-theme-icon]').forEach(function (el) {
            el.setAttribute('data-lucide', name === 'dark' ? 'sun' : 'moon');
        });
        drawIcons();
        window.dispatchEvent(new CustomEvent('samrat:theme', { detail: { theme: name } }));
    }

    function initTheme() {
        applyTheme(read(THEME_KEY) === 'light' ? 'light' : 'dark');

        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                write(THEME_KEY, next);
                applyTheme(next);
            });
        });
    }

    /* ---------- icons ------------------------------------------------- */

    function drawIcons() {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    /* ---------- sidebar ----------------------------------------------- */

    function initSidebar() {
        var app  = document.querySelector('.app');
        var side = document.getElementById('side');
        var back = document.getElementById('sideBackdrop');

        if (! app || ! side) {
            return;
        }

        if (read(MINI_KEY) === '1') {
            app.classList.add('mini');
        }

        var collapse = document.querySelector('[data-sidebar-collapse]');
        if (collapse) {
            collapse.addEventListener('click', function () {
                // Under the breakpoint the same control opens the drawer instead.
                if (window.innerWidth < 992) {
                    side.classList.add('open');
                    if (back) { back.classList.add('show'); }
                    return;
                }
                app.classList.toggle('mini');
                write(MINI_KEY, app.classList.contains('mini') ? '1' : '0');
            });
        }

        function close() {
            side.classList.remove('open');
            if (back) { back.classList.remove('show'); }
        }

        if (back) { back.addEventListener('click', close); }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { close(); }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992) { close(); }
        });
    }

    /* ---------- staggered reveal -------------------------------------- */

    function initReveal() {
        var items = document.querySelectorAll('.reveal');

        if (! items.length) {
            return;
        }

        if (calm || ! ('IntersectionObserver' in window)) {
            items.forEach(function (el) { el.classList.add('in'); });
            return;
        }

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (! entry.isIntersecting) {
                    return;
                }
                var el  = entry.target;
                var pos = Number(el.getAttribute('data-reveal-order') || 0);
                el.style.animationDelay = Math.min(pos, 8) * 60 + 'ms';
                el.classList.add('in');
                io.unobserve(el);
            });
        }, { rootMargin: '0px 0px -40px 0px', threshold: .05 });

        items.forEach(function (el, i) {
            if (! el.hasAttribute('data-reveal-order')) {
                el.setAttribute('data-reveal-order', i % 9);
            }
            io.observe(el);
        });
    }

    /* ---------- number count-up --------------------------------------- */

    function countUp(el) {
        var target  = parseFloat(el.getAttribute('data-count'));
        var decimals = Number(el.getAttribute('data-count-decimals') || 2);
        var prefix  = el.getAttribute('data-count-prefix') || '';
        var suffix  = el.getAttribute('data-count-suffix') || '';

        if (isNaN(target)) {
            return;
        }

        function paint(value) {
            el.textContent = prefix + value.toLocaleString(undefined, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }) + suffix;
        }

        if (calm) {
            paint(target);
            return;
        }

        var started = null;
        var span    = 900;

        function step(now) {
            if (started === null) { started = now; }
            var t = Math.min(1, (now - started) / span);
            paint(target * (1 - Math.pow(1 - t, 3)));   // ease-out cubic
            if (t < 1) { window.requestAnimationFrame(step); }
        }

        window.requestAnimationFrame(step);
    }

    function initCounters() {
        var nums = document.querySelectorAll('[data-count]');

        if (! nums.length) {
            return;
        }

        if (! ('IntersectionObserver' in window)) {
            nums.forEach(countUp);
            return;
        }

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    countUp(entry.target);
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: .3 });

        nums.forEach(function (el) { io.observe(el); });
    }

    /* ---------- progress rings ---------------------------------------- */

    function initRings() {
        document.querySelectorAll('.ring-fill').forEach(function (circle) {
            var r    = Number(circle.getAttribute('r'));
            var pct  = Math.max(0, Math.min(100, Number(circle.getAttribute('data-pct') || 0)));
            var span = 2 * Math.PI * r;

            circle.style.strokeDasharray  = span;
            circle.style.strokeDashoffset = span;

            window.requestAnimationFrame(function () {
                window.setTimeout(function () {
                    circle.style.strokeDashoffset = span * (1 - pct / 100);
                }, calm ? 0 : 160);
            });
        });
    }

    /* ---------- progress bars ----------------------------------------- */

    function initBars() {
        document.querySelectorAll('[data-bar]').forEach(function (bar) {
            var pct = Math.max(0, Math.min(100, Number(bar.getAttribute('data-bar'))));
            bar.style.width = '0%';
            window.requestAnimationFrame(function () {
                window.setTimeout(function () { bar.style.width = pct + '%'; }, calm ? 0 : 120);
            });
        });
    }

    /* ---------- notice ticker ----------------------------------------- */

    function initTicker() {
        var view  = document.querySelector('.ticker-view');
        var track = view && view.querySelector('.ticker-track');

        if (! track) {
            return;
        }

        function measure() {
            // One loop shifts the track by half its width, so the half has to
            // be at least as wide as the rail or a blank gap scrolls past.
            track.classList.toggle('still', track.scrollWidth < view.clientWidth * 2);
        }

        measure();
        window.addEventListener('resize', measure);
    }

    /* ---------- copy to clipboard ------------------------------------- */

    function initCopy() {
        document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var field = document.querySelector(btn.getAttribute('data-copy-target'));
                if (! field) {
                    return;
                }

                var done = function () {
                    toast('Copied to clipboard', 'ok');
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(field.value).then(done, function () {});
                    return;
                }

                field.select();
                field.setSelectionRange(0, 99999);
                try { document.execCommand('copy'); done(); } catch (e) { /* older browser */ }
            });
        });
    }

    /* ---------- toasts ------------------------------------------------ */

    function toast(message, kind) {
        var stack = document.getElementById('toastStack');

        if (! stack) {
            return;
        }

        var item = document.createElement('div');
        item.className = 'toast-item ' + (kind || 'info');
        item.setAttribute('role', 'status');

        var text = document.createElement('div');
        text.textContent = message;
        item.appendChild(text);

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'toast-x';
        close.setAttribute('aria-label', 'Dismiss');
        close.textContent = '×';
        item.appendChild(close);

        function drop() {
            item.classList.add('out');
            window.setTimeout(function () { item.remove(); }, 300);
        }

        close.addEventListener('click', drop);
        stack.appendChild(item);
        window.setTimeout(drop, 4200);
    }

    /* Flash messages rendered by PHP get lifted into the toast stack. */
    function initFlash() {
        document.querySelectorAll('[data-flash]').forEach(function (el) {
            toast(el.textContent.trim(), el.getAttribute('data-flash'));
            el.remove();
        });
    }

    /* ---------- boot -------------------------------------------------- */

    function boot() {
        initTheme();
        drawIcons();
        initSidebar();
        initReveal();
        initCounters();
        initRings();
        initBars();
        initTicker();
        initCopy();
        initFlash();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.SamratUI = { toast: toast, icons: drawIcons, reducedMotion: calm };
}());
