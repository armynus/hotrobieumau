document.addEventListener('DOMContentLoaded', async () => {
    const videoElem = document.getElementById('video');
    const resultElem = document.getElementById('result');
    const startBtn = document.getElementById('start-scan');
    const stopBtn = document.getElementById('stop-scan');
    const selectElem = document.getElementById('camera-select');
    
    // Tạo container cho video để kiểm soát overflow
    const videoContainer = document.createElement('div');
    videoContainer.id = 'video-container';
    videoContainer.style.width = '100%';
    videoContainer.style.height = '100%';
    videoContainer.style.position = 'relative';
    videoContainer.style.overflow = 'hidden';  // Ngăn tràn khi zoom
    
    // Bọc video trong container này
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
    zoomSlider.style.marginTop = '10px';

    const zoomValue = document.createElement('span');
    zoomValue.textContent = 'Thu phóng: 100%';
    zoomValue.id = 'zoom-value';

    const brightnessSlider = document.createElement('input');
    brightnessSlider.type = 'range';
    brightnessSlider.min = '0';
    brightnessSlider.max = '200';
    brightnessSlider.value = '100';
    brightnessSlider.step = '10';
    brightnessSlider.id = 'brightness-control';
    brightnessSlider.style.width = '100%';
    brightnessSlider.style.marginTop = '10px';

    const brightnessValue = document.createElement('span');
    brightnessValue.textContent = 'Độ sáng: 100%';
    brightnessValue.id = 'brightness-value';

    const contrastSlider = document.createElement('input');
    contrastSlider.type = 'range';
    contrastSlider.min = '0';
    contrastSlider.max = '200';
    contrastSlider.value = '100';
    contrastSlider.step = '10';
    contrastSlider.id = 'contrast-control';
    contrastSlider.style.width = '100%';
    contrastSlider.style.marginTop = '10px';

    const contrastValue = document.createElement('span');
    contrastValue.textContent = 'Độ tương phản: 100%';
    contrastValue.id = 'contrast-value';

    // Thêm checkbox cho chế độ quét liên tục
    const continuousScanDiv = document.createElement('div');
    continuousScanDiv.style.marginTop = '10px';
    
    const continuousScanCheck = document.createElement('input');
    continuousScanCheck.type = 'checkbox';
    continuousScanCheck.id = 'continuous-scan';
    continuousScanCheck.checked = true;
    
    const continuousScanLabel = document.createElement('label');
    continuousScanLabel.htmlFor = 'continuous-scan';
    continuousScanLabel.textContent = 'Quét liên tục';
    
    continuousScanDiv.appendChild(continuousScanCheck);
    continuousScanDiv.appendChild(continuousScanLabel);
    
    // Chèn các điều khiển vào DOM
    const controlsDiv = document.createElement('div');
    controlsDiv.id = 'advanced-controls';
    controlsDiv.style.padding = '10px';
    controlsDiv.style.backgroundColor = '#f5f5f5';
    controlsDiv.style.borderRadius = '5px';
    controlsDiv.style.marginTop = '10px';
    
    const controlsTitle = document.createElement('h4');
    controlsTitle.textContent = 'Tùy chỉnh nâng cao';
    controlsTitle.style.margin = '0 0 10px 0';
    
    controlsDiv.appendChild(controlsTitle);
    
    // Zoom controls
    const zoomDiv = document.createElement('div');
    zoomDiv.appendChild(zoomValue);
    zoomDiv.appendChild(zoomSlider);
    controlsDiv.appendChild(zoomDiv);
    
    // Brightness controls
    const brightnessDiv = document.createElement('div');
    brightnessDiv.appendChild(brightnessValue);
    brightnessDiv.appendChild(brightnessSlider);
    controlsDiv.appendChild(brightnessDiv);
    
    // Contrast controls
    const contrastDiv = document.createElement('div');
    contrastDiv.appendChild(contrastValue);
    contrastDiv.appendChild(contrastSlider);
    controlsDiv.appendChild(contrastDiv);
    
    // Add continuous scan option
    controlsDiv.appendChild(continuousScanDiv);
    
    // // Thêm nút chụp ảnh để phân tích từ frame đã chụp
    // const captureButton = document.createElement('button');
    // captureButton.textContent = '📸 Chụp và phân tích';
    // captureButton.id = 'capture-button';
    // captureButton.style.marginTop = '10px';
    // captureButton.style.padding = '8px';
    // captureButton.style.width = '100%';
    // controlsDiv.appendChild(captureButton);
    
    // Chèn vào sau các nút điều khiển
    // Tìm vị trí để chèn các điều khiển nâng cao
    const controlsContainer = document.querySelector('.controls') || 
                            document.querySelector('div:has(#start-scan)') || 
                            startBtn.parentElement;
    
    // Nếu vẫn không tìm thấy, thêm vào sau các nút
    if (controlsContainer) {
        controlsContainer.appendChild(controlsDiv);
    } else {
        // Tạo một container mới nếu không tìm thấy
        const newControlsContainer = document.createElement('div');
        newControlsContainer.className = 'controls-container';
        stopBtn.parentNode.insertBefore(controlsDiv, stopBtn.nextSibling);
    }
    
    let stream = null;
    let isScanning = false;
    let scanInterval = null;
    let scanAttempts = 0;
    let lastSuccessfulScan = '';
    let zoomLevel = 100;
    let scanResizeStep = 0; // Bước thay đổi kích thước khi quét
    
    // Canvas và context để xử lý hình ảnh
    const canvas = document.createElement('canvas');
    const canvasContext = canvas.getContext('2d', { willReadFrequently: true });
    
    // Zoom event handler
    zoomSlider.addEventListener('input', (e) => {
        zoomLevel = parseInt(e.target.value);
        zoomValue.textContent = `Thu phóng: ${zoomLevel}%`;
        updateScannerArea();
    });
    
    // Brightness event handler
    brightnessSlider.addEventListener('input', (e) => {
        const brightness = e.target.value;
        brightnessValue.textContent = `Độ sáng: ${brightness}%`;
        updateVideoStyles();
    });
    
    // Contrast event handler
    contrastSlider.addEventListener('input', (e) => {
        const contrast = e.target.value;
        contrastValue.textContent = `Độ tương phản: ${contrast}%`;
        updateVideoStyles();
    });
    
    // Update video styles based on sliders
    function updateVideoStyles() {
        const brightness = brightnessSlider.value;
        const contrast = contrastSlider.value;
        
        // Chỉ áp dụng brightness và contrast, không thay đổi scale
        videoElem.style.filter = `brightness(${brightness}%) contrast(${contrast}%)`;
    }
    
    // Update scanner area based on zoom
    function updateScannerArea() {
        const scannerArea = document.getElementById('scanner-area');
        if (scannerArea) {
            // Kích thước cơ bản của vùng quét - giữ nguyên
            const baseWidth = 50; // % width
            const baseHeight = 50; // % height
            
            // Định vị vùng quét vào giữa
            const top = (100 - baseHeight) / 2;
            const left = (100 - baseWidth) / 2;
            
            scannerArea.style.top = `${top}%`;
            scannerArea.style.left = `${left}%`;
            scannerArea.style.width = `${baseWidth}%`;
            scannerArea.style.height = `${baseHeight}%`;
            
            // Thay đổi tỷ lệ zoom bên trong vùng quét
            const zoomScale = zoomLevel / 100;
            
            // Điều chỉnh vị trí và scale của video để tạo hiệu ứng zoom vào vùng giữa
            // Tính toán kích thước và vị trí dựa trên mức zoom
            const offsetX = (zoomScale - 1) * (left + baseWidth/2) * -1;
            const offsetY = (zoomScale - 1) * (top + baseHeight/2) * -1;
            
            videoElem.style.transform = `scale(${zoomScale})`;
            videoElem.style.transformOrigin = 'center center';
            videoElem.style.position = 'absolute';
            videoElem.style.left = `${offsetX}%`;
            videoElem.style.top = `${offsetY}%`;
        }
    }
    
    // Populate camera dropdown với phân giải cao nhất
    async function populateCameraOptions() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(device => device.kind === 'videoinput');
            
            selectElem.innerHTML = '';
            videoDevices.forEach((device, index) => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.text = device.label || `Camera ${index + 1}`;
                selectElem.appendChild(option);
            });
            
            // Nếu có camera sau, chọn nó làm mặc định
            const backCamera = videoDevices.find(device => 
                device.label.toLowerCase().includes('back') || 
                device.label.toLowerCase().includes('sau') ||
                device.label.toLowerCase().includes('rear'));
                
            if (backCamera) {
                selectElem.value = backCamera.deviceId;
            }
        } catch (err) {
            console.error("Không thể liệt kê camera:", err);
            resultElem.textContent = "⚠️ Không tìm thấy camera nào!";
        }
    }
    
    // Xử lý ảnh để tăng khả năng nhận diện QR
    function preprocessImage(imageData) {
        // Tạo bản sao của dữ liệu ảnh để xử lý
        const data = new Uint8ClampedArray(imageData.data);
        const width = imageData.width;
        const height = imageData.height;
        
        // Áp dụng độ sáng và tương phản từ slider
        const brightness = parseInt(brightnessSlider.value) / 100;
        const contrast = parseInt(contrastSlider.value) / 100;
        
        for (let i = 0; i < data.length; i += 4) {
            // Áp dụng độ sáng và tương phản cho mỗi pixel
            for (let j = 0; j < 3; j++) {
                let value = data[i + j];
                
                // Áp dụng độ sáng
                value = value * brightness;
                
                // Áp dụng độ tương phản (xung quanh 128)
                value = 128 + (value - 128) * contrast;
                
                // Đảm bảo giá trị nằm trong khoảng 0-255
                data[i + j] = Math.min(255, Math.max(0, value));
            }
        }
        
        return new ImageData(data, width, height);
    }
    
    // // Chụp frame hiện tại để phân tích kỹ
    // captureButton.addEventListener('click', () => {
    //     if (!stream || !videoElem.srcObject) {
    //         resultElem.textContent = "⚠️ Hãy bắt đầu quét trước!";
    //         return;
    //     }
        
    //     // Hiển thị thông báo đang phân tích
    //     resultElem.textContent = "🔍 Đang phân tích kỹ frame...";
        
    //     // Mảng các cài đặt phân tích khác nhau để thử
    //     const analysisSettings = [
    //         { inversionAttempts: "dontInvert", brightness: 100, contrast: 100 },
    //         { inversionAttempts: "onlyInvert", brightness: 110, contrast: 120 },
    //         { inversionAttempts: "bothInverted", brightness: 90, contrast: 130 },
    //         { inversionAttempts: "dontInvert", brightness: 120, contrast: 140 },
    //         { inversionAttempts: "onlyInvert", brightness: 80, contrast: 150 }
    //     ];
        
    //     // Chụp frame hiện tại
    //     canvas.width = videoElem.videoWidth;
    //     canvas.height = videoElem.videoHeight;
    //     canvasContext.drawImage(videoElem, 0, 0, canvas.width, canvas.height);
    //     const originalImageData = canvasContext.getImageData(0, 0, canvas.width, canvas.height);
        
    //     // Thực hiện phân tích với nhiều cài đặt khác nhau
    //     let foundCode = null;
        
    //     for (const setting of analysisSettings) {
    //         if (foundCode) break;
            
    //         // Lưu cài đặt hiện tại
    //         const currentBrightness = brightnessSlider.value;
    //         const currentContrast = contrastSlider.value;
            
    //         // Áp dụng cài đặt mới tạm thời
    //         brightnessSlider.value = setting.brightness;
    //         contrastSlider.value = setting.contrast;
            
    //         // Xử lý ảnh với cài đặt mới
    //         const processedData = preprocessImage(originalImageData);
            
    //         // Thử phát hiện QR code
    //         foundCode = jsQR(
    //             processedData.data,
    //             processedData.width,
    //             processedData.height,
    //             { inversionAttempts: setting.inversionAttempts }
    //         );
            
    //         // Khôi phục cài đặt ban đầu
    //         brightnessSlider.value = currentBrightness;
    //         contrastSlider.value = currentContrast;
    //         updateVideoStyles();
    //     }
        
    //     if (foundCode) {
    //         resultElem.textContent = `✅ Đã phát hiện mã QR: ${foundCode.data}`;
    //         highlightQRCode(foundCode);
    //     } else {
    //         resultElem.textContent = "❌ Không tìm thấy mã QR trong frame này. Hãy điều chỉnh camera và thử lại!";
    //     }
    // });
    
    // Highlight QR code phát hiện được
    function highlightQRCode(code) {
        if (!code) return;
        
        // Vẽ viền xung quanh QR code
        canvasContext.beginPath();
        canvasContext.moveTo(code.location.topLeftCorner.x, code.location.topLeftCorner.y);
        canvasContext.lineTo(code.location.topRightCorner.x, code.location.topRightCorner.y);
        canvasContext.lineTo(code.location.bottomRightCorner.x, code.location.bottomRightCorner.y);
        canvasContext.lineTo(code.location.bottomLeftCorner.x, code.location.bottomLeftCorner.y);
        canvasContext.lineTo(code.location.topLeftCorner.x, code.location.topLeftCorner.y);
        canvasContext.lineWidth = 4;
        canvasContext.strokeStyle = "#04CA77";
        canvasContext.stroke();
        
        // Thêm hiệu ứng blink để thu hút sự chú ý
        let blinkCount = 0;
        const blinkInterval = setInterval(() => {
            canvasContext.strokeStyle = blinkCount % 2 === 0 ? "#04CA77" : "#FF3B30";
            canvasContext.stroke();
            blinkCount++;
            
            if (blinkCount > 6) {
                clearInterval(blinkInterval);
            }
        }, 300);
    }
    
    // Start scanning với nhiều tối ưu hóa
    async function startScan(deviceId) {
        try {
            if (stream) {
                stopScan();
            }
            
            resultElem.textContent = "🔄 Đang khởi tạo camera...";
            
            // Sử dụng độ phân giải và frameRate cao nhất có thể
            const constraints = {
                video: {
                    deviceId: deviceId ? { exact: deviceId } : undefined,
                    width: { ideal: 1920 },  // Full HD
                    height: { ideal: 1080 },
                    frameRate: { ideal: 30, min: 15 }, // Tối thiểu 15fps để đảm bảo trải nghiệm tốt
                    facingMode: "environment", // Ưu tiên camera sau
                    // Những điện thoại mới hỗ trợ zoom quang học
                    advanced: [
                        { zoom: 1.0 } // Mức zoom quang học nếu được hỗ trợ
                    ]
                }
            };
            
            stream = await navigator.mediaDevices.getUserMedia(constraints);
            videoElem.srcObject = stream;
            
            // Đợi video load hoàn toàn
            await new Promise(resolve => {
                videoElem.onloadedmetadata = () => {
                    videoElem.play(); // Bắt đầu phát video
                    resolve();
                };
            });
            
            // Đợi thêm để đảm bảo kích thước video đã sẵn sàng
            await new Promise(resolve => {
                setTimeout(() => {
                    // Đảm bảo kích thước video hợp lệ trước khi sử dụng
                    if (videoElem.videoWidth && videoElem.videoHeight) {
                        canvas.width = videoElem.videoWidth;
                        canvas.height = videoElem.videoHeight;
                    } else {
                        // Sử dụng kích thước mặc định nếu không lấy được kích thước video
                        canvas.width = 640;
                        canvas.height = 480;
                        console.log('Không thể lấy kích thước video, sử dụng kích thước mặc định');
                    }
                    resolve();
                }, 500);
            });
            
            // Reset các biến kiểm soát
            scanAttempts = 0;
            scanResizeStep = 0;
            lastSuccessfulScan = '';
            
            resultElem.textContent = "📡 Đang quét...";
            isScanning = true;
            
            // Reset video styles
            videoElem.style.transform = 'scale(1)';
            videoElem.style.left = '0';
            videoElem.style.top = '0';
            videoElem.style.filter = 'brightness(100%) contrast(100%)';
            
            // Tạo wrapper cho video nếu chưa có
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
            
            // Tạo vùng quét
            let scannerArea = document.getElementById('scanner-area');
            if (!scannerArea) {
                scannerArea = document.createElement('div');
                scannerArea.id = 'scanner-area';
                scannerArea.style.position = 'absolute';
                scannerArea.style.border = '3px solid #04CA77';
                scannerArea.style.borderRadius = '10px';
                scannerArea.style.boxShadow = '0 0 0 5000px rgba(0, 0, 0, 0.3)';
                scannerArea.style.zIndex = '2'; // Đảm bảo vùng quét hiển thị trên video
                videoWrapper.appendChild(scannerArea);
                
                // Thêm hiệu ứng quét animation
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
                
                // Thêm style animation cho scan line
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes scan-line {
                        0% { top: 0; }
                        50% { top: calc(100% - 2px); }
                        100% { top: 0; }
                    }
                `;
                document.head.appendChild(style);
            }
            
            // Cập nhật kích thước vùng quét
            updateScannerArea();
            
            // Quét liên tục với các cài đặt tối ưu
            scanQRCode();
            
        } catch (err) {
            console.error("❌ Không thể bật camera:", err);
            resultElem.textContent = "🚫 Không thể mở camera! " + err.message;
        }
    }
    
    // Quét QR code với thuật toán thích ứng
    function scanQRCode() {
        if (!isScanning) return;
        
        if (scanInterval) {
            clearInterval(scanInterval);
        }
        
        scanInterval = setInterval(() => {
            if (!isScanning || !videoElem.readyState === videoElem.HAVE_ENOUGH_DATA) return;
            
            // Kiểm tra video đã sẵn sàng và có kích thước hợp lệ
            if (videoElem.readyState === videoElem.HAVE_ENOUGH_DATA && 
                videoElem.videoWidth && videoElem.videoHeight) {
                // Vẽ frame hiện tại vào canvas
                canvasContext.drawImage(videoElem, 0, 0, canvas.width, canvas.height);
            } else {
                // Bỏ qua frame này nếu video chưa sẵn sàng
                return;
            }

            // Lấy dữ liệu ảnh từ canvas
            let imageData = canvasContext.getImageData(0, 0, canvas.width, canvas.height);
            
            // Tiền xử lý ảnh để tăng khả năng nhận diện
            imageData = preprocessImage(imageData);
            
            // Thuật toán quét thích ứng
            scanAttempts++;
            
            // Lựa chọn phương pháp quét dựa trên số lần thử
            let inversionMethod = "dontInvert"; // Mặc định, nhanh nhất
            
            // Mỗi 10 lần quét, thay đổi phương pháp
            if (scanAttempts % 30 === 10) {
                inversionMethod = "onlyInvert";
            } else if (scanAttempts % 30 === 20) {
                inversionMethod = "bothInverted"; // Kỹ lưỡng nhất nhưng chậm nhất
            }
            
            // Cố gắng tìm QR code
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: inversionMethod
            });
            
            if (code) {
                const qrData = code.data;
                console.log("✅ QR Code tìm thấy:", qrData);
                
                // Tránh quét liên tục cùng một mã
                if (qrData !== lastSuccessfulScan) {
                    lastSuccessfulScan = qrData;
                    
                    // Hiển thị kết quả
                    resultElem.textContent = `✅ Mã QR: ${qrData}`;
                    
                    // Highlight QR code
                    highlightQRCode(code);
                    
                    // Phát âm thanh thông báo thành công (tùy chọn)
                    const successSound = new Audio('data:audio/mp3;base64,SUQzBAAAAAAAI1RTU0UAAAAPAAADTGF2ZjU4Ljc2LjEwMAAAAAAAAAAAAAAA/+M4wAAAAAAAAAAAAEluZm8AAAAPAAAAAwAABPAAfX19fX19fX19fX19fX19fX19fX19fX19fX19fX19fX2ZmZmZmZmZmZmZmZmZmZmZmZmZmZmZmZmZmZmZmZmZmZmZ/////////////////////////////////8AAAAExTRAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/4zAAAAAAAAAAAAAAAAAAAAAAAFhpbmcAAAAPAAAA5AAyOQMAAgIJDRERFRYaGh0dISUlKCksLDAwMzc3OkBARkdISExSUlZcXGJiZWVpbW1wcHR6en1+goKFiYmNkZGUmJidoaGkqKissbG0t7e6vr7BxcXJzMzP09PX2trb39/i5eXp7Ozv8/P29/f7//8AAAAATGF2YzU4LjEzAAAAAAAAAAAAAAAAJAqOqwAAAAAAAAAAAAAAAAAAAAAAQMI4yEwAAAAAAAAQYgAAAAAAAADA+AZgAAAA/+NIxAAAAANIAAAAAExBTUUzLjEwMFVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVV/+MoxDsAAANIAAAAAFVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVV/+MoxMQAAANIAAAAAFVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVV');
                    successSound.play();
                    
                    // Nếu không quét liên tục, tắt quét
                    if (!continuousScanCheck.checked) {
                        stopScan();
                        return;
                    }
                    
                    // Tạm dừng quét để tránh lặp lại
                    clearInterval(scanInterval);
                    setTimeout(() => {
                        if (isScanning) {
                            scanQRCode();
                        }
                    }, 1500);
                    
                    return;
                }
            }
            
            // Nếu không tìm thấy QR code sau một số lần thử, thay đổi cách xử lý ảnh
            if (scanAttempts % 100 === 0) {
                scanResizeStep = (scanResizeStep + 1) % 5;
                    console.log("Đang thay đổi phương pháp quét...", scanResizeStep);
                
                // Thay đổi độ sáng và độ tương phản theo chu kỳ
                switch (scanResizeStep) {
                    case 0:
                        brightnessSlider.value = 100;
                        contrastSlider.value = 100;
                        break;
                    case 1:
                        brightnessSlider.value = 120;
                        contrastSlider.value = 120;
                        break;
                    case 2:
                        brightnessSlider.value = 80;
                        contrastSlider.value = 140;
                        break;
                    case 3:
                        brightnessSlider.value = 110;
                        contrastSlider.value = 90;
                        break;
                    case 4:
                        brightnessSlider.value = 90;
                        contrastSlider.value = 110;
                        break;
                }
                
                updateVideoStyles();
            }
            
        }, 50); // Quét mỗi 50ms
    }
    
    // Stop scanning
    function stopScan() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        
        if (scanInterval) {
            clearInterval(scanInterval);
            scanInterval = null;
        }
        
        // Xóa scanner area nếu có
        const scannerArea = document.getElementById('scanner-area');
        if (scannerArea) {
            scannerArea.remove();
        }
        
        isScanning = false;
        resultElem.textContent = "⏹️ Đã tắt quét.";
        
        // Reset video style
        videoElem.style.transform = 'scale(1)';
        videoElem.style.filter = 'brightness(100%) contrast(100%)';
    }
    
    // Khởi tạo
    await populateCameraOptions();
    
    startBtn.addEventListener('click', () => {
        const selectedId = selectElem.value;
        startScan(selectedId);
    });
    
    stopBtn.addEventListener('click', stopScan);
    
    selectElem.addEventListener('change', async (e) => {
        const newDeviceId = e.target.value;
        if (isScanning) {
            stopScan();
            await startScan(newDeviceId);
        }
    });
});