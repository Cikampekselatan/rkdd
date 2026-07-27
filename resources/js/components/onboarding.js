const onboardingForm = document.querySelector('[data-onboarding-form]');

onboardingForm?.addEventListener('submit', () => {
    const button = onboardingForm.querySelector('[data-wizard-submit]');

    if (button) {
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Menyimpan...</span>';
    }
});

document.querySelector('input[name="device_access[]"][value="none"]')?.addEventListener('change', (event) => {
    if (!event.currentTarget.checked) {
        return;
    }

    document.querySelectorAll('input[name="device_access[]"]:not([value="none"])').forEach((input) => {
        input.checked = false;
    });
});
