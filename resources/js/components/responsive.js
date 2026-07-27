import { Offcanvas } from 'bootstrap';

const mobileQuery = window.matchMedia('(max-width: 767.98px)');
const filterSelector = '.report-filter, .portfolio-filter, .phase12-filter, .attendance-filter-card, .student-document-filter';
const filterStates = new Map();

const mountFilter = (form, index) => {
    if (filterStates.has(form)) return;

    const marker = document.createComment(`responsive-filter-${index}`);
    const id = `responsiveFilter${index}`;
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'btn btn-outline-primary responsive-filter-trigger';
    trigger.setAttribute('data-bs-toggle', 'offcanvas');
    trigger.setAttribute('data-bs-target', `#${id}`);
    trigger.setAttribute('aria-controls', id);
    trigger.innerHTML = '<i class="bi bi-funnel" aria-hidden="true"></i><span>Buka filter</span>';

    const panel = document.createElement('div');
    panel.className = 'offcanvas offcanvas-end responsive-filter-offcanvas';
    panel.tabIndex = -1;
    panel.id = id;
    panel.setAttribute('aria-labelledby', `${id}Label`);
    panel.innerHTML = `<div class="offcanvas-header border-bottom"><div><p class="skuad-eyebrow mb-1">Penyaringan data</p><h2 class="offcanvas-title h5" id="${id}Label">Filter halaman</h2></div><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup filter"></button></div><div class="offcanvas-body"></div>`;

    form.before(marker);
    marker.parentNode.insertBefore(trigger, marker.nextSibling);
    panel.querySelector('.offcanvas-body').append(form);
    document.body.append(panel);
    filterStates.set(form, { marker, trigger, panel });
};

const unmountFilter = (form) => {
    const state = filterStates.get(form);
    if (!state) return;
    Offcanvas.getInstance(state.panel)?.dispose();
    state.marker.parentNode.insertBefore(form, state.marker.nextSibling);
    state.trigger.remove();
    state.panel.remove();
    state.marker.remove();
    filterStates.delete(form);
};

const syncFilters = () => {
    document.querySelectorAll(filterSelector).forEach((form, index) => {
        if (mobileQuery.matches) mountFilter(form, index);
        else unmountFilter(form);
    });
};

const enhanceTables = () => {
    document.querySelectorAll('.skuad-table').forEach((table) => {
        const labels = [...table.querySelectorAll('thead th')].map((header) => header.textContent.trim());
        table.querySelectorAll('tbody tr').forEach((row) => {
            row.querySelectorAll('td').forEach((cell, index) => {
                cell.dataset.label ||= labels[index] || `Kolom ${index + 1}`;
                if (cell.hasAttribute('colspan')) cell.classList.add('responsive-table-empty');
            });
        });
        table.classList.add('is-responsive-cards');
    });
};

const improveFeedback = () => {
    document.querySelectorAll('.alert-danger').forEach((alert) => alert.setAttribute('role', 'alert'));
    document.querySelectorAll('.alert-success').forEach((alert) => alert.setAttribute('role', 'status'));
    document.querySelectorAll('.btn-close:not([aria-label])').forEach((button) => button.setAttribute('aria-label', 'Tutup'));
};

const addSubmitFeedback = () => {
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.noLoading !== undefined) return;
        const button = event.submitter;
        if (!(button instanceof HTMLButtonElement) || button.classList.contains('is-submitting')) return;

        button.classList.add('is-submitting');
        button.setAttribute('aria-busy', 'true');
        const spinner = document.createElement('span');
        spinner.className = 'spinner-border spinner-border-sm responsive-submit-spinner';
        spinner.setAttribute('aria-hidden', 'true');
        button.prepend(spinner);
        requestAnimationFrame(() => { button.disabled = true; });
    });
};

document.querySelector('main')?.setAttribute('id', 'main-content');
enhanceTables();
improveFeedback();
addSubmitFeedback();
syncFilters();
mobileQuery.addEventListener('change', syncFilters);
