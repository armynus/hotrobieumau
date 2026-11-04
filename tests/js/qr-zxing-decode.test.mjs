import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';
import { writeBarcode, readBarcodes } from 'zxing-wasm/full';
import {
    computeScanRegion,
    SCAN_REGION_RATIO,
    TIGHT_REGION_RATIO,
    parseCccdQr,
    safeJsQr,
    deblurQrImageData,
    analyzeQrFrameQuality,
    formatFrameQualityGuidance,
    getLastFrameMetrics,
    decodeQrFromVideoFrame,
    warmUpZxing as parserWarmUpZxing,
    getZxingStatus as parserGetZxingStatus,
} from '../../public/js/user/cccd-qr-parser.js';
import {
    ZXING_VIDEO_OPTIONS,
    __setWorkerFactoryForTests,
    decodeWithZxing,
    getZxingStatus,
    isZxingAvailable,
    warmUpZxing,
} from '../../public/js/user/qr-worker-client.js';

if (typeof globalThis.ImageData === 'undefined') {
    globalThis.ImageData = class ImageData {
        constructor(data, width, height) {
            this.data = data;
            this.width = width;
            this.height = height;
        }
    };
}

const jsQrCode = readFileSync(new URL('../../public/js/jsQR.min.js', import.meta.url), 'utf8');
const sandbox = { Uint8ClampedArray, Array, Math, Object, Error, decodeURIComponent };
sandbox.self = sandbox;
vm.runInNewContext(jsQrCode, sandbox);
const jsQR = sandbox.jsQR;

/** Chuyển symbol ma trận từ writeBarcode sang ImageData RGBA (0 trong symbol = điểm đen). */
function symbolToImageData(symbol, scale = 4, quietZone = 4) {
    const sw = symbol.width;
    const sh = symbol.height;
    const width = (sw + quietZone * 2) * scale;
    const height = (sh + quietZone * 2) * scale;
    const data = new Uint8ClampedArray(width * height * 4).fill(255);

    for (let y = 0; y < sh; y++) {
        for (let x = 0; x < sw; x++) {
            const isBlack = symbol.data[y * sw + x] === 0;
            if (!isBlack) continue;
            for (let dy = 0; dy < scale; dy++) {
                for (let dx = 0; dx < scale; dx++) {
                    const px = (x + quietZone) * scale + dx;
                    const py = (y + quietZone) * scale + dy;
                    const idx = (py * width + px) * 4;
                    data[idx] = 0;
                    data[idx + 1] = 0;
                    data[idx + 2] = 0;
                }
            }
        }
    }
    return new ImageData(data, width, height);
}

/** Làm mờ hộp (box blur) để mô phỏng webcam lấy nét cố định bị nhòe khi đưa thẻ CCCD vào gần. */
function boxBlur(img, radius) {
    const { width: w, height: h, data: src } = img;
    const out = new Uint8ClampedArray(src.length);
    const side = radius * 2 + 1;
    const area = side * side;

    for (let y = 0; y < h; y++) {
        for (let x = 0; x < w; x++) {
            let sum = 0;
            for (let dy = -radius; dy <= radius; dy++) {
                const yy = Math.min(h - 1, Math.max(0, y + dy));
                for (let dx = -radius; dx <= radius; dx++) {
                    const xx = Math.min(w - 1, Math.max(0, x + dx));
                    sum += src[(yy * w + xx) * 4];
                }
            }
            const v = Math.round(sum / area);
            const idx = (y * w + x) * 4;
            out[idx] = v;
            out[idx + 1] = v;
            out[idx + 2] = v;
            out[idx + 3] = 255;
        }
    }
    return new ImageData(out, w, h);
}

test('computeScanRegion crops the 60% green frame at native resolution without downscaling', () => {
    assert.equal(typeof parserWarmUpZxing, 'function');
    assert.equal(typeof parserGetZxingStatus, 'function');
    assert.equal(SCAN_REGION_RATIO, 0.6);

    // 1080p ở zoom 1x: vùng cắt giữ nguyên 1152x648 (trước đây bị thu về ≤640px)
    const r1 = computeScanRegion(1920, 1080, 1);
    assert.deepEqual(r1, { sx: 384, sy: 216, sw: 1152, sh: 648 });

    // 1080p ở zoom 2x: cắt vùng trung tâm 576x324
    const r2 = computeScanRegion(1920, 1080, 2);
    assert.deepEqual(r2, { sx: 672, sy: 378, sw: 576, sh: 324 });
});

test('zxing-wasm decodes 7-field Vietnamese CCCD QR with full UTF-8 diacritics', async () => {
    const raw = '087095001234|341789123|Nguyễn Đỗ Tuấn Kiệt|15021995|Nam|Ấp 1, Xã Mỹ Tân, TP Cao Lãnh, Đồng Tháp|10052021';
    const written = await writeBarcode(raw, { format: 'QRCode', ecLevel: 'M' });
    assert.equal(written.error, '');

    const img = symbolToImageData(written.symbol, 4, 4);
    const results = await readBarcodes(img, ZXING_VIDEO_OPTIONS);
    assert.equal(results.length, 1);
    assert.equal(results[0].isValid, true);
    assert.equal(results[0].text, raw);

    const parsed = parseCccdQr(results[0].text);
    assert.equal(parsed.isCccd, true);
    assert.equal(parsed.fields.nameloc, 'Nguyễn Đỗ Tuấn Kiệt');
    assert.equal(parsed.fields.addrfull, 'Ấp 1, Xã Mỹ Tân, TP Cao Lãnh, Đồng Tháp');
});

test('zxing-wasm is at least as resilient as jsQR across blur and small-module scales', async () => {
    const raw = '079092004567|025678912|Trần Thị Bích Ngọc|22111992|Nữ|Số 12/4 Nguyễn Thị Minh Khai, Phường Đa Kao, Quận 1, TP Hồ Chí Minh|18092022';
    const written = await writeBarcode(raw, { format: 'QRCode', ecLevel: 'M' });

    let zxingHits = 0;
    let jsQrHits = 0;

    // Quét qua các kích thước module nhỏ (2..4 px) và mức làm mờ (0..2)
    for (const scale of [2, 3, 4]) {
        const base = symbolToImageData(written.symbol, scale, 4);
        for (const radius of [0, 1, 2]) {
            const sample = radius === 0 ? base : boxBlur(base, radius);
            const zRes = await readBarcodes(sample, ZXING_VIDEO_OPTIONS);
            if (zRes.some((r) => r.isValid && r.text === raw)) zxingHits++;
            if (safeJsQr(sample, 'attemptBoth', jsQR) === raw) jsQrHits++;
        }
    }

    assert.ok(zxingHits >= jsQrHits && zxingHits > 0, `expected ZXing (${zxingHits}) >= jsQR (${jsQrHits})`);
});

test('qr-worker-client drops frames while busy and falls back to null on worker failure', async () => {
    // 1. Worker báo ready nhưng xử lý chậm → lượt gọi thứ 2 với dropIfBusy phải trả '' ngay
    let pendingMsg = null;
    let workerSelf = null;
    __setWorkerFactoryForTests(() => {
        workerSelf = {
            onmessage: null,
            onerror: null,
            postMessage(msg) {
                pendingMsg = msg;
            },
            terminate() {},
        };
        queueMicrotask(() => workerSelf.onmessage?.({ data: { type: 'ready' } }));
        return workerSelf;
    });

    assert.equal(await warmUpZxing(), true);
    assert.equal(getZxingStatus(), 'ready');

    const dummy = new ImageData(new Uint8ClampedArray(16), 2, 2);
    const firstPromise = decodeWithZxing(dummy);
    const dropped = await decodeWithZxing(new ImageData(new Uint8ClampedArray(16), 2, 2), { dropIfBusy: true });
    assert.equal(dropped, '');

    workerSelf.onmessage({
        data: {
            type: 'result',
            id: pendingMsg.id,
            text: 'CCCD_OK',
            metrics: { sharpness: 48, glareRatio: 0.02, contrastSpread: 180, avgLum: 135, motionDiff: 1.5, hint: 'good' },
        },
    });
    assert.equal(await firstPromise, 'CCCD_OK');
    assert.equal(getLastFrameMetrics()?.sharpness, 48);
    assert.equal(getLastFrameMetrics()?.hint, 'good');

    // 2. Worker khởi tạo lỗi → isZxingAvailable() = false, decodeWithZxing trả null để chuyển sang jsQR
    __setWorkerFactoryForTests(() => {
        const w = { onmessage: null, onerror: null, postMessage() {}, terminate() {} };
        queueMicrotask(() => w.onmessage?.({ data: { type: 'init-error', error: 'WASM blocked' } }));
        return w;
    });

    assert.equal(await warmUpZxing(), false);
    assert.equal(isZxingAvailable(), false);
    assert.equal(await decodeWithZxing(new ImageData(new Uint8ClampedArray(16), 2, 2)), null);
});

test('decodeQrFromVideoFrame falls back to jsQR when ZXing worker is unavailable', async () => {
    __setWorkerFactoryForTests(() => {
        throw new Error('No Worker in environment');
    });

    const raw = '087095001234|341789123|Nguyễn Đỗ Tuấn Kiệt|15021995|Nam|Ấp 1, Xã Mỹ Tân, TP Cao Lãnh, Đồng Tháp|10052021';
    const written = await writeBarcode(raw, { format: 'QRCode', ecLevel: 'M' });
    const qrImg = symbolToImageData(written.symbol, 5, 6);

    const prevWindow = globalThis.window;
    globalThis.window = { jsQR };
    try {
        const fakeVideo = { videoWidth: qrImg.width, videoHeight: qrImg.height };
        const fakeCanvas = { width: 0, height: 0 };
        const fakeCtx = {
            drawImage() {},
            getImageData: () => new ImageData(new Uint8ClampedArray(qrImg.data), qrImg.width, qrImg.height),
        };

        const decoded = await decodeQrFromVideoFrame(fakeVideo, fakeCanvas, fakeCtx, 1, 1, null);
        assert.equal(decoded, raw);
    } finally {
        globalThis.window = prevWindow;
        __setWorkerFactoryForTests(null);
    }
});

test('deblurQrImageData rescues severely blurred low-contrast CCCD QR with glare that fails raw ZXing', async () => {
    assert.equal(TIGHT_REGION_RATIO, 0.42);
    const raw = '087095001234|341789123|Nguyễn Đỗ Tuấn Kiệt|15021995|Nam|Ấp 1, Xã Mỹ Tân, TP Cao Lãnh, Đồng Tháp|10052021';
    const written = await writeBarcode(raw, { format: 'QRCode', ecLevel: 'M' });
    const sym = written.symbol;

    // Tạo ảnh mô phỏng thẻ CCCD đưa sát webcam: tương phản thấp (95..160), chồng lóa đèn + nhiễu gợn sóng, làm mờ 3 lớp
    const scale = 5;
    const quiet = 6;
    const w = (sym.width + quiet * 2) * scale;
    const h = (sym.height + quiet * 2) * scale;
    const data = new Uint8ClampedArray(w * h * 4).fill(160);
    for (let i = 3; i < data.length; i += 4) data[i] = 255;

    for (let y = 0; y < sym.height; y++) {
        for (let x = 0; x < sym.width; x++) {
            const isBlack = sym.data[y * sym.width + x] === 0;
            for (let dy = 0; dy < scale; dy++) {
                for (let dx = 0; dx < scale; dx++) {
                    const px = (x + quiet) * scale + dx;
                    const py = (y + quiet) * scale + dy;
                    const glare = Math.round(35 * Math.exp(-((px - w * 0.3) ** 2 + (py - h * 0.3) ** 2) / (2 * (w * 0.35) ** 2)));
                    const wave = Math.round(10 * Math.sin(px * 0.25) * Math.cos(py * 0.25));
                    const v = Math.min(255, Math.max(0, (isBlack ? 95 : 160) + glare + wave));
                    const idx = (py * w + px) * 4;
                    data[idx] = v;
                    data[idx + 1] = v;
                    data[idx + 2] = v;
                }
            }
        }
    }

    let degraded = new ImageData(data, w, h);
    degraded = boxBlur(boxBlur(boxBlur(degraded, 2), 2), 2);

    // Ảnh gốc bị mờ nặng khiến ZXing đọc trực tiếp bị trượt
    const rawRes = await readBarcodes(degraded, ZXING_VIDEO_OPTIONS);
    assert.equal(rawRes.some((r) => r.isValid && r.text === raw), false);

    // Sau khi qua bộ lọc khử mờ DoG + Unsharp Mask, ZXing đọc chính xác chuỗi CCCD tiếng Việt
    const t0 = performance.now();
    const deblurred = deblurQrImageData(degraded, { fineRadius: 3, sharpenAmount: 3.2, coarseRadius: 12, contrastGain: 2.4 });
    const elapsedMs = performance.now() - t0;
    assert.ok(elapsedMs < 80, `deblurQrImageData took ${elapsedMs.toFixed(1)}ms`);

    const rescued = await readBarcodes(deblurred, ZXING_VIDEO_OPTIONS);
    assert.equal(rescued.length, 1);
    assert.equal(rescued[0].isValid, true);
    assert.equal(rescued[0].text, raw);
});

test('analyzeQrFrameQuality and formatFrameQualityGuidance detect sharpness, blur_close, and glare', async () => {
    const raw = '087095001234|341789123|Nguyễn Đỗ Tuấn Kiệt|15021995|Nam|Ấp 1, Xã Mỹ Tân, TP Cao Lãnh, Đồng Tháp|10052021';
    const written = await writeBarcode(raw, { format: 'QRCode', ecLevel: 'M' });
    const crisp = symbolToImageData(written.symbol, 4, 4);
    const crispMetrics = analyzeQrFrameQuality(crisp);

    assert.equal(crispMetrics.hint, 'good');
    assert.ok(crispMetrics.sharpness >= 30, `expected high sharpness, got ${crispMetrics.sharpness}`);
    assert.equal(formatFrameQualityGuidance(crispMetrics).level, 'good');

    // Làm mờ nặng → sharpness giảm mạnh và báo blur_close
    const heavilyBlurred = boxBlur(boxBlur(boxBlur(crisp, 4), 4), 4);
    const blurMetrics = analyzeQrFrameQuality(heavilyBlurred);
    assert.ok(blurMetrics.sharpness < crispMetrics.sharpness);
    assert.equal(blurMetrics.hint, 'blur_close');
    const blurGuidance = formatFrameQualityGuidance(blurMetrics);
    assert.equal(blurGuidance.level, 'warn');
    assert.match(blurGuidance.badgeText, /Lùi thẻ ra xa/);

    // Tạo vùng lóa đèn làm bệt bóng đen (mọi pixel được cộng sáng +110 và > 25% vùng bị cháy sáng 252)
    const glareData = new Uint8ClampedArray(crisp.data.length);
    const totalPixels = crisp.width * crisp.height;
    for (let i = 0; i < totalPixels; i++) {
        const p = i * 4;
        const lifted = i < Math.floor(totalPixels * 0.35) ? 252 : Math.min(240, crisp.data[p] + 110);
        glareData[p] = lifted;
        glareData[p + 1] = lifted;
        glareData[p + 2] = lifted;
        glareData[p + 3] = 255;
    }
    const glareImg = new ImageData(glareData, crisp.width, crisp.height);
    const glareMetrics = analyzeQrFrameQuality(glareImg);
    assert.equal(glareMetrics.hint, 'glare');
    const glareGuidance = formatFrameQualityGuidance(glareMetrics);
    assert.equal(glareGuidance.level, 'warn');
    assert.match(glareGuidance.badgeText, /lóa ánh đèn/);
});
