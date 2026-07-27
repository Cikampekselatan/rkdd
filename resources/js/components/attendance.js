const attendanceForm = document.querySelector('[data-attendance-form]');

document.querySelectorAll('[data-attendance-mark-all]').forEach((button) => {
    button.addEventListener('click', () => {
        const status = button.dataset.attendanceMarkAll;
        attendanceForm?.querySelectorAll(`input[type="radio"][value="${status}"]`).forEach((input) => {
            input.checked = true;
        });
    });
});

document.querySelector('[data-attendance-filter]')?.addEventListener('change', (event) => {
    const classSelect = event.currentTarget.form?.querySelector('[name="class_id"]');
    if (classSelect) classSelect.value = '';
    event.currentTarget.form?.submit();
});

document.querySelectorAll('[data-attendance-scanner]').forEach((scanner) => {
    const video = scanner.querySelector('[data-attendance-scanner-video]');
    const state = scanner.querySelector('[data-attendance-scanner-state]');
    const startButton = scanner.querySelector('[data-attendance-scanner-start]');
    const stopButton = scanner.querySelector('[data-attendance-scanner-stop]');
    const allowedPath = scanner.dataset.checkInPath || '/student/attendance/check-in/';
    let stream = null;
    let detector = null;
    let scanning = false;

    const setState = (title, message, icon = 'bi-info-circle') => {
        if (!state) return;
        state.innerHTML = `<i class="bi ${icon}"></i><strong>${title}</strong><small>${message}</small>`;
    };

    const stop = () => {
        scanning = false;
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
        if (video) video.srcObject = null;
        startButton?.removeAttribute('disabled');
        stopButton?.setAttribute('disabled', 'disabled');
        setState('Kamera berhenti', 'Tekan mulai scan untuk mencoba lagi.', 'bi-camera-video-off');
    };

    const acceptCode = (rawValue) => {
        let url;
        try {
            url = new URL(rawValue, window.location.origin);
        } catch (_error) {
            return false;
        }

        if (url.origin !== window.location.origin || !url.pathname.includes(allowedPath)) {
            setState('QR bukan presensi SKUAD', 'Arahkan kamera ke QR yang tampil di layar guru/coach.', 'bi-exclamation-triangle');
            return false;
        }

        stop();
        window.location.href = url.toString();

        return true;
    };

    const scanLoop = async () => {
        if (!scanning || !detector || !video) return;

        try {
            const codes = await detector.detect(video);
            const matched = codes.find((code) => code.rawValue && acceptCode(code.rawValue));
            if (matched) return;
        } catch (_error) {
            // Some browsers throw while video metadata is still warming up; keep scanning.
        }

        window.requestAnimationFrame(scanLoop);
    };

    startButton?.addEventListener('click', async () => {
        if (!('BarcodeDetector' in window)) {
            setState('Browser belum mendukung scanner in-app', 'Gunakan Chrome terbaru, atau scan QR dengan kamera bawaan HP.', 'bi-browser-chrome');
            return;
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            setState('Kamera tidak tersedia', 'Pastikan halaman dibuka melalui HTTPS dan izin kamera diberikan.', 'bi-camera-video-off');
            return;
        }

        try {
            detector = new window.BarcodeDetector({ formats: ['qr_code'] });
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' } },
                audio: false,
            });
            video.srcObject = stream;
            await video.play();
            scanning = true;
            startButton.setAttribute('disabled', 'disabled');
            stopButton?.removeAttribute('disabled');
            setState('Mencari QR...', 'Arahkan kamera ke QR presensi di layar kelas.', 'bi-qr-code-scan');
            scanLoop();
        } catch (_error) {
            stop();
            setState('Kamera gagal dibuka', 'Izinkan kamera dan pastikan browser memakai HTTPS.', 'bi-shield-exclamation');
        }
    });

    stopButton?.addEventListener('click', stop);
});
