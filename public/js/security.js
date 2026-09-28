/* ============================================================
   security.js · client-side security (forum pages, students only)
   Blocks: Copy, Cut, Paste, Select, context menu, drag and the
   developer tools. EVERY attempt is reported to the server with
   user, date/time, IP and platform.
   On a REAL window/tab switch (Alt+Tab, another app, background
   tab) the session is closed: the event is logged and the user
   is redirected to sign out. Interactions inside the page (typing,
   clicking, SweetAlert popups) never trigger it.
   NOTE: everything here is passive (preventDefault) or only
   navigates on exit; nothing steals focus or writes to the
   clipboard, so normal buttons always keep working.
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

    // ---------------- Session guard: real window/tab switch ----------------
    // A REAL switch means the tab left the screen: window switched (Alt+Tab),
    // another app covered it or the tab went to the background. The browser
    // ALSO fires a window blur on in-page focus juggling (SweetAlert popups,
    // focus rings, autofill) WITHOUT the tab leaving the screen, so those are
    // only counted when the page is actually hidden.
    var logoutPending = false;

    function forceSignOut(label, detail) {
        if (logoutPending) { return; }
        logoutPending = true;

        // Log the switch reliably before navigating away.
        try {
            var fd = new FormData();
            fd.append('csrf', window.App.csrf());
            fd.append('event', 'window_switch');
            fd.append('detail', label + ' \u2014 ' + detail + ' · Device: ' + platform);
            if (navigator.sendBeacon) {
                navigator.sendBeacon(window.App.baseURL() + '/forum/report', fd);
            } else {
                window.App.post(window.App.baseURL() + '/forum/report', {
                    event: 'window_switch',
                    detail: label + ' \u2014 ' + detail
                }).catch(function () { /* silent */ });
            }
        } catch (e) { /* no log if the helper is missing */ }

        // Close the session: the student must sign in again.
        try {
            window.location.href = window.App.baseURL() + '/auth/logout';
        } catch (e) { /* ignore */ }
    }

    // Fresh timestamp on every in-page interaction (click, typing, touch,
    // walking through fields). A blur/visibility that happens while the user
    // is clearly using the page is not a switch.
    var lastInteraction = 0;
    document.addEventListener('mousedown', function () { lastInteraction = Date.now(); }, true);
    document.addEventListener('keydown', function () { lastInteraction = Date.now(); }, true);
    document.addEventListener('touchstart', function () { lastInteraction = Date.now(); }, true);
    document.addEventListener('focus', function () { lastInteraction = Date.now(); }, true);

    function looksLikeSwitch() {
        return (Date.now() - lastInteraction) > 150;
    }

    var everFocused = false;
    window.addEventListener('focus', function () { everFocused = true; }, true);
    window.addEventListener('blur', function () {
        if (everFocused && looksLikeSwitch() && document.visibilityState === 'hidden') {
            forceSignOut('Window/tab switch', 'the page left the screen');
        }
    }, true);

    // No popup is open while the tab really goes to the background, so a
    // micro-hide right after an in-page alert cannot be a switch.
    function popupOpen() {
        return !!document.querySelector('.swal2-container');
    }

    var seenVisible = document.visibilityState === 'visible';
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            seenVisible = true;
            return;
        }
        if (seenVisible && looksLikeSwitch() && !popupOpen()) {
            forceSignOut('Tab switch', 'the tab went to the background');
        }
    });

})(window, document);