import { Modal } from 'bootstrap';

const previewModalElement = document.querySelector('#documentPreviewModal');

document.querySelectorAll('[data-document-preview]').forEach((button) => {
    button.addEventListener('click', () => {
        if (!previewModalElement) {
            return;
        }

        const frame = previewModalElement.querySelector('[data-document-preview-frame]');
        const title = previewModalElement.querySelector('[data-document-preview-title]');
        const openLink = previewModalElement.querySelector('[data-document-preview-open]');

        if (frame) frame.src = button.dataset.previewUrl ?? '';
        if (title) title.textContent = button.dataset.previewTitle ?? 'Preview dokumen';
        if (openLink) openLink.href = button.dataset.documentUrl ?? '#';

        Modal.getOrCreateInstance(previewModalElement).show();
    });
});

previewModalElement?.addEventListener('hidden.bs.modal', () => {
    const frame = previewModalElement.querySelector('[data-document-preview-frame]');
    if (frame) frame.src = 'about:blank';
});

document.querySelectorAll('[data-copy-document-url]').forEach((button) => {
    button.addEventListener('click', async () => {
        const originalLabel = button.innerHTML;

        try {
            await navigator.clipboard.writeText(button.dataset.copyDocumentUrl ?? '');
            button.innerHTML = '<i class="bi bi-check-lg"></i><span>Tersalin</span>';
        } catch {
            button.innerHTML = '<i class="bi bi-x-lg"></i><span>Gagal menyalin</span>';
        }

        setTimeout(() => { button.innerHTML = originalLabel; }, 1800);
    });
});
