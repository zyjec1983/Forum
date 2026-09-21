/* ============================================================
   security.js · client-side security (forum pages only)
   Blocks: Copy, Cut, Paste, Select, context menu,
   screenshots and developer tools.
   Every attempt is reported to the server with user, date/time and IP.
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

    // Block text selection on the whole forum page
    document.body.classList.add('forum-lock');

    // Watermark with the user's data (deters screenshots and shows who violates)
    if (user) {
        var wm = document.createElement('div');
        wm.className = 'screen-watermark';
        wm.setAttribute('aria-hidden', 'true');
        wm.textContent = (user.first_name + ' ' + (user.last_name || '') + ' · ' + user.email).trim();
        document.body.appendChild(wm);
    }

    // Screen-capture shield: as soon as a capture attempt is detected
    // (PrtSc key, snip/screen-capture tool, window or tab switch-out), a
    // full-screen cover is shown and stays; the student is then forced to
    // close the session. It only appears during those attempts: a fresh
    // sign-in never shows it again.
    var shield = document.createElement('div');
    shield.className = 'capture-shield';
    shield.innerHTML =
        '<div>' +
        '<span class="shield-title">Screen capture attempt detected</span>' +
        '<span class="shield-msg"></span>' +
        '<button type="button" class="btn btn-light btn-sm shield-logout">Sign out / Sign in again</button>' +
        '</div>';
    document.body.appendChild(shield);

    var logoutPending = false;

    // Best-effort clipboard wipe: after a capture attempt, the last entry on
    // the clipboard is replaced so that pasting it elsewhere shows nothing.
    // Browsers restrict this while the window is blurred, so the permanent
    // black shield is the real defense; this is an extra layer.
    function overwriteClipboard(text) {
        text = text || ' ';
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).catch(function () { /* ignore */ });
            }
        } catch (e) { /* ignore */ }
        try {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            document.execCommand('copy');
            ta.remove();
        } catch (e) { /* ignore */ }
    }

    // Chromium fires 'clipboardchange' when a screen-capture tool writes the
    // image; blank it right away while the sign-out is pending.
    try {
        navigator.clipboard.addEventListener('clipboardchange', function () {
            if (logoutPending) { overwriteClipboard(' '); }
        });
    } catch (e) { /* not supported */ }

    function forceSignOut(label, detail) {
        if (logoutPending) { return; }
        logoutPending = true;

        // Log the attempt reliably before navigating away.
        try {
            var fd = new FormData();
            fd.append('csrf', window.App.csrf());
            fd.append('event', 'printscreen');
            fd.append('detail', label + ' — ' + detail);
            if (navigator.sendBeacon) {
                navigator.sendBeacon(window.App.baseURL() + '/forum/report', fd);
            } else {
                window.App.post(window.App.baseURL() + '/forum/report', {
                    event: 'printscreen',
                    detail: label + ' — ' + detail
                });
            }
        } catch (e) { /* ignore */ }

        // Blank the clipboard so the captured frame does not paste in other apps.
        overwriteClipboard(' ');
        var wipeTries = 0;
        var wipeTimer = setInterval(function () {
            overwriteClipboard(' ');
            if (++wipeTries >= 4) { clearInterval(wipeTimer); }
        }, 300);

        shield.querySelector('.shield-msg').textContent = label + '. Your session will be closed for security. Sign in again to continue.';
        shield.classList.add('visible');

        var logoutUrl = window.App.baseURL() + '/auth/logout';
        shield.querySelector('.shield-logout').addEventListener('click', function () {
            window.location.href = logoutUrl;
        });

        setTimeout(function () {
            window.location.href = logoutUrl;
        }, 1500);
    }

    function screenCaptureAttempt(label, detail) {
        forceSignOut(label, detail);
    }

    // Capture attempts: screen-capture/snip tools and other apps blur the
    // window/tab the moment the overlay is taken. Only trigger once the page
    // has been focused/visible, so opening the forum in a background tab does
    // not count as an attempt.
    var everFocused = false;
    window.addEventListener('focus', function () { everFocused = true; }, true);
    window.addEventListener('blur', function () {
        if (everFocused) {
            screenCaptureAttempt('Screen capture / window switch', 'the page lost focus');
        }
    }, true);
    var seenVisible = document.visibilityState === 'visible';
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            seenVisible = true;
            return;
        }
        if (seenVisible) {
            screenCaptureAttempt('Screen capture / tab switch', 'the tab went to the background');
        }
    });

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
                detail: detail
            }).catch(function () { /* silent */ });
        } catch (e) { /* no report if the helper is missing */ }
    }

    var LABELS = {
        copy: 'Copy',
        cut: 'Cut',
        paste: 'Paste',
        select: 'Select text',
        contextmenu: 'context menu',
        printscreen: 'screenshot',
        devtools: 'developer tools',
        drag: 'drag text'
    };

    function warn(eventName, detail) {
        report(eventName, detail);
        var label = LABELS[eventName] || eventName;
        return window.App.alert(
            'warning',
            'Action not allowed in the forum',
            'You cannot use <strong>Copy, Cut, Paste or Select</strong> functions or take <strong>screenshots</strong> in this forum. ' +
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

    // Text selection
    document.addEventListener('selectstart', function (e) {
        e.preventDefault();
        warn('select', 'Attempt to select text in the forum');
    }, true);

    // Context menu (right click) -> prevents "view source", menu copying
    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
        warn('contextmenu', 'Context menu blocked in the forum');
    }, true);

    // Drag text
    document.addEventListener('dragstart', function (e) {
        e.preventDefault();
        warn('drag', 'Attempt to drag text in the forum');
    }, true);

    // Keyboard selection movement inside fields
    document.addEventListener('keydown', function (e) {
        var k = (e.key || '').toLowerCase();
        var mod = e.ctrlKey || e.metaKey;

        if (e.key === 'F12') {
            e.preventDefault();
            warn('devtools', 'F12 key (developer tools) blocked');
            return;
        }
        if (e.key === 'PrintScreen' || e.code === 'PrintScreen') {
            e.preventDefault();
            screenCaptureAttempt('Print Screen (PrtSc) key', 'screen-capture button pressed');
            return;
        }
        if (mod && k === 'c') { e.preventDefault(); warn('copy', 'Ctrl+C blocked'); return; }
        if (mod && k === 'x') { e.preventDefault(); warn('cut', 'Ctrl+X blocked'); return; }
        if (mod && k === 'v') { e.preventDefault(); warn('paste', 'Ctrl+V blocked'); return; }
        if (mod && k === 'a') { e.preventDefault(); warn('select', 'Ctrl+A (select all) blocked'); return; }
        if (mod && (k === 'i' || k === 'j' || k === 'c' || k === 'u' || k === 'p' || k === 's')) {
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

    // ---------------- Extra shortcuts ----------------
    // Prevent text from being dragged out from page inputs
    document.addEventListener('touchend', function (e) {
        // On mobile, prevent selection by long press on non-editable elements
        if (document.activeElement && document.activeElement.tagName === 'INPUT') {
            return;
        }
    }, false);

})(window, document);