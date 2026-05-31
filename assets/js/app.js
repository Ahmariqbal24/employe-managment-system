/**
 * assets/js/app.js
 * EMS Pro – Main JavaScript
 * Handles sidebar toggle, auto-dismiss alerts, and minor UX enhancements.
 */

'use strict';

/* ── DOM Ready ─────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {

    // ── Sidebar toggle (mobile) ─────────────────────────────
    const sidebar        = document.getElementById('sidebar');
    const overlay        = document.getElementById('sidebarOverlay');
    const toggleBtn      = document.getElementById('sidebarToggle');

    function openSidebar() {
        sidebar?.classList.add('open');
        overlay?.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar?.classList.remove('open');
        overlay?.classList.remove('open');
        document.body.style.overflow = '';
    }

    toggleBtn?.addEventListener('click', function () {
        sidebar?.classList.contains('open') ? closeSidebar() : openSidebar();
    });
    overlay?.addEventListener('click', closeSidebar);

    // Close sidebar on ESC key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSidebar();
    });

    // ── Auto-dismiss flash alerts after 5 s ─────────────────
    document.querySelectorAll('.alert.alert-dismissible').forEach(function (el) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert?.close();
        }, 5000);
    });

    // ── Confirm delete links ────────────────────────────────
    // Fallback if onclick="return confirm(...)" is not used inline
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // ── Form: prevent double submit ─────────────────────────
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('[type="submit"]');
            if (btn) {
                btn.disabled = true;
                const orig = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…';
                // Re-enable after 6 s as a safety net
                setTimeout(function () {
                    btn.disabled = false;
                    btn.innerHTML = orig;
                }, 6000);
            }
        });
    });

    // ── Highlight active sidebar link by exact URL match ────
    const currentHref = window.location.href.split('?')[0];
    document.querySelectorAll('.sidebar-nav-link').forEach(function (link) {
        if (link.href.split('?')[0] === currentHref) {
            link.classList.add('active');
        }
    });

    // ── Animate stat card counters ──────────────────────────
    document.querySelectorAll('.stat-card-value').forEach(function (el) {
        const raw    = parseInt(el.textContent.replace(/[^0-9]/g, ''), 10);
        const prefix = el.textContent.match(/^\$/) ? '$' : '';
        if (!isNaN(raw) && raw > 0) {
            let start     = 0;
            const duration = 700;
            const step    = Math.ceil(raw / (duration / 16));
            const timer   = setInterval(function () {
                start = Math.min(start + step, raw);
                el.textContent = prefix + start.toLocaleString();
                if (start >= raw) clearInterval(timer);
            }, 16);
        }
    });

});
