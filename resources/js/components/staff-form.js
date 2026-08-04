document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        const icon = button.querySelector('i');

        if (!input) {
            return;
        }

        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        icon?.classList.toggle('bi-eye', !isHidden);
        icon?.classList.toggle('bi-eye-slash', isHidden);
        button.setAttribute('aria-label', isHidden ? 'Sembunyikan kata sandi' : 'Lihat kata sandi');
        button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        button.setAttribute('title', isHidden ? 'Sembunyikan kata sandi' : 'Lihat kata sandi');
    });
});
