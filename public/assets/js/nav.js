document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.nav-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (!toggle || !sidebar) {
        return;
    }

    toggle.textContent = '⋮';
    toggle.setAttribute('aria-label', 'Open navigation menu');

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });

    document.addEventListener('click', (event) => {
        if (!sidebar.contains(event.target) && !toggle.contains(event.target) && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
        }
    });
});
