const sidebar = document.querySelector('[data-sidebar]');
const sidebarToggle = document.querySelector('[data-sidebar-toggle]');

if (sidebar && sidebarToggle) {
    const storageKey = 'skuad-sidebar-collapsed';

    const setCollapsed = (collapsed) => {
        sidebar.classList.toggle('is-collapsed', collapsed);
        sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
        sidebarToggle.setAttribute('aria-label', collapsed ? 'Perluas sidebar' : 'Ciutkan sidebar');
    };

    try {
        setCollapsed(localStorage.getItem(storageKey) === 'true');
    } catch {
        setCollapsed(false);
    }

    sidebarToggle.addEventListener('click', () => {
        const collapsed = !sidebar.classList.contains('is-collapsed');
        setCollapsed(collapsed);

        try {
            localStorage.setItem(storageKey, String(collapsed));
        } catch {
            // The visual toggle still works when storage is unavailable.
        }
    });
}
