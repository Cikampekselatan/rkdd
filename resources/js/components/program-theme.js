const bindProgramThemeForm = (form) => {
    const primary = form.querySelector('[data-program-theme-primary]');
    const secondary = form.querySelector('[data-program-theme-secondary]');
    const accent = form.querySelector('[data-program-theme-accent]');
    const name = form.querySelector('[data-program-theme-name]');
    const type = form.querySelector('[data-program-theme-type]');
    const description = form.querySelector('[data-program-theme-description]');
    const previewName = form.querySelector('[data-program-theme-preview-name]');
    const previewType = form.querySelector('[data-program-theme-preview-type]');
    const previewDescription = form.querySelector('[data-program-theme-preview-description]');

    const syncPreview = () => {
        form.style.setProperty('--program-primary', primary?.value || '#0f766e');
        form.style.setProperty('--program-secondary', secondary?.value || '#0f172a');
        form.style.setProperty('--program-accent', accent?.value || '#f59e0b');

        if (previewName) previewName.textContent = name?.value?.trim() || 'Nama Program';
        if (previewType) previewType.textContent = type?.selectedOptions?.[0]?.textContent || 'Program';
        if (previewDescription) {
            previewDescription.textContent = description?.value?.trim()
                || 'Preview ini membantu memastikan karakter warna program terlihat premium dan teks tetap terbaca.';
        }
    };

    [primary, secondary, accent, name, type, description].forEach((input) => {
        input?.addEventListener('input', syncPreview);
        input?.addEventListener('change', syncPreview);
    });

    form.querySelectorAll('[data-program-theme-preset]').forEach((button) => {
        button.addEventListener('click', () => {
            if (primary) primary.value = button.dataset.primary;
            if (secondary) secondary.value = button.dataset.secondary;
            if (accent) accent.value = button.dataset.accent;
            syncPreview();
        });
    });

    syncPreview();
};

document.querySelectorAll('[data-program-theme-form]').forEach(bindProgramThemeForm);
