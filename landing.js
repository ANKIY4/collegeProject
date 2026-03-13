document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('menu-toggle');
    const mainNav = document.getElementById('main-nav');
    const header = document.querySelector('.site-header');
    const footerYear = document.getElementById('footer-year');
    const currentDate = document.getElementById('current-date');

    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', () => {
            const isOpen = mainNav.classList.toggle('open');
            menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        mainNav.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                mainNav.classList.remove('open');
                menuToggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    window.addEventListener('scroll', () => {
        if (!header) return;
        header.classList.toggle('scrolled', window.scrollY > 6);
    });

    const now = new Date();
    if (footerYear) footerYear.textContent = String(now.getFullYear());
    if (currentDate) {
        currentDate.textContent = new Intl.DateTimeFormat('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        }).format(now);
    }
});
