/**
 * Client điều phối Web Worker giải mã QR bằng ZXing-C++ (zxing-wasm).
 *
 * Quy ước kết quả của decodeWithZxing():
 *   - chuỗi khác rỗng : đọc được mã QR
 *   - ''              : ZXing đã chạy nhưng không thấy mã (hoặc bỏ qua khung vì Worker đang bận)
 *   - null            : ZXing không dùng được (chưa sẵn sàng / lỗi) → phía gọi tự chuyển sang jsQR dự phòng
 */

// Đổi chuỗi này khi sửa qr-decode-worker.js để phá cache trình duyệt.
const WORKER_VERSION = '20261104-v3';
const INIT_TIMEOUT_MS = 15000;
const MAX_CONSECUTIVE_TIMEOUTS = 2;

/** Tùy chọn ZXing cho luồng camera (vùng khung xanh ở độ phân giải gốc). */
export const ZXING_VIDEO_OPTIONS = Object.freeze({
    formats: ['QRCode'],
    tryHarder: true,
    tryRotate: true,
    tryInvert: true,
    tryDownscale: true,
    tryDenoise: true,
    downscaleThreshold: 280,
    downscaleFactor: 2,
    maxNumberOfSymbols: 1,
    characterSet: 'UTF8',
    textMode: 'Plain',
});

/** Tùy chọn ZXing cho ảnh tải lên (ảnh tĩnh, ưu tiên độ nhạy hơn tốc độ). */
export const ZXING_IMAGE_OPTIONS = Object.freeze({
    ...ZXING_VIDEO_OPTIONS,
    tryDenoise: true,
});

function defaultWorkerFactory() {
    if (typeof Worker === 'undefined') {
        throw new Error('Trình duyệt không hỗ trợ Web Worker');
    }
    return new Worker(new URL(`./qr-decode-worker.js?v=${WORKER_VERSION}`, import.meta.url));
}

let workerFactory = defaultWorkerFactory;
let worker = null;
let state = 'idle'; // idle | loading | ready | failed
let readyPromise = null;
let resolveReady = null;
let initTimer = null;
let nextId = 1;
let consecutiveTimeouts = 0;
let lastError = '';
let lastFrameMetrics = null;
const pending = new Map();

function settleReady(ok) {
    if (initTimer) {
        clearTimeout(initTimer);
        initTimer = null;
    }
    if (resolveReady) {
        const fn = resolveReady;
        resolveReady = null;
        fn(ok);
    }
}

function markFailed(reason) {
    lastError = String(reason || 'unknown');
    state = 'failed';
    settleReady(false);
    for (const entry of pending.values()) {
        clearTimeout(entry.timer);
        entry.resolve(null);
    }
    pending.clear();
    if (worker) {
        try { worker.terminate(); } catch (_) {}
        worker = null;
    }
    if (typeof console !== 'undefined') {
        console.warn('[QR] ZXing WASM không khả dụng, chuyển sang jsQR dự phòng:', lastError);
    }
}

function handleMessage(event) {
    const msg = event && event.data ? event.data : {};
    if (msg.type === 'ready') {
        state = 'ready';
        settleReady(true);
        return;
    }
    if (msg.type === 'init-error') {
        markFailed(msg.error);
        return;
    }
    if (msg.type === 'result') {
        const entry = pending.get(msg.id);
        if (!entry) return;
        pending.delete(msg.id);
        clearTimeout(entry.timer);
        consecutiveTimeouts = 0;
        if (msg.metrics && typeof msg.metrics === 'object') {
            lastFrameMetrics = msg.metrics;
        }
        entry.resolve(typeof msg.text === 'string' ? msg.text : '');
    }
}

function ensureWorker() {
    if (state === 'failed') return Promise.resolve(false);
    if (readyPromise) return readyPromise;

    state = 'loading';
    readyPromise = new Promise((resolve) => { resolveReady = resolve; });
    try {
        worker = workerFactory();
        worker.onmessage = handleMessage;
        worker.onerror = (e) => {
            if (e && typeof e.preventDefault === 'function') e.preventDefault();
            markFailed((e && e.message) || 'Worker error');
        };
        initTimer = setTimeout(() => {
            if (state === 'loading') markFailed('Hết thời gian khởi tạo ZXing WASM');
        }, INIT_TIMEOUT_MS);
    } catch (err) {
        markFailed((err && err.message) || err);
    }
    return readyPromise;
}

/** Khởi tạo sớm Worker + WASM (gọi khi người dùng bấm Bắt đầu quét / mở modal camera). */
export function warmUpZxing() {
    return ensureWorker();
}

/** false sau khi Worker/WASM khởi tạo lỗi – khi đó luôn dùng jsQR. */
export function isZxingAvailable() {
    return state !== 'failed';
}

/** Trạng thái để hiển thị: idle | loading | ready | failed. */
export function getZxingStatus() {
    return state;
}

export function getZxingLastError() {
    return lastError;
}

/** Chỉ số chất lượng khung hình mới nhất ({ sharpness, glareRatio, contrastSpread, avgLum, motionDiff, hint }). */
export function getLastFrameMetrics() {
    return lastFrameMetrics;
}

export function setLastFrameMetrics(metrics) {
    lastFrameMetrics = metrics && typeof metrics === 'object' ? metrics : null;
}

function toTransferable(imageData) {
    const data = imageData.data;
    // Buffer phải khớp đúng dữ liệu ảnh mới chuyển quyền sở hữu được, nếu không thì sao chép.
    if (data.byteOffset === 0 && data.byteLength === data.buffer.byteLength) {
        return data.buffer;
    }
    return data.slice().buffer;
}

/**
 * Giải mã một ImageData bằng ZXing trong Worker.
 * Lưu ý: buffer của imageData được CHUYỂN sang Worker (không sao chép) nên không dùng lại được sau lời gọi.
 */
export async function decodeWithZxing(
    imageData,
    { dropIfBusy = true, timeoutMs = 1500, options = ZXING_VIDEO_OPTIONS, deblurMode = 'video', presetIndex = 0 } = {}
) {
    if (!imageData || !imageData.data || !imageData.width || !imageData.height) return '';
    if (state === 'failed') return null;

    const readyWait = ensureWorker();
    if (state !== 'ready') {
        // Luồng camera không chờ: trong lúc WASM đang tải thì để jsQR xử lý khung này.
        if (dropIfBusy) return null;
        const ok = await readyWait;
        if (!ok) return null;
    }

    if (dropIfBusy && pending.size > 0) return '';

    const id = nextId++;
    return new Promise((resolve) => {
        const timer = setTimeout(() => {
            if (!pending.has(id)) return;
            pending.delete(id);
            consecutiveTimeouts++;
            if (consecutiveTimeouts >= MAX_CONSECUTIVE_TIMEOUTS) {
                markFailed('ZXing phản hồi quá chậm');
                resolve(null);
                return;
            }
            resolve('');
        }, timeoutMs);

        pending.set(id, { resolve, timer });
        try {
            const buffer = toTransferable(imageData);
            worker.postMessage(
                {
                    type: 'decode',
                    id,
                    width: imageData.width,
                    height: imageData.height,
                    buffer,
                    options: { ...options },
                    deblurMode,
                    presetIndex,
                },
                [buffer]
            );
        } catch (err) {
            pending.delete(id);
            clearTimeout(timer);
            markFailed((err && err.message) || err);
            resolve(null);
        }
    });
}

export function terminateZxing() {
    if (worker) {
        try { worker.terminate(); } catch (_) {}
    }
    worker = null;
    for (const entry of pending.values()) {
        clearTimeout(entry.timer);
        entry.resolve(null);
    }
    pending.clear();
    settleReady(false);
    readyPromise = null;
    state = 'idle';
    consecutiveTimeouts = 0;
    lastFrameMetrics = null;
}

/** Chỉ dùng cho kiểm thử: thay hàm tạo Worker và đặt lại toàn bộ trạng thái. */
export function __setWorkerFactoryForTests(factory) {
    terminateZxing();
    lastError = '';
    workerFactory = typeof factory === 'function' ? factory : defaultWorkerFactory;
}
