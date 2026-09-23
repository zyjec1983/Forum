/* ============================================================
   security.js · client-side security (forum pages only)
   Blocks: Copy, Cut, Paste, Select, context menu, Print Screen
   and developer tools.
   A real window/tab switch (Alt+Tab, another app) shows the black
   shield and closes the session; in-page interaction (typing,
   clicking the answer box) never does. On Print Screen the
   temporary memory (clipboard) is wiped so the captured frame
   cannot be pasted anywhere.
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

    // ---------- Device / platform (drives capture defense per OS) ----------
    // Windows: Print Screen / Snipping hide the tab -> the black shield fires.
    // macOS: Cmd+Shift+3/4/5 shortcuts are intercepted on keydown.
    // Android/iOS: the screenshot gesture hides the tab (Chrome/Safari) ->
    //              the shield fires; the platform is logged for the teacher.
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

    // Watermark with the user's data (deters screenshots and shows who violates)
    if (user) {
        var wm = document.createElement('div');
        wm.className = 'screen-watermark';
        wm.setAttribute('aria-hidden', 'true');
        wm.textContent = (user.first_name + ' ' + (user.last_name || '') + ' · ' + user.email).trim();
        document.body.appendChild(wm);
    }

    // Clipboard wipe: replaces the temporary memory content so a screenshot
    // taken (Print Screen / snip tool) cannot be pasted anywhere after the fact.
    // Windows keeps SEVERAL formats per clipboard entry: writing text leaves the
    // bitmap (DIB/PNG) of a capture intact, so we ALSO overwrite the image
    // format with a blank 1x1 PNG (mspaint/photos paste then shows nothing).
    // Both the async Clipboard API and the synchronous execCommand fallback
    // are used: if the first is rejected (no user gesture), the second still
    // overwrites the current clipboard content.
    function overwriteClipboard(text) {
        text = text || ' ';
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).catch(function () { /* execCommand below covers it */ });
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
        try {
            if (typeof ClipboardItem !== 'undefined' && navigator.clipboard && navigator.clipboard.write) {
                var c = document.createElement('canvas');
                c.width = 1;
                c.height = 1;
                var cctx = c.getContext('2d');
                cctx.fillStyle = 'rgba(0,0,0,0)';
                cctx.fillRect(0, 0, 1, 1);
                c.toBlob(function (blob) {
                    if (!blob) { return; }
                    try {
                        navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]);
                    } catch (e) { /* ignore */ }
                }, 'image/png');
            }
        } catch (e) { /* ignore */ }
    }

    // Pre-warm: on the first real interaction we request clipboard-write so later
    // wipes work even without a user gesture (the async API rejects writes
    // when the document has no activation, e.g. a pure OS capture).
    var clipboardWarmed = false;
    function warmClipboard() {
        if (clipboardWarmed) { return; }
        clipboardWarmed = true;
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(' ');
            }
        } catch (e) { /* ignore */ }
    }
    document.addEventListener('mousedown', warmClipboard, true);
    document.addEventListener('keydown', warmClipboard, true);

    // Chromium fires 'clipboardchange' when a screen-capture tool writes the
    // image to the clipboard; blank it repeatedly so pasting shows nothing
    // (a single write can lose the race with the OS capture/history).
    try {
        navigator.clipboard.addEventListener('clipboardchange', function () {
            for (var i = 0; i < 4; i++) {
                (function (t) {
                    setTimeout(function () { overwriteClipboard(' '); }, t);
                })(i * 200);
            }
        });
    } catch (e) { /* not supported */ }

    // A capture written while the tab was in the background (e.g. the Snipping
    // overlay) is blanked the moment the tab regains focus, so pasting the
    // frame right after shows nothing.
    window.addEventListener('focus', function () {
        overwriteClipboard(' ');
    }, true);

    // Screen-capture shield for window/tab switches. Only a REAL switch to
    // another window or tab triggers it (Alt+Tab, opening another app). Any
    // interaction inside the page (clicking the answer box, typing, scrolling)
    // keeps the clock fresh, so legitimate use never signs the student out.
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

    function forceSignOut(label, detail) {
        if (logoutPending) { return; }
        logoutPending = true;

        // Log the attempt reliably before navigating away.
        try {
            var fd = new FormData();
            fd.append('csrf', window.App.csrf());
            fd.append('event', 'printscreen');
            fd.append('detail', label + ' \u2014 ' + detail + ' · Device: ' + platform);
            if (navigator.sendBeacon) {
                navigator.sendBeacon(window.App.baseURL() + '/forum/report', fd);
            } else {
                window.App.post(window.App.baseURL() + '/forum/report', {
                    event: 'printscreen',
                    detail: label + ' \u2014 ' + detail
                });
            }
        } catch (e) { /* ignore */ }

        // Blank the clipboard so a captured frame does not paste anywhere.
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
        }, 3000);
    }

    // Fresh timestamp on every in-page interaction (click, typing, touch,
    // walking through fields). A blur/visibility that happens while the user
    // is clearly using the page is not a capture attempt.
    var lastInteraction = 0;
    document.addEventListener('mousedown', function () { lastInteraction = Date.now(); }, true);
    document.addEventListener('keydown', function () { lastInteraction = Date.now(); }, true);
    document.addEventListener('touchstart', function () { lastInteraction = Date.now(); }, true);
    document.addEventListener('focus', function () { lastInteraction = Date.now(); }, true);

    function looksLikeCapture() {
        return (Date.now() - lastInteraction) > 150;
    }

    // A REAL capture always means the tab left the screen (window switched,
    // another app covered it, the tab went to the background). The browser
    // ALSO fires a window blur on in-page focus juggling (the SweetAlert
    // "Action not allowed" popup, focus rings, etc.) WITHOUT the tab leaving
    // the screen, so those are only counted when the tab is actually gone.
    var everFocused = false;
    window.addEventListener('focus', function () { everFocused = true; }, true);
    window.addEventListener('blur', function () {
        if (everFocused && looksLikeCapture() && document.visibilityState === 'hidden') {
            forceSignOut('Screen capture / window switch', 'the page left the screen');
        }
    }, true);

    // No popup is open while the tab really goes to the background, so a
    // micro-hide right after an in-page alert cannot be a capture.
    function popupOpen() {
        return !!document.querySelector('.swal2-container');
    }

    var seenVisible = document.visibilityState === 'visible';
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            seenVisible = true;
            return;
        }
        if (seenVisible && looksLikeCapture() && !popupOpen()) {
            forceSignOut('Screen capture / tab switch', 'the tab went to the background');
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

    // Keyboard: shortcuts and Print Screen
    document.addEventListener('keydown', function (e) {
        var k = (e.key || '').toLowerCase();
        var mod = e.ctrlKey || e.metaKey;

        if (e.key === 'F12') {
            e.preventDefault();
            warn('devtools', 'F12 key (developer tools) blocked');
            return;
        }
        if (e.altKey && e.key === 'PrintScreen') {
            e.preventDefault();
            forceSignOut('Screen capture / active window', 'Alt+PrintScreen pressed');
            return;
        }
        // macOS screen captures: Cmd+Shift+3 (full), 4 (area), 5 (toolbar)
        if (e.metaKey && e.shiftKey && (k === '3' || k === '4' || k === '5')) {
            e.preventDefault();
            forceSignOut('Screen capture / macOS', 'Cmd+Shift+' + k.toUpperCase() + ' screenshot shortcut');
            return;
        }
        if (e.key === 'PrintScreen' || e.code === 'PrintScreen') {
            e.preventDefault();
            // The frame was captured by the OS; the temporary memory is wiped
            // so it cannot be pasted anywhere AND the black shield + forced
            // logout close the session so the attempt has no value.
            forceSignOut('Screen capture / Print Screen', 'the Print Screen key was pressed');
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

})(window, document);