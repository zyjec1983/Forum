/* ============================================================
   security.js · client-side security (forum pages only)
   Blocks: Copy, Cut, Paste, Select, context menu, drag and the
   developer tools. EVERY attempt is reported to the server with
   user, date/time, IP and platform.
   ============================================================ */
(function (window, document) {
    'use strict';

    var user = null;
    try {
        var holder = document.getElementById('app');
        if (holder && holder.dataset.user) {
            user = JSON.parse(holder.dataset.user);
        }
    } catch (e) { /* no user -> reports still go out with null id */ }

    // ---------- Device / platform (shown in the reports) ----------
    var platform = (function () {
        var ua = navigator.userAgent || '';
        if (/iphone|ipad|ipod/i.test(ua)) { return 'iOS'; }
        if (/android/i.test(ua)) { return 'Android'; }
        var p = (navigator.userAgentData && navigator.userAgentData.platform) ? navigator.userAgentData.platform : (navigator.platform || '');
        if (/win/i.test(p + ' ' + ua)) { return 'Windows'; }
        if (/mac/i.test(p + ' ' + ua)) { return 'macOS'; }
        if (/linux/i.test(p + ' ' + ua)) { return 'Linux'; }
        return 'Unknown';
    })();

    // Block text selection on the whole forum page
    document.body.classList.add('forum-lock');

    // ---------------- Report throttler ----------------
    var lastSent = {};
    var DEVTOOLS_COOLDOWN = 90000;   // 90 s
    var GENERAL_COOLDOWN = 45000;    // 45 s

    function report(eventName, detail, cooldown) {
        cooldown = cooldown || GENERAL_COOLDOWN;
        var now = Date.now();
        if (lastSent[eventName] && (now - lastSent[eventName]) < cooldown) {
            return;
        }
        lastSent[eventName] = now;
        try {
            window.App.post(window.App.baseURL() + '/forum/report', {
                event: eventName,
                detail: detail + ' · Device: ' + platform
            }).catch(function () { /* silent */ });
        } catch (e) { /* no report if the helper is missing */ }
    }

    var LABELS = {
        copy: 'Copy',
        cut: 'Cut',
        paste: 'Paste',
        select: 'Select text',
        contextmenu: 'context menu',
        devtools: 'developer tools',
        drag: 'drag text'
    };

    function warn(eventName, detail) {
        report(eventName, detail);
        var label = LABELS[eventName] || eventName;
        return window.App.alert(
            'warning',
            'Action not allowed in the forum',
            'You cannot use <strong>Copy, Cut, Paste or Select</strong> functions in this forum. ' +
            'The attempt with <strong>"' + label + '"</strong> was logged with your user, date/time and IP.'
        );
    }

    // ---------------- Clipboard / selection events ----------------
    ['copy', 'cut'].forEach(function (ev) {
        document.addEventListener(ev, function (e) {
            e.preventDefault();
            warn(ev, 'Attempt to ' + (ev === 'copy' ? 'copy' : 'cut') + ' text in the forum');
        }, true);
    });

    // Paste must always be disabled
    document.addEventListener('paste', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        warn('paste', 'Attempt to paste text in the forum');
    }, true);

    // Text selection (inside fields the caret still works: selection is blocked)
    document.addEventListener('selectstart', function (e) {
        e.preventDefault();
        warn('select', 'Attempt to select text in the forum');
    }, true);

    // Context menu (right click)
    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
        warn('contextmenu', 'Context menu blocked in the forum');
    }, true);

    // Drag text
    document.addEventListener('dragstart', function (e) {
        e.preventDefault();
        warn('drag', 'Attempt to drag text in the forum');
    }, true);

    // Keyboard: shortcuts and devtools keys
    document.addEventListener('keydown', function (e) {
        var k = (e.key || '').toLowerCase();
        var mod = e.ctrlKey || e.metaKey;

        if (e.key === 'F12') {
            e.preventDefault();
            warn('devtools', 'F12 key (developer tools) blocked');
            return;
        }
        if (mod && k === 'c') { e.preventDefault(); warn('copy', 'Ctrl+C blocked'); return; }
        if (mod && k === 'x') { e.preventDefault(); warn('cut', 'Ctrl+X blocked'); return; }
        if (mod && k === 'v') { e.preventDefault(); warn('paste', 'Ctrl+V blocked'); return; }
        if (mod && k === 'a') { e.preventDefault(); warn('select', 'Ctrl+A (select all) blocked'); return; }
        if (mod && (k === 'i' || k === 'j' || k === 'u' || k === 'p' || k === 's')) {
            e.preventDefault();
            warn('devtools', 'Developer tools keyboard shortcut blocked');
        }
    }, true);

    // ---------------- DevTools heuristic detection ----------------
    (function detectDevtools() {
        function open() {
            var w = 160, h = 90;
            if (window.outerWidth - window.innerWidth > w) return true;
            if (window.outerHeight - window.innerHeight > h) return true;
            return false;
        }
        function probe() {
            if (open()) {
                report('devtools', 'Developer tools panel detected open', DEVTOOLS_COOLDOWN);
            }
        }
        setInterval(probe, 5000);
        window.addEventListener('resize', probe);
    })();

})(window, document);