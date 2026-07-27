document.querySelectorAll('[data-signature-pad]').forEach((pad) => {
    const canvas = pad.querySelector('[data-signature-canvas]');
    const output = pad.querySelector('[data-signature-output]');
    const clearButton = pad.querySelector('[data-signature-clear]');
    const fileInput = pad.querySelector('[data-signature-file]');
    const drawPanel = pad.querySelector('[data-signature-draw-panel]');
    const uploadPanel = pad.querySelector('[data-signature-upload-panel]');
    const methodInputs = pad.querySelectorAll('[data-signature-method]');
    const form = pad.closest('form');
    const context = canvas?.getContext('2d');
    let drawing = false;
    let hasInk = false;

    if (!canvas || !context || !output) return;

    const resize = () => {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const image = hasInk ? canvas.toDataURL('image/png') : null;
        const rect = canvas.getBoundingClientRect();
        canvas.width = Math.max(1, Math.floor(rect.width * ratio));
        canvas.height = Math.max(1, Math.floor(rect.height * ratio));
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        context.lineCap = 'round';
        context.lineJoin = 'round';
        context.lineWidth = 3;
        context.strokeStyle = '#071827';

        if (image) {
            const img = new Image();
            img.onload = () => context.drawImage(img, 0, 0, rect.width, rect.height);
            img.src = image;
        }
    };

    const point = (event) => {
        const rect = canvas.getBoundingClientRect();
        return { x: event.clientX - rect.left, y: event.clientY - rect.top };
    };

    const start = (event) => {
        event.preventDefault();
        drawing = true;
        hasInk = true;
        const { x, y } = point(event);
        context.beginPath();
        context.moveTo(x, y);
    };

    const move = (event) => {
        if (!drawing) return;
        event.preventDefault();
        const { x, y } = point(event);
        context.lineTo(x, y);
        context.stroke();
    };

    const stop = () => {
        drawing = false;
    };

    const clear = () => {
        context.clearRect(0, 0, canvas.width, canvas.height);
        output.value = '';
        hasInk = false;
    };

    const selectedMethod = () => pad.querySelector('[data-signature-method]:checked')?.value || 'draw';

    const syncPanels = () => {
        const draw = selectedMethod() === 'draw';
        drawPanel?.classList.toggle('d-none', !draw);
        uploadPanel?.classList.toggle('d-none', draw);
        if (draw && fileInput) fileInput.value = '';
        if (!draw) clear();
    };

    canvas.addEventListener('pointerdown', start);
    canvas.addEventListener('pointermove', move);
    canvas.addEventListener('pointerup', stop);
    canvas.addEventListener('pointercancel', stop);
    canvas.addEventListener('pointerleave', stop);
    clearButton?.addEventListener('click', clear);
    methodInputs.forEach((input) => input.addEventListener('change', syncPanels));
    form?.addEventListener('submit', () => {
        if (selectedMethod() === 'draw' && hasInk) {
            output.value = canvas.toDataURL('image/png');
        }
    });

    window.addEventListener('resize', resize);
    resize();
    syncPanels();
});
