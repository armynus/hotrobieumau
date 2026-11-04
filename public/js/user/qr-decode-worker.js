/* global ZXingWASM */
// Web Worker giải mã QR bằng ZXing-C++ (zxing-wasm) kèm:
//   - Chấm điểm độ nét Laplacian, tỷ lệ lóa sáng (glare) và độ rung tay (motionDiff)
//   - Lọc nhiễu đa khung hình (Temporal Multi-Frame Denoising) + bộ đệm Lucky Frame
//   - Khử mờ quang học DoG + Unsharp Mask

// Đổi khi chạy `npm run vendor:zxing` với phiên bản mới để phá cache trình duyệt.
const ZXING_VERSION = '3.1.6';
const VENDOR_BASE = new URL('../../vendor/zxing-wasm/', self.location.href);
const WASM_URL = new URL(`zxing_reader.wasm?v=${ZXING_VERSION}`, VENDOR_BASE).href;

const DEFAULT_OPTIONS = {
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
};

const DEBLUR_PRESETS = [
    { fineRadius: 2, sharpenAmount: 2.5, coarseRadius: 8, contrastGain: 2.3 },
    { fineRadius: 3, sharpenAmount: 3.2, coarseRadius: 12, contrastGain: 2.4 },
    { fineRadius: 4, sharpenAmount: 3.8, coarseRadius: 16, contrastGain: 2.5 },
];

// Bộ nhớ trạng thái đa khung hình cho luồng video (Temporal Denoising + Lucky Frame)
const temporalState = {
    width: 0,
    height: 0,
    prevLum: null,
    luckyLum: null,
    luckySharpness: 0,
    luckyAge: 0,
};

function resetTemporalState(width, height) {
    temporalState.width = width;
    temporalState.height = height;
    temporalState.prevLum = null;
    temporalState.luckyLum = null;
    temporalState.luckySharpness = 0;
    temporalState.luckyAge = 0;
}

/**
 * Đo chất lượng khung hình bằng toán tử Laplacian rời rạc, phân vị tương phản và độ lệch giữa 2 khung.
 */
function analyzeLuminanceQuality(lum, width, height, prevLum = null) {
    const step = width > 320 ? 2 : 1;
    const marginX = Math.max(1, Math.floor(width * 0.08));
    const marginY = Math.max(1, Math.floor(height * 0.08));

    let lapSum = 0;
    let lapCount = 0;
    let glareCount = 0;
    let sumLum = 0;
    let motionSum = 0;
    const hasPrev = prevLum && prevLum.length === lum.length;
    const hist = new Uint32Array(256);

    for (let y = marginY; y < height - marginY; y += step) {
        const row = y * width;
        for (let x = marginX; x < width - marginX; x += step) {
            const idx = row + x;
            const c = lum[idx];
            const bin = c < 0 ? 0 : c > 255 ? 255 : c | 0;
            hist[bin]++;
            sumLum += c;
            if (c >= 245) glareCount++;

            const lap = Math.abs(4 * c - lum[idx - 1] - lum[idx + 1] - lum[idx - width] - lum[idx + width]);
            lapSum += lap;
            lapCount++;

            if (hasPrev) {
                motionSum += Math.abs(c - prevLum[idx]);
            }
        }
    }

    if (lapCount === 0) {
        return { sharpness: 0, glareRatio: 0, contrastSpread: 0, avgLum: 128, motionDiff: 0, hint: 'low_light' };
    }

    const pLowTarget = Math.floor(lapCount * 0.05);
    const pHighTarget = Math.ceil(lapCount * 0.95);
    let acc = 0;
    let pLow = 0;
    let pHigh = 255;
    for (let v = 0; v < 256; v++) {
        acc += hist[v];
        if (acc >= pLowTarget) { pLow = v; break; }
    }
    acc = 0;
    for (let v = 0; v < 256; v++) {
        acc += hist[v];
        if (acc >= pHighTarget) { pHigh = v; break; }
    }

    const contrastSpread = Math.max(0, pHigh - pLow);
    const meanLap = lapSum / lapCount;
    // Chuẩn hóa điểm độ nét về thang 0..100 (khung nét của QR thường có meanLap >= 18..40)
    const sharpness = Math.min(100, Math.round(meanLap * 2.5));
    const glareRatio = Number((glareCount / lapCount).toFixed(3));
    const avgLum = Math.round(sumLum / lapCount);
    const motionDiff = hasPrev ? Number((motionSum / lapCount).toFixed(1)) : 0;

    let hint = 'good';
    if (avgLum < 45 || contrastSpread < 28) {
        hint = 'low_light';
    } else if (hasPrev && motionDiff > 16) {
        hint = 'motion';
    } else if (glareRatio > 0.22 && (pLow > 85 || sharpness < 26)) {
        hint = 'glare';
    } else if (sharpness < 18) {
        hint = 'blur_close';
    }

    return { sharpness, glareRatio, contrastSpread, avgLum, motionDiff, hint };
}

/**
 * Lọc nhiễu đa khung hình (Temporal Denoising): trộn có trọng số khung hiện tại với khung trước khi thẻ đứng yên.
 */
function blendTemporalLuminance(currLum, prevLum, weightCurrent = 0.65) {
    const n = currLum.length;
    const out = new Float32Array(n);
    const wPrev = 1 - weightCurrent;
    for (let i = 0; i < n; i++) {
        out[i] = currLum[i] * weightCurrent + prevLum[i] * wPrev;
    }
    return out;
}

/**
 * Bộ lọc hộp tách trục ngang-dọc O(N) (Sliding-window Box Filter) trên mảng độ sáng 1 kênh.
 */
function boxFilterLuminance(lum, width, height, radius) {
    const n = width * height;
    const tmp = new Float32Array(n);
    const out = new Float32Array(n);
    const span = 2 * radius + 1;

    for (let y = 0; y < height; y++) {
        const row = y * width;
        let sum = 0;
        for (let dx = -radius; dx <= radius; dx++) {
            const xx = dx < 0 ? 0 : dx >= width ? width - 1 : dx;
            sum += lum[row + xx];
        }
        for (let x = 0; x < width; x++) {
            tmp[row + x] = sum / span;
            const xSub = x - radius < 0 ? 0 : x - radius;
            const xAdd = x + radius + 1 >= width ? width - 1 : x + radius + 1;
            sum += lum[row + xAdd] - lum[row + xSub];
        }
    }

    for (let x = 0; x < width; x++) {
        let sum = 0;
        for (let dy = -radius; dy <= radius; dy++) {
            const yy = dy < 0 ? 0 : dy >= height ? height - 1 : dy;
            sum += tmp[yy * width + x];
        }
        for (let y = 0; y < height; y++) {
            out[y * width + x] = sum / span;
            const ySub = y - radius < 0 ? 0 : y - radius;
            const yAdd = y + radius + 1 >= height ? height - 1 : y + radius + 1;
            sum += tmp[yAdd * width + x] - tmp[ySub * width + x];
        }
    }

    return out;
}

/**
 * Khử mờ quang học (Unsharp Mask) kết hợp chuẩn hóa tương phản cục bộ (Difference of Box Filters - DoG).
 */
function deblurAndNormalize(lum, width, height, preset) {
    const { fineRadius = 2, sharpenAmount = 2.5, coarseRadius = 8, contrastGain = 2.3 } = preset || {};
    const n = width * height;
    const fineBlur = boxFilterLuminance(lum, width, height, fineRadius);
    const coarseBlur = boxFilterLuminance(lum, width, height, coarseRadius);
    const out = new Uint8ClampedArray(n * 4);

    for (let i = 0; i < n; i++) {
        const l = lum[i];
        const sharpened = l + sharpenAmount * (l - fineBlur[i]);
        const normalized = 128 + (sharpened - coarseBlur[i]) * contrastGain;
        const c = normalized < 0 ? 0 : normalized > 255 ? 255 : normalized;
        const p = i * 4;
        out[p] = c;
        out[p + 1] = c;
        out[p + 2] = c;
        out[p + 3] = 255;
    }

    return new ImageData(out, width, height);
}

function extractLuminance(rgba, pixelCount) {
    const lum = new Float32Array(pixelCount);
    for (let i = 0, p = 0; i < pixelCount; i++, p += 4) {
        lum[i] = (rgba[p] * 77 + rgba[p + 1] * 150 + rgba[p + 2] * 29) >> 8;
    }
    return lum;
}

async function tryReadText(imageData, opts) {
    const results = await ZXingWASM.readBarcodes(imageData, opts);
    const hit = Array.isArray(results) ? results.find((r) => r && r.isValid && r.text) : null;
    return hit ? String(hit.text).trim() : '';
}

let ready;
try {
    importScripts(new URL(`zxing-reader.iife.js?v=${ZXING_VERSION}`, VENDOR_BASE).href);
    ready = ZXingWASM.prepareZXingModule({
        overrides: {
            locateFile: (path, prefix) => (path.endsWith('.wasm') ? WASM_URL : prefix + path),
        },
        fireImmediately: true,
    });
    ready.then(
        () => self.postMessage({ type: 'ready' }),
        (err) => self.postMessage({ type: 'init-error', error: String((err && err.message) || err) })
    );
} catch (err) {
    ready = Promise.reject(err);
    ready.catch(() => {});
    self.postMessage({ type: 'init-error', error: String((err && err.message) || err) });
}

self.onmessage = async (event) => {
    const msg = event.data || {};
    if (msg.type !== 'decode') return;

    const { id, width, height, buffer, options, deblurMode = 'video', presetIndex = 0 } = msg;
    try {
        await ready;
        const baseOpts = { ...DEFAULT_OPTIONS, ...(options || {}) };
        const rawBytes = new Uint8ClampedArray(buffer);
        const rawImage = new ImageData(rawBytes, width, height);
        const lum = extractLuminance(rawBytes, width * height);

        const isVideoMode = deblurMode === 'video';
        if (isVideoMode && (temporalState.width !== width || temporalState.height !== height)) {
            resetTemporalState(width, height);
        }

        const metrics = analyzeLuminanceQuality(
            lum,
            width,
            height,
            isVideoMode ? temporalState.prevLum : null
        );

        // Bước 1: đọc trực tiếp trên ảnh gốc (nhanh nhất khi ảnh đủ nét)
        const directHit = await tryReadText(rawImage, baseOpts);
        if (directHit) {
            if (isVideoMode) temporalState.prevLum = lum;
            self.postMessage({ type: 'result', id, text: directHit, metrics });
            return;
        }

        // Bước 2: Lọc nhiễu đa khung hình + Lucky Frame + Khử mờ DoG + Unsharp Mask
        if (deblurMode !== 'none' && width >= 48 && height >= 48) {
            let workingLum = lum;

            if (isVideoMode) {
                // Nếu thẻ đang đứng yên (motionDiff nhỏ), trộn với khung trước để triệt nhiễu hạt webcam
                if (temporalState.prevLum && metrics.motionDiff > 0 && metrics.motionDiff < 9.0) {
                    workingLum = blendTemporalLuminance(lum, temporalState.prevLum, 0.65);
                }

                // Cập nhật Lucky Frame (khung sắc nét nhất trong 5 nhịp gần nhất)
                temporalState.luckyAge++;
                if (!temporalState.luckyLum || metrics.sharpness >= temporalState.luckySharpness || temporalState.luckyAge > 5) {
                    temporalState.luckyLum = workingLum;
                    temporalState.luckySharpness = metrics.sharpness;
                    temporalState.luckyAge = 0;
                }
                temporalState.prevLum = lum;
            }

            const presetsToRun = deblurMode === 'image'
                ? DEBLUR_PRESETS
                : [DEBLUR_PRESETS[Math.abs(presetIndex) % 2]];

            for (const preset of presetsToRun) {
                const deblurred = deblurAndNormalize(workingLum, width, height, preset);
                const deblurHit = await tryReadText(deblurred, baseOpts);
                if (deblurHit) {
                    self.postMessage({ type: 'result', id, text: deblurHit, metrics });
                    return;
                }
                if (deblurMode === 'image') {
                    const histHit = await tryReadText(deblurred, { ...baseOpts, binarizer: 'GlobalHistogram' });
                    if (histHit) {
                        self.postMessage({ type: 'result', id, text: histHit, metrics });
                        return;
                    }
                }
            }

            // Bước 3 (Video Lucky Frame): nếu khung hiện tại bị rung/mờ hơn khung Lucky vừa bắt được,
            // thử khử mờ sâu (DEBLUR_PRESETS[2]) trên Lucky Frame
            if (
                isVideoMode &&
                temporalState.luckyLum &&
                temporalState.luckyLum !== workingLum &&
                temporalState.luckySharpness >= metrics.sharpness + 6
            ) {
                const luckyDeblurred = deblurAndNormalize(temporalState.luckyLum, width, height, DEBLUR_PRESETS[2]);
                const luckyHit = await tryReadText(luckyDeblurred, baseOpts);
                if (luckyHit) {
                    self.postMessage({ type: 'result', id, text: luckyHit, metrics });
                    return;
                }
            }
        }

        self.postMessage({ type: 'result', id, text: '', metrics });
    } catch (err) {
        self.postMessage({ type: 'result', id, text: '', error: String((err && err.message) || err) });
    }
};
