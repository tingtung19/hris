(function () {
    'use strict';

    window.HRIS = window.HRIS || {};

    HRIS.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    HRIS.fetchJson = async function (url, options = {}) {
        options.headers = Object.assign({
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }, options.headers || {});

        if (options.body instanceof FormData) {
            options.body.append('_csrf', HRIS.csrfToken);
        } else if (options.method && options.method.toUpperCase() !== 'GET') {
            options.headers['Content-Type'] = 'application/json';
            const payload = options.body ? JSON.parse(options.body) : {};
            payload._csrf = HRIS.csrfToken;
            options.body = JSON.stringify(payload);
        }

        const res = await fetch(url, options);
        let data;
        try {
            data = await res.json();
        } catch (e) {
            data = { success: false, message: 'Respon server tidak valid.' };
        }
        if (!res.ok && !data.message) {
            data.message = 'Terjadi kesalahan (' + res.status + ')';
        }
        return { ok: res.ok, status: res.status, data };
    };

    HRIS.toast = function (icon, message) {
        if (window.Swal) {
            Swal.fire({
                icon: icon,
                title: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        } else {
            alert(message);
        }
    };

    HRIS.confirmDelete = function (message) {
        message = message || 'Data yang dihapus tidak dapat dikembalikan.';
        if (!window.Swal) {
            return Promise.resolve(confirm(message));
        }
        return Swal.fire({
            title: 'Apakah Anda yakin?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#155EEF',
            cancelButtonColor: '#94A3B8',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
        }).then(res => res.isConfirmed);
    };

    HRIS.deleteRow = function (url, onSuccess) {
        HRIS.confirmDelete().then(async confirmed => {
            if (!confirmed) return;
            const { data } = await HRIS.fetchJson(url, { method: 'DELETE' });
            if (data.success) {
                HRIS.toast('success', data.message || 'Data berhasil dihapus.');
                if (onSuccess) onSuccess(data);
                else setTimeout(() => window.location.reload(), 700);
            } else {
                HRIS.toast('error', data.message || 'Gagal menghapus data.');
            }
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('appSidebar');
        const mainWrap = document.getElementById('appMainWrap');
        const toggleBtn = document.getElementById('sidebarToggle');
        const mobileToggleBtn = document.getElementById('mobileSidebarToggle');
        const backdrop = document.getElementById('sidebarBackdrop');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                sidebar.classList.toggle('collapsed');
                mainWrap.classList.toggle('sidebar-collapsed');
                localStorage.setItem('hris_sidebar_collapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
            });
            if (localStorage.getItem('hris_sidebar_collapsed') === '1') {
                sidebar.classList.add('collapsed');
                mainWrap.classList.add('sidebar-collapsed');
            }
        }

        if (mobileToggleBtn) {
            mobileToggleBtn.addEventListener('click', function () {
                sidebar.classList.toggle('mobile-open');
                backdrop.classList.toggle('show');
            });
        }
        if (backdrop) {
            backdrop.addEventListener('click', function () {
                sidebar.classList.remove('mobile-open');
                backdrop.classList.remove('show');
            });
        }

        document.querySelectorAll('[data-confirm-delete]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                const url = el.getAttribute('href') || el.dataset.url;
                HRIS.deleteRow(url);
            });
        });
    });
})();
