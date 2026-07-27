import { Modal, Toast } from 'bootstrap';

const toastElement = document.querySelector('#demoToast');
const toastTrigger = document.querySelector('[data-toast-demo]');

const showToast = () => {
    if (toastElement) {
        Toast.getOrCreateInstance(toastElement, { delay: 4200 }).show();
    }
};

toastTrigger?.addEventListener('click', showToast);

document.querySelector('[data-confirm-action]')?.addEventListener('click', (event) => {
    const modalElement = event.currentTarget.closest('.modal');

    if (modalElement) {
        Modal.getOrCreateInstance(modalElement).hide();
    }

    showToast();
});

document.querySelector('[data-auth-form]')?.addEventListener('submit', (event) => {
    const button = event.currentTarget.querySelector('[data-submit-button]');

    if (button) {
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Memeriksa akun...</span>';
    }
});
