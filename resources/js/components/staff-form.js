document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);

        if (!input) {
            return;
        }

        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        button.innerHTML = `<i class="bi ${isHidden ? 'bi-eye-slash' : 'bi-eye'}" aria-hidden="true"></i>`;
        button.setAttribute('aria-label', isHidden ? 'Sembunyikan kata sandi' : 'Lihat kata sandi');
    });
});
