/* ============================================================
   forum.js · forum interactions (AJAX + time window)
   ============================================================ */
(function (window, document) {
    'use strict';

    var app = document.getElementById('forum-app');
    if (!app) { return; }

    var forumId   = parseInt(app.dataset.forumId || '0', 10);
    var openTs    = parseInt(app.dataset.open || '0', 10);
    var closeTs   = parseInt(app.dataset.close || '0', 10);
    var serverTs  = parseInt(app.dataset.server || '0', 10);
    var minLen    = parseInt(app.dataset.minLen || '10', 10);
    var offsetMs  = (serverTs * 1000) - Date.now(); // syncs the student's clock with the server

    function nowSec() { return Math.floor((Date.now() + offsetMs) / 1000); }

    function parts(sec) {
        var p = { d: 0, h: 0, m: 0, s: 0 };
        p.d = Math.floor(sec / 86400); sec -= p.d * 86400;
        p.h = Math.floor(sec / 3600);  sec -= p.h * 3600;
        p.m = Math.floor(sec / 60);    p.s = sec - p.m * 60;
        return p;
    }
    function fmt(sec) {
        var p = parts(sec);
        return p.d + ' day(s), ' + p.h + ' hour(s), ' + p.m + ' minute(s) and ' + p.s + ' second(s)';
    }
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function fmtClock(sec) {
        var d = new Date(sec * 1000);
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) +
               ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }
    function clock(sec) {
        var p = parts(sec);
        var d = p.d > 0 ? p.d + 'd ' : '';
        return d + pad(p.h % 24) + ':' + pad(p.m) + ':' + pad(p.s);
    }

    function windowState() {
        var now = nowSec();
        if (now < openTs) {
            return { status: 'not_started', message: 'The forum has not opened yet. It starts in ' + fmt(openTs - now) + '.' };
        }
        if (now > closeTs) {
            return { status: 'expired', message: 'The forum expired ' + fmt(now - closeTs) + ' ago.' };
        }
        return { status: 'open', remaining: closeTs - now, message: 'It will close on ' + fmtClock(closeTs) + ' (device time).' };
    }

    // ---------------- Banner / countdown ----------------
    var badgeEl = document.getElementById('window-badge');
    var msgEl   = document.getElementById('window-msg');

    function liveUpdate() {
        var st = windowState();
        if (!badgeEl || !msgEl) { return; }
        msgEl.innerHTML = st.message;
        if (st.status === 'open') {
            badgeEl.className = 'badge text-bg-success';
            badgeEl.textContent = 'Open';
            msgEl.innerHTML = 'Open for participation. Closes in <strong class="text-success">' + clock(st.remaining) + '</strong> · ' + st.message;
        } else if (st.status === 'not_started') {
            badgeEl.className = 'badge text-bg-warning';
            badgeEl.textContent = 'Not started';
        } else {
            badgeEl.className = 'badge text-bg-danger';
            badgeEl.textContent = 'Expired';
        }
    }
    liveUpdate();
    setInterval(liveUpdate, 1000);

    // ---------------- Participation window control ----------------
    function assertWindow() {
        var st = windowState();
        if (st.status === 'open') { return true; }
        // Logs the block and shows the message with days/hours/minutes/seconds
        try {
            window.App.post(window.App.baseURL() + '/forum/report', {
                event: st.status === 'expired' ? 'time_block' : 'time_not_started',
                detail: st.message
            });
        } catch (e) { /* no log */ }
        window.App.alert(
            'error',
            'Outside the participation window',
            st.message + ' You can only interact while the forum is within the teacher\'s time range.'
        );
        return false;
    }

    // ============================
    // Response to the teacher (once)
    // ============================
    var btnTeacher = document.getElementById('btn-response-teacher');
    var teacherBox = document.getElementById('teacher-form-box');
    var txtTeacher = document.getElementById('txt-teacher');
    var btnTeacherSend = document.getElementById('btn-teacher-send');
    var teacherLocked = false;

    if (btnTeacher && teacherBox && txtTeacher && btnTeacherSend) {
        btnTeacher.addEventListener('click', function () {
            if (teacherLocked) { return; }
            if (!assertWindow()) { return; }
            teacherBox.classList.toggle('d-none');
        });
        document.getElementById('btn-teacher-cancel').addEventListener('click', function () {
            teacherBox.classList.add('d-none');
        });
        btnTeacherSend.addEventListener('click', async function () {
            if (teacherLocked) {
                window.App.alert('error', 'You already sent your response', 'Only ONE response to the teacher is allowed.');
                return;
            }
            if (!assertWindow()) { return; }
            var text = txtTeacher.value.trim();
            if (text.length < minLen) {
                window.App.alert('error', 'Response too short', 'Write at least ' + minLen + ' characters.');
                return;
            }
            btnTeacherSend.disabled = true;
            try {
                var res = await window.App.post(window.App.baseURL() + '/forum/respond-teacher', { content: text });
                if (res.ok) {
                    var thread = document.getElementById('forum-thread');
                    var temp = document.createElement('div');
                    temp.innerHTML = res.html;
                    if (temp.firstElementChild) { thread.prepend(temp.firstElementChild); }
                    teacherBox.classList.add('d-none');
                    txtTeacher.value = '';
                    teacherLocked = true;
                    btnTeacher.classList.add('d-none');
                    window.App.alert('success', 'Response published', 'Your response to the teacher was sent correctly. Only 1 submission is allowed.');
                } else if (res.hack) {
                    window.App.alert('error', res.name + ' cannot perform that action', res.message);
                } else {
                    window.App.alert('error', 'Could not publish', res.message);
                }
            } catch (err) {
                window.App.alert('error', 'Connection error', String(err && err.message ? err.message : err));
            }
            btnTeacherSend.disabled = false;
        });
    }

    // ============================
    // Reply to a classmate
    // ============================
    function closestCard(el) { return el.closest('.student-card'); }

    window.Forum = {
        togglePartner: function (btn) {
            if (!assertWindow()) { return; }
            var form = closestCard(btn).querySelector('.partner-form');
            if (form) { form.classList.toggle('d-none'); }
        },
        cancelPartner: function (btn) {
            var card = closestCard(btn);
            var form = card.querySelector('.partner-form');
            if (form) {
                form.classList.add('d-none');
                var input = form.querySelector('.partner-input');
                if (input) { input.value = ''; }
            }
        },
        submitPartner: async function (btn, parentId) {
            if (!assertWindow()) { return; }
            var card = closestCard(btn);
            var input = card.querySelector('.partner-input');
            var text = input.value.trim();
            if (text.length < minLen) {
                window.App.alert('error', 'Reply too short', 'Write at least ' + minLen + ' characters.');
                return;
            }
            var target = btn.getAttribute('data-target') || ('the post');
            btn.disabled = true;
            try {
                var res = await window.App.post(window.App.baseURL() + '/forum/respond-partner', {
                    parent_id: parentId,
                    content: text
                });
                if (res.ok) {
                    var container = card.querySelector('.sub-replies');
                    var temp = document.createElement('div');
                    temp.innerHTML = res.html;
                    if (temp.firstElementChild) { container.appendChild(temp.firstElementChild); }
                    input.value = '';
                    formHide(card);
                    window.App.alert('success', 'Reply published', 'Your reply to ' + target + ' was published correctly.');
                } else if (res.hack) {
                    window.App.alert('error', res.name + ' cannot perform that action', res.message);
                } else {
                    window.App.alert('error', 'Could not publish', res.message);
                }
            } catch (err) {
                window.App.alert('error', 'Connection error', String(err && err.message ? err.message : err));
            }
            btn.disabled = false;

            function formHide(card) {
                var form = card.querySelector('.partner-form');
                if (form) { form.classList.add('d-none'); }
            }
        }
    };

    // ============================
    // Final conclusion (once)
    // ============================
    var btnConcl = document.getElementById('btn-conclusion');
    var txtConcl = document.getElementById('txt-conclusion');
    if (btnConcl && txtConcl) {
        btnConcl.addEventListener('click', async function () {
            if (!assertWindow()) { return; }
            var text = txtConcl.value.trim();
            if (text.length < minLen) {
                window.App.alert('error', 'Conclusion too short', 'Write at least ' + minLen + ' characters.');
                return;
            }
            btnConcl.disabled = true;
            try {
                var res = await window.App.post(window.App.baseURL() + '/forum/conclusion', { content: text });
                if (res.ok) {
                    await window.App.alert('success', 'Conclusion saved', 'Your final conclusion was saved correctly. Only 1 submission is allowed.');
                    window.location.reload();
                } else if (res.hack) {
                    window.App.alert('error', res.name + ' cannot perform that action', res.message);
                    btnConcl.disabled = false;
                } else {
                    window.App.alert('error', 'Could not save', res.message);
                    btnConcl.disabled = false;
                }
            } catch (err) {
                window.App.alert('error', 'Connection error', String(err && err.message ? err.message : err));
                btnConcl.disabled = false;
            }
        });
    }

})(window, document);