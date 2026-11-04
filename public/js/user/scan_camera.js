document.addEventListener('DOMContentLoaded', async () => {
    const videoElem = document.getElementById('video');
    const resultElem = document.getElementById('result');
    const startBtn = document.getElementById('start-scan');
    const stopBtn = document.getElementById('stop-scan');
    const selectElem = document.getElementById('camera-select');

    if (!videoElem || !resultElem || !startBtn || !stopBtn || !selectElem) {
        return;
    }

    const cfgEl = document.getElementById('scan-qr-config');
    let scanConfig = {};
    if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
        try {
            scanConfig = JSON.parse(cfgEl.dataset.config);
        } catch (_) {}
    }

    // Tạo container cho video để kiểm soát overflow khi zoom
    const videoContainer = document.createElement('div');
    videoContainer.id = 'video-container';
    videoContainer.style.width = '100%';
    videoContainer.style.height = '100%';
    videoContainer.style.position = 'relative';
    videoContainer.style.overflow = 'hidden';

    videoElem.parentNode.insertBefore(videoContainer, videoElem);
    videoContainer.appendChild(videoElem);

    // Thêm các điều khiển nâng cao
    const zoomSlider = document.createElement('input');
    zoomSlider.type = 'range';
    zoomSlider.min = '100';
    zoomSlider.max = '200';
    zoomSlider.value = '100';
    zoomSlider.step = '25';
    zoomSlider.id = 'zoom-control';
    zoomSlider.style.width = '100%';
    zoomSlider.style.marginTop = '6px';

    const zoomValue = document.createElement('span');
    zoomValue.textContent = 'Thu phóng: 100%';
    zoomValue.id = 'zoom-value';
    zoomValue.className = 'small font-weight-bold text-secondary';

    const brightnessSlider = document.createElement('input');
    brightnessSlider.type = 'range';
    brightnessSlider.min = '50';
    brightnessSlider.max = '180';
    brightnessSlider.value = '100';
    brightnessSlider.step = '10';
    brightnessSlider.id = 'brightness-control';
    brightnessSlider.style.width = '100%';
    brightnessSlider.style.marginTop = '6px';

    const brightnessValue = document.createElement('span');
    brightnessValue.textContent = 'Độ sáng: 100%';
    brightnessValue.id = 'brightness-value';
    brightnessValue.className = 'small font-weight-bold text-secondary';

    const contrastSlider = document.createElement('input');
    contrastSlider.type = 'range';
    contrastSlider.min = '50';
    contrastSlider.max = '180';
    contrastSlider.value = '100';
    contrastSlider.step = '10';
    contrastSlider.id = 'contrast-control';
    contrastSlider.style.width = '100%';
    contrastSlider.style.marginTop = '6px';

    const contrastValue = document.createElement('span');
    contrastValue.textContent = 'Độ tương phản: 100%';
    contrastValue.id = 'contrast-value';
    contrastValue.className = 'small font-weight-bold text-secondary';

    // Checkbox cho chế độ quét liên tục
    const continuousScanDiv = document.createElement('div');
    continuousScanDiv.style.marginTop = '8px';

    const continuousScanCheck = document.createElement('input');
    continuousScanCheck.type = 'checkbox';
    continuousScanCheck.id = 'continuous-scan';
    continuousScanCheck.checked = false;
    continuousScanCheck.style.marginRight = '6px';

    const continuousScanLabel = document.createElement('label');
    continuousScanLabel.htmlFor = 'continuous-scan';
    continuousScanLabel.className = 'small mb-0';
    continuousScanLabel.textContent = 'Quét liên tục nhiều thẻ (không tự tắt camera sau khi quét)';

    continuousScanDiv.appendChild(continuousScanCheck);
    continuousScanDiv.appendChild(continuousScanLabel);

    // Chèn các điều khiển vào DOM
    const controlsDiv = document.createElement('div');
    controlsDiv.id = 'advanced-controls';
    controlsDiv.style.padding = '12px';
    controlsDiv.style.backgroundColor = '#f8fafc';
    controlsDiv.style.border = '1px solid #e2e8f0';
    controlsDiv.style.borderRadius = '8px';
    controlsDiv.style.marginTop = '12px';
    controlsDiv.style.textAlign = 'left';

    const controlsTitle = document.createElement('div');
    controlsTitle.textContent = '⚙️ Tùy chỉnh hình ảnh Camera';
    controlsTitle.style.fontWeight = '700';
    controlsTitle.style.fontSize = '0.85rem';
    controlsTitle.style.marginBottom = '8px';
    controlsDiv.appendChild(controlsTitle);

    const zoomDiv = document.createElement('div');
    zoomDiv.appendChild(zoomValue);
    zoomDiv.appendChild(zoomSlider);
    controlsDiv.appendChild(zoomDiv);

    const brightnessDiv = document.createElement('div');
    brightnessDiv.style.marginTop = '6px';
    brightnessDiv.appendChild(brightnessValue);
    brightnessDiv.appendChild(brightnessSlider);
    controlsDiv.appendChild(brightnessDiv);

    const contrastDiv = document.createElement('div');
    contrastDiv.style.marginTop = '6px';
    contrastDiv.appendChild(contrastValue);
    contrastDiv.appendChild(contrastSlider);
    controlsDiv.appendChild(contrastDiv);

    // Thanh trượt lấy nét gần (Macro Focus Distance) – chỉ hiện khi phần cứng camera hỗ trợ
    const focusDiv = document.createElement('div');
    focusDiv.id = 'focus-control-wrapper';
    focusDiv.style.marginTop = '6px';
    focusDiv.style.display = 'none';

    const focusValue = document.createElement('span');
    focusValue.textContent = 'Tiêu cự lấy nét: Tự động';
    focusValue.id = 'focus-value';
    focusValue.className = 'small font-weight-bold text-secondary';

    const focusSlider = document.createElement('input');
    focusSlider.type = 'range';
    focusSlider.min = '0';
    focusSlider.max = '100';
    focusSlider.value = '0';
    focusSlider.step = '5';
    focusSlider.id = 'focus-control';
    focusSlider.style.width = '100%';
    focusSlider.style.marginTop = '6px';

    focusDiv.appendChild(focusValue);
    focusDiv.appendChild(focusSlider);
    controlsDiv.appendChild(focusDiv);

    controlsDiv.appendChild(continuousScanDiv);

    const controlsContainer = document.querySelector('.controls') || startBtn.parentElement;
    if (controlsContainer && controlsContainer.parentNode) {
        controlsContainer.parentNode.insertBefore(controlsDiv, controlsContainer.nextSibling);
    }

    let stream = null;
    let isScanning = false;
    let scanTimer = null;
    let scanAttempts = 0;
    let lastSuccessfulScan = '';
    let zoomLevel = 100;
    let hwZoomRange = null; // { min, max } nếu camera hỗ trợ zoom phần cứng
    let hwFocusRange = null; // { min, max } nếu camera hỗ trợ lấy nét thủ công (focusDistance)
    let currentParsedCccd = null;
    let currentMatchedCustomer = null;

    // Canvas và context để xử lý hình ảnh
    const canvas = document.createElement('canvas');
    const canvasContext = canvas.getContext('2d', { willReadFrequently: true });
    const MAX_ANALYSIS_DIMENSION = 1280;
    const TARGET_SCAN_PERIOD_MS = 120;

    function configureAnalysisCanvas() {
        const sourceWidth = videoElem.videoWidth || 640;
        const sourceHeight = videoElem.videoHeight || 480;
        const scale = Math.min(1, MAX_ANALYSIS_DIMENSION / Math.max(sourceWidth, sourceHeight));
        canvas.width = Math.max(1, Math.round(sourceWidth * scale));
        canvas.height = Math.max(1, Math.round(sourceHeight * scale));
    }

    function applyHardwareZoomIfSupported() {
        if (!hwZoomRange || !stream) return false;
        const track = stream.getVideoTracks()[0];
        if (!track || typeof track.applyConstraints !== 'function') return false;
        const ratio = Math.max(0, Math.min(1, (zoomLevel - 100) / 100));
        const targetZoom = hwZoomRange.min + (hwZoomRange.max - hwZoomRange.min) * ratio;
        track.applyConstraints({ advanced: [{ zoom: targetZoom }] }).catch(() => {});
        return true;
    }

    zoomSlider.addEventListener('input', (e) => {
        zoomLevel = parseInt(e.target.value, 10) || 100;
        zoomValue.textContent = `Thu phóng: ${zoomLevel}%`;
        updateScannerArea();
    });

    brightnessSlider.addEventListener('input', (e) => {
        const brightness = e.target.value;
        brightnessValue.textContent = `Độ sáng: ${brightness}%`;
        updateVideoStyles();
    });

    contrastSlider.addEventListener('input', (e) => {
        const contrast = e.target.value;
        contrastValue.textContent = `Độ tương phản: ${contrast}%`;
        updateVideoStyles();
    });

    focusSlider.addEventListener('input', (e) => {
        if (!hwFocusRange || !stream) return;
        const track = stream.getVideoTracks()[0];
        if (!track || typeof track.applyConstraints !== 'function') return;
        const pct = parseInt(e.target.value, 10) || 0;
        if (pct === 0 && hwFocusRange.canContinuous) {
            focusValue.textContent = 'Tiêu cự lấy nét: Tự động';
            track.applyConstraints({ advanced: [{ focusMode: 'continuous' }] }).catch(() => {});
            return;
        }
        const ratio = pct / 100;
        const dist = hwFocusRange.min + (hwFocusRange.max - hwFocusRange.min) * ratio;
        focusValue.textContent = `Tiêu cự lấy nét gần: ${pct}%`;
        track.applyConstraints({ advanced: [{ focusMode: 'manual', focusDistance: dist }] }).catch(() => {});
    });

    function updateVideoStyles() {
        const brightness = brightnessSlider.value;
        const contrast = contrastSlider.value;
        videoElem.style.filter = `brightness(${brightness}%) contrast(${contrast}%)`;
    }

    function updateScannerArea() {
        const scannerArea = document.getElementById('scanner-area');
        if (scannerArea) {
            const baseWidth = 60;
            const baseHeight = 60;
            const top = (100 - baseHeight) / 2;
            const left = (100 - baseWidth) / 2;

            scannerArea.style.top = `${top}%`;
            scannerArea.style.left = `${left}%`;
            scannerArea.style.width = `${baseWidth}%`;
            scannerArea.style.height = `${baseHeight}%`;
        }

        if (applyHardwareZoomIfSupported()) {
            videoElem.style.transform = 'scale(1)';
            return;
        }

        const zoomScale = zoomLevel / 100;
        videoElem.style.transform = `scale(${zoomScale})`;
        videoElem.style.transformOrigin = 'center center';
    }

    async function populateCameraOptions(preferredDeviceId = '') {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
            resultElem.textContent = '⚠️ Trình duyệt không hỗ trợ truy cập danh sách camera (cần HTTPS hoặc localhost).';
            return;
        }
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(device => device.kind === 'videoinput');

            const previousValue = preferredDeviceId || selectElem.value;
            selectElem.innerHTML = '';
            videoDevices.forEach((device, index) => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.text = device.label || `Camera ${index + 1}`;
                selectElem.appendChild(option);
            });

            if (previousValue && videoDevices.some(d => d.deviceId === previousValue)) {
                selectElem.value = previousValue;
                return;
            }

            const backCamera = videoDevices.find(device => {
                const label = (device.label || '').toLowerCase();
                return label.includes('back') || label.includes('sau') || label.includes('rear') || label.includes('environment');
            });

            if (backCamera) {
                selectElem.value = backCamera.deviceId;
            }
        } catch (err) {
            console.error('Không thể liệt kê camera:', err);
            resultElem.textContent = '⚠️ Không tìm thấy camera nào!';
        }
    }

    function preprocessImage(imageData) {
        const brightness = parseInt(brightnessSlider.value, 10) / 100;
        const contrast = parseInt(contrastSlider.value, 10) / 100;

        // Cấu hình mặc định dùng trực tiếp buffer gốc, không sao chép hàng triệu byte mỗi lượt quét.
        if (brightness === 1 && contrast === 1) {
            return imageData;
        }

        const data = imageData.data;
        for (let i = 0; i < data.length; i += 4) {
            for (let j = 0; j < 3; j++) {
                let value = data[i + j];
                value = value * brightness;
                value = 128 + (value - 128) * contrast;
                data[i + j] = Math.min(255, Math.max(0, value));
            }
        }

        return imageData;
    }

    function ensureScannerStyleOnce() {
        if (document.getElementById('scanner-area-style')) return;
        const style = document.createElement('style');
        style.id = 'scanner-area-style';
        style.textContent = `
            @keyframes scan-line {
                0% { top: 0; }
                50% { top: calc(100% - 2px); }
                100% { top: 0; }
            }
        `;
        document.head.appendChild(style);
    }

    async function openCameraStream(deviceId) {
        const videoConstraints = {
            width: { ideal: 1920 },
            height: { ideal: 1080 },
            frameRate: { ideal: 30 },
        };

        if (deviceId) {
            videoConstraints.deviceId = { exact: deviceId };
        } else {
            videoConstraints.facingMode = { ideal: 'environment' };
        }

        try {
            return await navigator.mediaDevices.getUserMedia({ video: videoConstraints });
        } catch (primaryErr) {
            console.warn('Fallback cấu hình camera cơ bản:', primaryErr);
            return await navigator.mediaDevices.getUserMedia({
                video: deviceId ? { deviceId: { exact: deviceId } } : true,
            });
        }
    }

    async function configureTrackCapabilities(track) {
        hwZoomRange = null;
        hwFocusRange = null;
        focusDiv.style.display = 'none';
        if (!track || typeof track.getCapabilities !== 'function') return;
        try {
            const caps = track.getCapabilities() || {};
            const adv = {};
            const canContinuousFocus = Array.isArray(caps.focusMode) && caps.focusMode.includes('continuous');
            const canManualFocus = Array.isArray(caps.focusMode) && caps.focusMode.includes('manual');
            if (canContinuousFocus) {
                adv.focusMode = 'continuous';
            }
            if (Array.isArray(caps.exposureMode) && caps.exposureMode.includes('continuous')) {
                adv.exposureMode = 'continuous';
            }
            if (caps.sharpness && typeof caps.sharpness.max === 'number' && typeof caps.sharpness.min === 'number') {
                // Đặt độ sắc nét phần cứng ở mức 75% dải hỗ trợ để làm rõ cạnh mã QR
                adv.sharpness = caps.sharpness.min + (caps.sharpness.max - caps.sharpness.min) * 0.75;
            }
            if (caps.zoom && typeof caps.zoom.min === 'number' && typeof caps.zoom.max === 'number' && caps.zoom.max > caps.zoom.min) {
                hwZoomRange = { min: caps.zoom.min, max: Math.min(caps.zoom.max, caps.zoom.min * 3) };
            }
            if (canManualFocus && caps.focusDistance && typeof caps.focusDistance.min === 'number' && typeof caps.focusDistance.max === 'number' && caps.focusDistance.max > caps.focusDistance.min) {
                hwFocusRange = {
                    min: caps.focusDistance.min,
                    max: caps.focusDistance.max,
                    canContinuous: canContinuousFocus,
                };
                focusSlider.value = '0';
                focusValue.textContent = 'Tiêu cự lấy nét: Tự động (kéo lên nếu thẻ gần bị mờ)';
                focusDiv.style.display = 'block';
            }
            if (Object.keys(adv).length && typeof track.applyConstraints === 'function') {
                await track.applyConstraints({ advanced: [adv] }).catch(() => {});
            }
        } catch (_) {}
    }

    function updateScanningBanner() {
        if (!isScanning || lastSuccessfulScan) return;
        const vw = videoElem.videoWidth;
        const vh = videoElem.videoHeight;
        const resText = vw && vh ? `Camera ${vw}×${vh}` : 'Camera';
        const status = window.CccdQrParser?.getZxingStatus?.() || 'idle';
        const engineText = status === 'ready'
            ? 'ZXing WASM'
            : status === 'failed'
                ? 'jsQR (dự phòng)'
                : 'Đang nạp ZXing...';
        resultElem.textContent = `📡 ${resText} · ${engineText} — Đưa mã QR CCCD vào khung xanh.`;
    }

    async function startScan(deviceId) {
        try {
            if (stream) {
                stopScan();
            }

            resultElem.textContent = '🔄 Đang khởi tạo camera...';
            if (window.CccdQrParser && typeof window.CccdQrParser.warmUpZxing === 'function') {
                window.CccdQrParser.warmUpZxing().then(() => updateScanningBanner()).catch(() => {});
            }
            stream = await openCameraStream(deviceId);
            videoElem.srcObject = stream;

            const activeTrack = stream.getVideoTracks()[0];
            await configureTrackCapabilities(activeTrack);
            const activeDeviceId = activeTrack?.getSettings?.()?.deviceId || deviceId || '';
            await populateCameraOptions(activeDeviceId);

            await new Promise(resolve => {
                videoElem.onloadedmetadata = () => {
                    videoElem.play().catch(() => {});
                    resolve();
                };
            });

            await new Promise(resolve => {
                setTimeout(() => {
                    configureAnalysisCanvas();
                    resolve();
                }, 250);
            });

            scanAttempts = 0;
            lastSuccessfulScan = '';
            isScanning = true;
            updateScanningBanner();

            updateVideoStyles();

            let videoWrapper = document.getElementById('video-wrapper');
            if (!videoWrapper) {
                videoWrapper = document.createElement('div');
                videoWrapper.id = 'video-wrapper';
                videoWrapper.style.position = 'relative';
                videoWrapper.style.width = '100%';
                videoWrapper.style.height = 'auto';
                videoWrapper.style.overflow = 'hidden';
                videoContainer.parentNode.insertBefore(videoWrapper, videoContainer);
                videoWrapper.appendChild(videoContainer);
            }

            ensureScannerStyleOnce();

            let scannerArea = document.getElementById('scanner-area');
            if (!scannerArea) {
                scannerArea = document.createElement('div');
                scannerArea.id = 'scanner-area';
                scannerArea.style.position = 'absolute';
                scannerArea.style.border = '3px solid #04CA77';
                scannerArea.style.borderRadius = '10px';
                scannerArea.style.boxShadow = '0 0 0 5000px rgba(0, 0, 0, 0.32)';
                scannerArea.style.zIndex = '2';
                scannerArea.style.pointerEvents = 'none';
                videoWrapper.appendChild(scannerArea);

                const scanLine = document.createElement('div');
                scanLine.style.position = 'absolute';
                scanLine.style.left = '0';
                scanLine.style.width = '100%';
                scanLine.style.height = '2px';
                scanLine.style.backgroundColor = '#04CA77';
                scanLine.style.boxShadow = '0 0 8px 2px rgba(4, 202, 119, 0.8)';
                scanLine.style.animation = 'scan-line 2s linear infinite';
                scanLine.style.zIndex = '3';
                scannerArea.appendChild(scanLine);
            }

            let qualityHud = document.getElementById('scanner-quality-hud');
            if (!qualityHud) {
                qualityHud = document.createElement('div');
                qualityHud.id = 'scanner-quality-hud';
                qualityHud.style.position = 'absolute';
                qualityHud.style.bottom = '10px';
                qualityHud.style.left = '50%';
                qualityHud.style.transform = 'translateX(-50%)';
                qualityHud.style.maxWidth = '92%';
                qualityHud.style.padding = '5px 12px';
                qualityHud.style.borderRadius = '999px';
                qualityHud.style.backgroundColor = 'rgba(15, 23, 42, 0.82)';
                qualityHud.style.color = '#ffffff';
                qualityHud.style.fontSize = '0.78rem';
                qualityHud.style.fontWeight = '600';
                qualityHud.style.textAlign = 'center';
                qualityHud.style.zIndex = '4';
                qualityHud.style.pointerEvents = 'none';
                qualityHud.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.35)';
                qualityHud.textContent = '📷 Đưa mã QR trên thẻ CCCD vào giữa khung xanh';
                videoWrapper.appendChild(qualityHud);
            }

            updateScannerArea();
            scanQRCode();
        } catch (err) {
            console.error('❌ Không thể bật camera:', err);
            resultElem.textContent = '🚫 Không thể mở camera: ' + (err.message || 'Vui lòng kiểm tra quyền truy cập camera hoặc chọn ảnh QR.');
        }
    }

    function scheduleNextScan(delay = TARGET_SCAN_PERIOD_MS) {
        if (scanTimer) {
            clearTimeout(scanTimer);
        }

        scanTimer = isScanning && !document.hidden
            ? setTimeout(scanQRCode, Math.max(0, delay))
            : null;
    }

    function drawVideoToAnalysisCanvas() {
        const vw = videoElem.videoWidth;
        const vh = videoElem.videoHeight;
        const zoomScale = Math.max(1, zoomLevel / 100);

        if (zoomScale > 1) {
            const cropW = vw / zoomScale;
            const cropH = vh / zoomScale;
            const sx = (vw - cropW) / 2;
            const sy = (vh - cropH) / 2;
            canvasContext.drawImage(videoElem, sx, sy, cropW, cropH, 0, 0, canvas.width, canvas.height);
        } else {
            canvasContext.drawImage(videoElem, 0, 0, canvas.width, canvas.height);
        }
    }

    function playSuccessBeep() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.12);
            setTimeout(() => ctx.close().catch(() => {}), 250);
        } catch (_) {}
    }

    function updateQualityHudFromMetrics() {
        const parser = window.CccdQrParser;
        if (!parser || typeof parser.getLastFrameMetrics !== 'function' || typeof parser.formatFrameQualityGuidance !== 'function') {
            return;
        }
        const metrics = parser.getLastFrameMetrics();
        if (!metrics) return;
        const guidance = parser.formatFrameQualityGuidance(metrics);

        const scannerArea = document.getElementById('scanner-area');
        if (scannerArea) {
            scannerArea.style.borderColor = guidance.borderColor;
        }

        const hud = document.getElementById('scanner-quality-hud');
        if (hud) {
            hud.textContent = guidance.badgeText;
            hud.style.border = `1px solid ${guidance.borderColor}`;
        }

        const fpsBadge = document.getElementById('scanner-fps-badge');
        if (fpsBadge) {
            fpsBadge.textContent = `Độ nét: ${metrics.sharpness ?? 0}/100`;
        }
    }

    async function scanQRCode() {
        if (!isScanning || document.hidden) return;

        scanTimer = null;
        if (videoElem.readyState < videoElem.HAVE_ENOUGH_DATA) {
            scheduleNextScan();

            return;
        }

        const scanStartedAt = performance.now();

        if (!videoElem.videoWidth || !videoElem.videoHeight) {
            scheduleNextScan();

            return;
        }

        scanAttempts++;
        const parser = window.CccdQrParser;
        let qrData = '';

        if (parser && typeof parser.decodeQrFromVideoFrame === 'function') {
            const effectiveZoom = hwZoomRange ? 1 : Math.max(1, zoomLevel / 100);
            try {
                qrData = await parser.decodeQrFromVideoFrame(
                    videoElem,
                    canvas,
                    canvasContext,
                    scanAttempts,
                    effectiveZoom,
                    preprocessImage
                );
            } catch (_) {}
            configureAnalysisCanvas();
            updateQualityHudFromMetrics();
        } else {
            drawVideoToAnalysisCanvas();
            let imageData = canvasContext.getImageData(0, 0, canvas.width, canvas.height);
            imageData = preprocessImage(imageData);
            const inversionMethod = scanAttempts % 3 === 0 ? 'attemptBoth' : 'dontInvert';
            try {
                const code = typeof jsQR === 'function'
                    ? jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: inversionMethod })
                    : null;
                if (code && code.data) {
                    qrData = code.data.trim();
                }
            } catch (_) {}
        }

        if (!isScanning || document.hidden) return;

        if (qrData !== '') {
            if (qrData !== lastSuccessfulScan) {
                lastSuccessfulScan = qrData;
                playSuccessBeep();
                handleScannedQrData(qrData);

                if (!continuousScanCheck.checked) {
                    stopScan(true);
                    return;
                }

                scheduleNextScan(1500);
                return;
            }
        }

        scheduleNextScan(Math.max(80, TARGET_SCAN_PERIOD_MS - (performance.now() - scanStartedAt)));
    }

    function stopScan(keepResultText = false) {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        hwZoomRange = null;
        hwFocusRange = null;
        focusDiv.style.display = 'none';

        if (scanTimer) {
            clearTimeout(scanTimer);
            scanTimer = null;
        }

        const scannerArea = document.getElementById('scanner-area');
        if (scannerArea) {
            scannerArea.remove();
        }
        const qualityHud = document.getElementById('scanner-quality-hud');
        if (qualityHud) {
            qualityHud.remove();
        }
        const fpsBadge = document.getElementById('scanner-fps-badge');
        if (fpsBadge) {
            fpsBadge.textContent = 'Chế độ HD tối ưu';
        }

        isScanning = false;
        if (!keepResultText) {
            resultElem.textContent = '⏹️ Đã tắt camera quét.';
        }

        videoElem.style.transform = 'scale(1)';
        videoElem.style.filter = 'brightness(100%) contrast(100%)';
    }

    // Bóc tách CCCD, đối chiếu DB và hiển thị kết quả
    async function lookupCustomerInDb(identityNo, oldIdentityNo = '') {
        if (!scanConfig.customerSearchUrl || !identityNo) return null;
        const queries = [identityNo, oldIdentityNo].filter(q => q && q.length >= 6);

        for (const q of queries) {
            try {
                const url = `${scanConfig.customerSearchUrl}?query=${encodeURIComponent(q)}`;
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok) continue;
                const list = await response.json();
                if (!Array.isArray(list) || !list.length) continue;

                const exact = list.find(item => {
                    const cust = item?.customer;
                    return cust && (cust.identity_no === identityNo || (oldIdentityNo && cust.identity_no === oldIdentityNo));
                });
                return (exact || list[0])?.customer || null;
            } catch (err) {
                console.warn('Không thể tra cứu khách hàng tự động:', err);
            }
        }
        return null;
    }

    function renderCccdFields(displayData, dbCustomer = null) {
        const setField = (name, val) => {
            const el = document.querySelector(`[data-cccd-field="${name}"]`);
            if (el) el.textContent = val || '—';
        };
        const pick = (primary, fallback) => (primary && primary !== '—') ? primary : (fallback || '—');

        setField('identity_no', pick(displayData.identity_no, dbCustomer?.identity_no));
        setField('old_identity_no', displayData.old_identity_no);
        setField('nameloc', pick(displayData.nameloc, dbCustomer?.nameloc));
        setField('name', pick(displayData.name, dbCustomer?.name));
        setField('birthday', pick(displayData.birthday, dbCustomer?.birthday));
        setField('gender', pick(displayData.gender, dbCustomer?.gender));
        setField('identity_date', pick(displayData.identity_date, dbCustomer?.identity_date));
        setField('identity_outdate', pick(displayData.identity_outdate, dbCustomer?.identity_outdate));
        setField('addrfull', pick(displayData.addrfull, dbCustomer?.addrfull));

        if (dbCustomer && dbCustomer.custno) {
            setField('custno', dbCustomer.custno);
            const accounts = Array.isArray(dbCustomer.accounts) ? dbCustomer.accounts : [];
            const accText = accounts.length
                ? accounts.map(a => `${a.idxacno} (${a.ccycd || 'VND'})`).join(', ')
                : 'Chưa có TK';
            const phoneText = dbCustomer.phone_no ? `SĐT: ${dbCustomer.phone_no} · ` : '';
            setField('account_summary', `${phoneText}${accText}`);
        } else {
            setField('custno', 'Chưa có CIF');
            setField('account_summary', 'Khách hàng mới / vãng lai');
        }
    }

    async function handleScannedQrData(qrData) {
        const parser = window.CccdQrParser;
        const parsed = parser ? parser.parseCccdQr(qrData) : { isCccd: false, raw: qrData };
        const statusBanner = document.getElementById('cccd-db-status');
        const applyBtn = document.getElementById('apply-to-selected-form');
        const saveDraftBtn = document.getElementById('save-to-shared-draft');
        const copyBtn = document.getElementById('copy-cccd-btn');

        if (!parsed.isCccd) {
            currentParsedCccd = null;
            currentMatchedCustomer = null;
            resultElem.textContent = `✅ Đã quét mã QR: ${qrData}`;
            if (statusBanner) {
                statusBanner.className = 'cccd-db-banner is-new';
                statusBanner.textContent = '⚠️ Mã QR vừa quét không phải định dạng chuẩn CCCD gắn chip Việt Nam (7 trường phân cách bởi dấu |).';
            }
            if (copyBtn) copyBtn.disabled = false;
            return;
        }

        currentParsedCccd = parsed;
        currentMatchedCustomer = null;
        resultElem.textContent = `✅ Đã nhận diện CCCD: ${parsed.fields.nameloc} (${parsed.fields.identity_no})`;

        renderCccdFields(parsed.display, null);
        if (applyBtn) applyBtn.disabled = false;
        if (saveDraftBtn) saveDraftBtn.disabled = false;
        if (copyBtn) copyBtn.disabled = false;

        if (statusBanner) {
            statusBanner.className = 'cccd-db-banner';
            statusBanner.textContent = '🔄 Đang đối chiếu số CCCD với cơ sở dữ liệu khách hàng tại chi nhánh...';
        }

        const matchedCustomer = await lookupCustomerInDb(parsed.fields.identity_no, parsed.fields.old_identity_no);
        currentMatchedCustomer = matchedCustomer;
        renderCccdFields(parsed.display, matchedCustomer);

        if (statusBanner) {
            if (matchedCustomer && matchedCustomer.custno) {
                statusBanner.className = 'cccd-db-banner is-matched';
                statusBanner.textContent = `✅ Đã khớp hồ sơ khách hàng trong hệ thống (CIF: ${matchedCustomer.custno}). Khi chuyển sang biểu mẫu sẽ điền sẵn cả CIF, SĐT, Tài khoản và thông tin CCCD mới nhất.`;
            } else {
                statusBanner.className = 'cccd-db-banner is-new';
                statusBanner.textContent = 'ℹ️ Khách hàng chưa có mã CIF trong hệ thống chi nhánh. Toàn bộ thông tin nhân thân trên CCCD đã sẵn sàng để điền vào biểu mẫu.';
            }
        }
    }

    // Lưu dữ liệu CCCD (+ DB customer) vào Bản nháp dùng chung (localStorage + Server FormDraft)
    async function persistCccdToSharedDraft() {
        if (!currentParsedCccd || !window.CccdQrParser) return false;
        const payload = window.CccdQrParser.buildFormPayloadFromCccd(currentParsedCccd, currentMatchedCustomer);
        const userId = scanConfig.userId || 'guest';
        const storageKey = `draft:shared:user:${userId}`;

        let existingLocal = {};
        let existingRevision = null;
        try {
            const rawLocal = localStorage.getItem(storageKey);
            if (rawLocal) {
                const parsedLocal = JSON.parse(rawLocal);
                if (parsedLocal && typeof parsedLocal.data === 'object') {
                    existingLocal = parsedLocal.data;
                    existingRevision = parsedLocal.revision || null;
                }
            }
        } catch (_) {}

        const mergedPayload = { ...existingLocal, ...payload };

        // Đồng bộ lên Server FormDraft nếu có URL
        if (scanConfig.draftGetUrl && scanConfig.draftSaveUrl) {
            try {
                const getResp = await fetch(scanConfig.draftGetUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (getResp.ok) {
                    const serverState = await getResp.json();
                    const revision = serverState?.revision || 'missing';
                    const serverBase = (serverState?.payload && typeof serverState.payload === 'object') ? serverState.payload : {};
                    const finalPayload = { ...serverBase, ...mergedPayload };

                    const body = new URLSearchParams({
                        form_key: 'supportForm',
                        payload: JSON.stringify(finalPayload),
                        revision,
                        mode: 'merge',
                    });

                    const saveResp = await fetch(scanConfig.draftSaveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': scanConfig.csrfToken || '',
                        },
                        credentials: 'same-origin',
                        body: body.toString(),
                    });

                    if (saveResp.ok) {
                        const savedData = await saveResp.json();
                        localStorage.setItem(storageKey, JSON.stringify({
                            data: savedData.payload || finalPayload,
                            dirty: false,
                            revision: savedData.revision || revision,
                            mode: 'merge',
                            saved_at: new Date().toISOString(),
                        }));
                        return true;
                    }
                }
            } catch (err) {
                console.warn('Lưu nháp server không thành công, giữ nháp local:', err);
            }
        }

        // Fallback lưu vào localStorage để support-form-draft.js tự động nạp và đồng bộ
        try {
            localStorage.setItem(storageKey, JSON.stringify({
                data: mergedPayload,
                dirty: true,
                revision: existingRevision,
                mode: 'merge',
                saved_at: new Date().toISOString(),
            }));
            return true;
        } catch (_) {
            return false;
        }
    }

    // Quét từ ảnh tải lên (đa vùng & đa tỷ lệ cho ảnh chụp nguyên thẻ CCCD)
    const imageInput = document.getElementById('qr-image-input');
    if (imageInput) {
        imageInput.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (!file) return;
            resultElem.textContent = '🔄 Đang phân tích đa vùng ảnh QR...';

            const reader = new FileReader();
            reader.onload = () => {
                const img = new Image();
                img.onload = async () => {
                    const parser = window.CccdQrParser;
                    let qrText = '';
                    if (parser && typeof parser.decodeQrFromImageElement === 'function') {
                        try {
                            qrText = await parser.decodeQrFromImageElement(img, canvas, canvasContext);
                        } catch (_) {}
                    } else {
                        const scale = Math.min(1, 1400 / Math.max(img.width, img.height));
                        canvas.width = Math.max(1, Math.round(img.width * scale));
                        canvas.height = Math.max(1, Math.round(img.height * scale));
                        canvasContext.drawImage(img, 0, 0, canvas.width, canvas.height);
                        const imgData = canvasContext.getImageData(0, 0, canvas.width, canvas.height);
                        try {
                            const code = typeof jsQR === 'function'
                                ? jsQR(imgData.data, imgData.width, imgData.height, { inversionAttempts: 'attemptBoth' })
                                : null;
                            if (code && code.data) qrText = code.data.trim();
                        } catch (_) {}
                    }

                    if (qrText) {
                        playSuccessBeep();
                        handleScannedQrData(qrText);
                    } else {
                        resultElem.textContent = '⚠️ Không tìm thấy mã QR trong ảnh đã chọn. Hãy chụp cận cảnh góc mã QR trên thẻ CCCD rõ nét hơn.';
                    }
                    imageInput.value = '';
                };
                img.src = reader.result;
            };
            reader.readAsDataURL(file);
        });
    }

    // Nhập từ máy quét 2D cầm tay hoặc dán chuỗi QR
    const manualInput = document.getElementById('qr-manual-input');
    const manualBtn = document.getElementById('qr-manual-btn');
    function triggerManualParse() {
        const val = manualInput?.value?.trim();
        if (!val) {
            resultElem.textContent = '⚠️ Vui lòng dán chuỗi QR hoặc quét bằng đầu đọc 2D vào ô nhập.';
            return;
        }
        playSuccessBeep();
        handleScannedQrData(val);
    }
    if (manualBtn) {
        manualBtn.addEventListener('click', triggerManualParse);
    }
    if (manualInput) {
        manualInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                triggerManualParse();
            }
        });
    }

    // Nút điền ngay vào biểu mẫu đã chọn
    const applyBtn = document.getElementById('apply-to-selected-form');
    const targetFormSelect = document.getElementById('target-form-select');
    const bridgeStatus = document.getElementById('bridge-status');

    if (applyBtn) {
        applyBtn.addEventListener('click', async () => {
            const targetUrl = targetFormSelect?.value;
            if (!targetUrl) {
                if (bridgeStatus) {
                    bridgeStatus.className = 'small text-danger font-weight-bold mb-0 mt-2';
                    bridgeStatus.textContent = '⚠️ Vui lòng chọn một biểu mẫu giao dịch trong danh sách trước khi bấm Điền ngay.';
                }
                targetFormSelect?.focus();
                return;
            }
            applyBtn.disabled = true;
            if (bridgeStatus) {
                bridgeStatus.className = 'small text-primary font-weight-bold mb-0 mt-2';
                bridgeStatus.textContent = '🔄 Đang lưu dữ liệu khách hàng vào biểu mẫu và chuyển trang...';
            }
            await persistCccdToSharedDraft();
            window.location.href = targetUrl;
        });
    }

    // Nút lưu vào nháp dùng chung và mở Danh sách mẫu / Bộ hồ sơ
    const saveDraftBtn = document.getElementById('save-to-shared-draft');
    if (saveDraftBtn) {
        saveDraftBtn.addEventListener('click', async () => {
            saveDraftBtn.disabled = true;
            if (bridgeStatus) {
                bridgeStatus.className = 'small text-primary font-weight-bold mb-0 mt-2';
                bridgeStatus.textContent = '🔄 Đang đồng bộ vào bản nháp dùng chung...';
            }
            await persistCccdToSharedDraft();
            if (scanConfig.formsCatalogUrl) {
                window.location.href = scanConfig.formsCatalogUrl;
            } else {
                saveDraftBtn.disabled = false;
                if (bridgeStatus) {
                    bridgeStatus.className = 'small text-success font-weight-bold mb-0 mt-2';
                    bridgeStatus.textContent = '✅ Đã lưu vào bản nháp dùng chung! Mọi biểu mẫu bạn mở sẽ tự động điền thông tin khách hàng này.';
                }
            }
        });
    }

    // Nút sao chép thông tin CCCD
    const copyBtn = document.getElementById('copy-cccd-btn');
    if (copyBtn) {
        copyBtn.addEventListener('click', async () => {
            const text = currentParsedCccd
                ? [
                    `Số CCCD: ${currentParsedCccd.display.identity_no}`,
                    `CMND cũ: ${currentParsedCccd.display.old_identity_no}`,
                    `Họ và tên: ${currentParsedCccd.display.nameloc}`,
                    `Ngày sinh: ${currentParsedCccd.display.birthday}`,
                    `Giới tính: ${currentParsedCccd.display.gender}`,
                    `Ngày cấp: ${currentParsedCccd.display.identity_date}`,
                    `Địa chỉ: ${currentParsedCccd.display.addrfull}`,
                ].join('\n')
                : lastSuccessfulScan;

            if (!text) return;
            try {
                await navigator.clipboard.writeText(text);
                copyBtn.textContent = '✓ Đã chép';
                setTimeout(() => {
                    copyBtn.innerHTML = '<i class="far fa-copy mr-1"></i> Sao chép';
                }, 1800);
            } catch (_) {}
        });
    }

    await populateCameraOptions();

    startBtn.addEventListener('click', () => {
        const selectedId = selectElem.value;
        startScan(selectedId);
    });

    stopBtn.addEventListener('click', () => stopScan(false));

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            if (scanTimer) {
                clearTimeout(scanTimer);
                scanTimer = null;
            }
        } else if (isScanning) {
            scheduleNextScan(0);
        }
    });

    window.addEventListener('pagehide', stopScan);

    selectElem.addEventListener('change', async (e) => {
        const newDeviceId = e.target.value;
        if (isScanning) {
            stopScan(false);
            await startScan(newDeviceId);
        }
    });
});
