document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const message = form.dataset.confirm ?? 'Lanjutkan tindakan ini?';

        if (!window.confirm(message)) {
            event.preventDefault();
            return;
        }

        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
        });
    });
});

const studentDetail = document.querySelector('[data-student-detail]');
const studentSkeleton = document.querySelector('[data-student-skeleton]');

if (studentDetail) {
    requestAnimationFrame(() => {
        studentSkeleton?.remove();
        studentDetail.hidden = false;
        studentDetail.classList.add('student-detail-ready');
    });
}
