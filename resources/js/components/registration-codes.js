document.querySelectorAll('[data-copy-code]').forEach((button) => button.addEventListener('click', async (event) => {
    const wrapper = event.currentTarget.closest('.registration-code-value');
    const code = wrapper?.querySelector('[data-generated-code]')?.textContent?.trim();

    if (!code) {
        return;
    }

    await navigator.clipboard.writeText(code);
    event.currentTarget.innerHTML = '<i class="bi bi-check-lg" aria-hidden="true"></i>';
    event.currentTarget.setAttribute('aria-label', 'Kode tersalin');
}));

document.querySelector('#deleteCodeModal')?.addEventListener('show.bs.modal', (event) => {
    const trigger = event.relatedTarget;
    const form = event.currentTarget.querySelector('[data-delete-code-form]');
    const name = event.currentTarget.querySelector('[data-delete-code-name]');

    if (trigger && form && name) {
        form.action = trigger.dataset.deleteUrl;
        name.textContent = trigger.dataset.codeName;
    }
});
