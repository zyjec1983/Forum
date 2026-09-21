/* ============================================================
   app.js · global utilities (CSRF + SweetAlert + fetch)
   ============================================================ */
(function (window, document) {
    'use strict';

    function baseURL() {
        const m = document.querySelector('meta[name="base-url"]');
        return m ? m.content : '';
    }

    function csrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.content : '';
    }

    /**
     * POST JSON to the server with CSRF protection.
     */
    async function post(url, data) {
        const fd = new FormData();
        fd.append('csrf', csrf());
        Object.keys(data || {}).forEach(function (k) {
            fd.append(k, data[k]);
        });
        const res = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': csrf() },
            body: fd
        });
        if (!res.ok) {
            let t = '';
            try { t = await res.text(); } catch (e) { t = 'HTTP ' + res.status; }
            throw new Error(t || 'HTTP ' + res.status);
        }
        return res.json();
    }

    function alert(icon, title, html) {
        return window.Swal.fire({
            icon: icon,
            title: title,
            html: html,
            confirmButtonText: 'OK',
            allowOutsideClick: false
        });
    }

    window.App = {
        baseURL: baseURL,
        csrf: csrf,
        post: post,
        alert: alert,
        confirm: function (message) {
            return window.Swal.fire({
                icon: 'warning',
                title: 'Are you sure?',
                text: message,
                showCancelButton: true,
                confirmButtonText: 'Yes, continue',
                cancelButtonText: 'Cancel'
            });
        }
    };
})(window, document);