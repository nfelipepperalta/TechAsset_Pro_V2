/* ============================================================
   TechAsset Pro — JavaScript principal
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

    // ── Toggle sidebar en móvil ────────────────────────────────
    const toggle  = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // ── Auto-cerrar alertas de éxito tras 4s ──────────────────
    document.querySelectorAll('.alert-success').forEach(el => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert?.close();
        }, 4000);
    });

    // ── Marcar alerta como vista al hacer clic (AJAX) ─────────
    document.querySelectorAll('[data-mark-read]').forEach(btn => {
        btn.addEventListener('click', function () {
            const id  = this.dataset.markRead;
            const url = (window.APP_URL || '') + '/alerts/' + id + '/read';
            fetch(url, { method: 'POST' })
                .then(r => r.json())
                .then(d => { if (d.success) this.closest('li')?.classList.add('opacity-50'); })
                .catch(() => {});
        });
    });

    // ── Confirmación para formularios de eliminación ──────────
    document.querySelectorAll('[data-confirm]').forEach(form => {
        form.addEventListener('submit', function (e) {
            const msg = this.dataset.confirm || '¿Estás seguro?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // ── Activar tooltips de Bootstrap ─────────────────────────
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

});
