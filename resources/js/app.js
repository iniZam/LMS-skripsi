// Small progressive-enhancement helpers. No framework needed for this app.

document.addEventListener('DOMContentLoaded', () => {
    // Confirm before any destructive form submission.
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.getAttribute('data-confirm') || 'Apakah Anda yakin?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // Mobile sidebar toggle.
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('app-sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });
    }

    // Auto-hide flash banners after a few seconds.
    document.querySelectorAll('[data-flash]').forEach((el) => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.5s ease';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 5000);
    });

    // Toggle assignment fields in the meeting form based on the checkbox.
    const hasAssignment = document.getElementById('has_assignment');
    const assignmentFields = document.getElementById('assignment-fields');
    if (hasAssignment && assignmentFields) {
        const sync = () => assignmentFields.classList.toggle('hidden', !hasAssignment.checked);
        hasAssignment.addEventListener('change', sync);
        sync();
    }
});
