/**
 * Bộ phân tích & chuẩn hóa dữ liệu mã QR Căn cước công dân (CCCD) Việt Nam
 * Chuẩn dữ liệu QR CCCD gắn chip:
 * Số CCCD (12 số) | Số CMND cũ (9 số hoặc trống) | Họ và tên | Ngày sinh (DDMMYYYY) | Giới tính | Địa chỉ thường trú | Ngày cấp (DDMMYYYY)
 */

import {
    decodeWithZxing,
    getLastFrameMetrics,
    getZxingStatus,
    isZxingAvailable,
    setLastFrameMetrics,
    warmUpZxing,
    ZXING_IMAGE_OPTIONS,
    ZXING_VIDEO_OPTIONS,
} from './qr-worker-client.js';

export { warmUpZxing, getZxingStatus, isZxingAvailable, getLastFrameMetrics };

export function toUppercaseWithoutAccents(value) {
    return String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D')
        .toUpperCase()
        .replace(/\s+/g, ' ')
        .trim();
}

export function normalizeVietnameseName(value) {
    return String(value ?? '')
        .replace(/\s+/g, ' ')
        .trim();
}

export function parseCccdDate(rawDate) {
    const clean = String(rawDate ?? '').trim();
    if (!clean) return null;

    let day, month, year;

    if (/^\d{8}$/.test(clean)) {
        // Ưu tiên chuẩn DDMMYYYY của QR CCCD Việt Nam trước
        const d1 = Number(clean.slice(0, 2));
        const m1 = Number(clean.slice(2, 4));
        const y1 = Number(clean.slice(4, 8));

        if (y1 >= 1900 && y1 <= 2100 && m1 >= 1 && m1 <= 12 && d1 >= 1 && d1 <= 31) {
            day = d1;
            month = m1;
            year = y1;
        } else {
            // Fallback sang YYYYMMDD
            year = Number(clean.slice(0, 4));
            month = Number(clean.slice(4, 6));
            day = Number(clean.slice(6, 8));
        }
    } else if (/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/.test(clean)) {
        const match = clean.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
        day = Number(match[1]);
        month = Number(match[2]);
        year = Number(match[3]);
    } else if (/^(\d{4})-(\d{1,2})-(\d{1,2})$/.test(clean)) {
        const match = clean.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
        year = Number(match[1]);
        month = Number(match[2]);
        day = Number(match[3]);
    } else {
        return null;
    }

    if (year < 1900 || year > 2100 || month < 1 || month > 12 || day < 1 || day > 31) {
        return null;
    }

    const probe = new Date(Date.UTC(year, month - 1, day));
    if (
        probe.getUTCFullYear() !== year ||
        probe.getUTCMonth() + 1 !== month ||
        probe.getUTCDate() !== day
    ) {
        return null;
    }

    const dd = String(day).padStart(2, '0');
    const mm = String(month).padStart(2, '0');
    const yyyy = String(year);

    return {
        iso: `${yyyy}-${mm}-${dd}`,
        display: `${dd}/${mm}/${yyyy}`,
        day,
        month,
        year,
    };
}

function addYearsSafe(year, month, day, yearsToAdd) {
    const targetYear = year + yearsToAdd;
    const probe = new Date(Date.UTC(targetYear, month - 1, day));
    // Xử lý ngày 29/02 năm nhuận sang năm không nhuận -> 28/02
    if (probe.getUTCMonth() + 1 !== month) {
        return { year: targetYear, month: 2, day: 28 };
    }
    return { year: targetYear, month, day };
}

function formatIso({ year, month, day }) {
    return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

function formatDisplay({ year, month, day }) {
    return `${String(day).padStart(2, '0')}/${String(month).padStart(2, '0')}/${year}`;
}

/**
 * Tính ngày hết hạn thẻ CCCD theo Luật Căn cước công dân (mốc 25, 40, 60 tuổi).
 * Nếu cấp trong vòng 2 năm trước mốc đổi thẻ thì có giá trị đến mốc tuổi tiếp theo.
 * Từ đủ 58 tuổi trở lên khi cấp thẻ thì thẻ có giá trị sử dụng không thời hạn.
 */
export function calculateCccdExpiryDate(birthdayIso, issueDateIso) {
    const birth = parseCccdDate(birthdayIso);
    if (!birth) {
        return { iso: '', display: '' };
    }

    const issue = parseCccdDate(issueDateIso);
    const referenceIso = issue ? issue.iso : new Date().toISOString().slice(0, 10);

    const milestones = [25, 40, 60];
    for (const age of milestones) {
        const milestoneDate = addYearsSafe(birth.year, birth.month, birth.day, age);
        const milestoneIso = formatIso(milestoneDate);
        const twoYearsBefore = formatIso(addYearsSafe(birth.year, birth.month, birth.day, age - 2));

        if (referenceIso < twoYearsBefore) {
            return {
                iso: milestoneIso,
                display: formatDisplay(milestoneDate),
            };
        }
        if (referenceIso < milestoneIso) {
            // Cấp trong vòng 2 năm trước mốc tuổi -> nhảy sang mốc tiếp theo
            const nextIndex = milestones.indexOf(age) + 1;
            if (nextIndex < milestones.length) {
                const nextMilestone = addYearsSafe(birth.year, birth.month, birth.day, milestones[nextIndex]);
                return {
                    iso: formatIso(nextMilestone),
                    display: formatDisplay(nextMilestone),
                };
            }
            return { iso: '', display: 'Không thời hạn' };
        }
    }

    return { iso: '', display: 'Không thời hạn' };
}

export function normalizeGender(rawGender) {
    const clean = String(rawGender ?? '').trim().toLowerCase();
    if (clean === 'nam' || clean === 'male' || clean === 'm') return 'Nam';
    if (clean === 'nữ' || clean === 'nu' || clean === 'female' || clean === 'f') return 'Nữ';
    return String(rawGender ?? '').trim();
}

export function parseCccdQr(rawText) {
    const raw = String(rawText ?? '')
        .replace(/^\uFEFF/, '')
        .replace(/[\r\n]+/g, ' ')
        .trim();
    if (!raw) {
        return { isCccd: false, raw: '', fields: {}, display: {} };
    }

    const normalizedRaw = raw.replace(/[\uFF5C\u00A6]/g, '|');
    const parts = normalizedRaw.split('|').map(part => part.trim());

    // Cho phép nhập trực tiếp 9-12 chữ số CCCD/CMND để tra cứu nhanh hoặc điền số giấy tờ
    if (parts.length === 1 && /^\d{9,12}$/.test(parts[0])) {
        const idNo = parts[0];
        const fields = {
            identity_no: idNo,
            old_identity_no: '',
            nameloc: '',
            name: '',
            birthday: '',
            gender: '',
            addrfull: '',
            identity_date: '',
            identity_outdate: '',
            identity_place: 'Bộ Công An',
            NoiCapCCCDMoi: 'Bộ Công An',
            identity_type: 'Căn cước công dân',
            QuocTich: 'Việt Nam',
            custtpcd: 'Cá nhân',
            CCCD: idNo,
            CMNDCu: '',
            SoCCCD: idNo,
            NgayCCCDMoi: '',
            HanCCCDMoi: '',
        };
        const display = {
            identity_no: idNo,
            old_identity_no: 'Không có',
            nameloc: '—',
            name: '—',
            birthday: '—',
            gender: '—',
            addrfull: '—',
            identity_date: '—',
            identity_outdate: '—',
            identity_place: 'Bộ Công An',
            identity_type: 'Căn cước công dân',
        };
        return {
            isCccd: true,
            isIdOnly: true,
            raw,
            fields,
            display,
        };
    }

    // Hỗ trợ trường hợp máy quét 2D gắn thêm tiền tố AIM (ví dụ ]Q1038098001234) ở phần tử đầu
    const idMatch = (parts[0] || '').match(/(\d{9,12})$/);
    if (parts.length < 5 || !idMatch) {
        return { isCccd: false, raw, fields: {}, display: {} };
    }

    const cccdNo = idMatch[1];
    let cmndOld = '';
    let fullName = '';
    let dobRaw = '';
    let genderRaw = '';
    let address = '';
    let issueDateRaw = '';

    if (parts.length >= 7) {
        [, cmndOld, fullName, dobRaw, genderRaw, address, issueDateRaw] = parts;
    } else if (parts.length === 6) {
        // Trường hợp định dạng rút gọn 6 phần tử (không có cột CMND cũ)
        [, fullName, dobRaw, genderRaw, address, issueDateRaw] = parts;
    } else {
        // Trường hợp 5 phần tử
        [, fullName, dobRaw, genderRaw, address] = parts;
    }

    const nameloc = normalizeVietnameseName(fullName);
    const name = toUppercaseWithoutAccents(nameloc);
    const birthParsed = parseCccdDate(dobRaw);
    const issueParsed = parseCccdDate(issueDateRaw);
    const gender = normalizeGender(genderRaw);
    const addrfull = normalizeVietnameseName(address);
    const expiry = calculateCccdExpiryDate(birthParsed?.iso || '', issueParsed?.iso || '');

    const fields = {
        identity_no: cccdNo,
        old_identity_no: cmndOld,
        nameloc,
        name,
        birthday: birthParsed ? birthParsed.iso : '',
        gender,
        addrfull,
        identity_date: issueParsed ? issueParsed.iso : '',
        identity_outdate: expiry.iso,
        identity_place: 'Bộ Công An',
        NoiCapCCCDMoi: 'Bộ Công An',
        identity_type: 'Căn cước công dân',
        QuocTich: 'Việt Nam',
        custtpcd: 'Cá nhân',
        // Các mã trường tương đương nếu có trong biểu mẫu
        CCCD: cccdNo,
        CMNDCu: cmndOld,
        SoCCCD: cccdNo,
        NgayCCCDMoi: issueParsed ? issueParsed.iso : '',
        HanCCCDMoi: expiry.iso,
    };

    const display = {
        identity_no: cccdNo,
        old_identity_no: cmndOld || 'Không có',
        nameloc: nameloc || '—',
        name: name || '—',
        birthday: birthParsed ? birthParsed.display : (dobRaw || '—'),
        gender: gender || '—',
        addrfull: addrfull || '—',
        identity_date: issueParsed ? issueParsed.display : (issueDateRaw || '—'),
        identity_outdate: expiry.display || '—',
        identity_place: 'Bộ Công An',
        identity_type: 'Căn cước công dân',
    };

    return {
        isCccd: true,
        isIdOnly: false,
        raw,
        fields,
        display,
    };
}

/**
 * Kết hợp dữ liệu CCCD vừa quét với dữ liệu khách hàng đã có trong DB (nếu tìm thấy theo số CCCD/CMND).
 * Ưu tiên thông tin nhân thân & giấy tờ mới nhất từ thẻ CCCD, đồng thời giữ lại Mã CIF, SĐT, Nghề nghiệp và Tài khoản từ DB.
 */
export function buildFormPayloadFromCccd(parsedCccd, dbCustomer = null) {
    const cccdFields = parsedCccd?.fields ? { ...parsedCccd.fields } : {};
    if (!dbCustomer || typeof dbCustomer !== 'object') {
        return cccdFields;
    }

    const dbBase = {
        custno: dbCustomer.custno || '',
        custno_hidden: dbCustomer.custno || '',
        nameloc: dbCustomer.nameloc || '',
        name: dbCustomer.name || '',
        birthday: dbCustomer.birthday || '',
        gender: dbCustomer.gender || '',
        addrfull: dbCustomer.addrfull || '',
        identity_date: dbCustomer.identity_date || '',
        identity_outdate: dbCustomer.identity_outdate || '',
        identity_place: dbCustomer.identity_place || '',
        phone_no: dbCustomer.phone_no || '',
        NgheNghiepKH: dbCustomer.profnm || '',
        branch_code: dbCustomer.branch_code || '',
        MaSoThueCN: dbCustomer.taxno || '',
        NoiCapThueCN: dbCustomer.taxno_place || '',
        addrtpcd: dbCustomer.addrtpcd || '',
        custdtltpcd: dbCustomer.custdtltpcd || '',
    };

    const merged = { ...dbBase };
    for (const [k, v] of Object.entries(cccdFields)) {
        if (v !== '' && v != null) {
            merged[k] = v;
        } else if (!(k in merged)) {
            merged[k] = '';
        }
    }

    const accounts = Array.isArray(dbCustomer.accounts)
        ? dbCustomer.accounts
        : (dbCustomer.accounts && typeof dbCustomer.accounts === 'object' ? [dbCustomer.accounts] : []);

    if (accounts.length >= 1) {
        merged.idxacno = accounts[0].idxacno || '';
        merged.idxacno_hidden = accounts[0].idxacno || '';
        merged.ccycd = (accounts[0].ccycd && String(accounts[0].ccycd).trim()) ? String(accounts[0].ccycd).trim() : 'VND';
    }

    return merged;
}

/**
 * Trích xuất chuỗi UTF-8 an toàn từ kết quả jsQR (bao gồm fallback giải mã từ binaryData nếu chunk bị ngắt).
 */
export function extractTextFromJsQrResult(code) {
    if (!code) return '';
    if (typeof code.data === 'string' && code.data.trim()) {
        return code.data.trim();
    }
    if (Array.isArray(code.binaryData) && code.binaryData.length > 0 && typeof TextDecoder !== 'undefined') {
        try {
            const decoded = new TextDecoder('utf-8', { fatal: false }).decode(new Uint8Array(code.binaryData));
            if (decoded && decoded.trim()) return decoded.trim();
        } catch (_) {}
    }
    return '';
}

/**
 * Gọi jsQR an toàn: tuyệt đối không truyền 'onlyInvert' (do lỗi crash trong jsQR 1.4.0 làm hỏng biến toàn cục d).
 */
export function safeJsQr(imageData, inversionAttempts = 'attemptBoth', jsQrFn = typeof window !== 'undefined' ? window.jsQR : null) {
    if (!imageData || !imageData.data || !imageData.width || !imageData.height || typeof jsQrFn !== 'function') {
        return '';
    }
    const safeMode = (inversionAttempts === 'dontInvert' || inversionAttempts === 'invertFirst')
        ? inversionAttempts
        : 'attemptBoth';
    try {
        const res = jsQrFn(imageData.data, imageData.width, imageData.height, { inversionAttempts: safeMode });
        return extractTextFromJsQrResult(res);
    } catch (_) {
        return '';
    }
}

/**
 * Tăng độ tương phản & chuyển sang thang xám rõ nét cho ảnh QR mờ/lóa trên thẻ nhựa CCCD.
 */
export function enhanceContrastImageData(sourceImageData, contrastFactor = 1.65) {
    if (!sourceImageData || !sourceImageData.data) return sourceImageData;
    const width = sourceImageData.width;
    const height = sourceImageData.height;
    const src = sourceImageData.data;
    const out = new Uint8ClampedArray(src.length);

    let sumLum = 0;
    const pixelCount = width * height;
    for (let i = 0; i < src.length; i += 4) {
        sumLum += 0.299 * src[i] + 0.587 * src[i + 1] + 0.114 * src[i + 2];
    }
    const avgLum = pixelCount > 0 ? sumLum / pixelCount : 128;

    for (let i = 0; i < src.length; i += 4) {
        const lum = 0.299 * src[i] + 0.587 * src[i + 1] + 0.114 * src[i + 2];
        const stretched = Math.min(255, Math.max(0, avgLum + (lum - avgLum) * contrastFactor));
        out[i] = stretched;
        out[i + 1] = stretched;
        out[i + 2] = stretched;
        out[i + 3] = 255;
    }

    if (typeof ImageData !== 'undefined') {
        return new ImageData(out, width, height);
    }
    return { data: out, width, height };
}

/**
 * Đo chất lượng khung hình (Độ nét Laplacian, tỷ lệ lóa sáng glareRatio, độ tương phản và gợi ý điều chỉnh thẻ).
 * Dùng chung cho cả kiểm thử và nhánh dự phòng jsQR.
 */
export function analyzeQrFrameQuality(sourceImageData) {
    if (!sourceImageData || !sourceImageData.data || !sourceImageData.width || !sourceImageData.height) {
        return { sharpness: 0, glareRatio: 0, contrastSpread: 0, avgLum: 128, motionDiff: 0, hint: 'low_light' };
    }
    const width = sourceImageData.width;
    const height = sourceImageData.height;
    const src = sourceImageData.data;
    const n = width * height;
    const lum = new Float32Array(n);
    for (let i = 0, p = 0; i < n; i++, p += 4) {
        lum[i] = (src[p] * 77 + src[p + 1] * 150 + src[p + 2] * 29) >> 8;
    }

    const step = width > 320 ? 2 : 1;
    const marginX = Math.max(1, Math.floor(width * 0.08));
    const marginY = Math.max(1, Math.floor(height * 0.08));
    let lapSum = 0;
    let lapCount = 0;
    let glareCount = 0;
    let sumLum = 0;
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
            lapSum += Math.abs(4 * c - lum[idx - 1] - lum[idx + 1] - lum[idx - width] - lum[idx + width]);
            lapCount++;
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
    const sharpness = Math.min(100, Math.round((lapSum / lapCount) * 2.5));
    const glareRatio = Number((glareCount / lapCount).toFixed(3));
    const avgLum = Math.round(sumLum / lapCount);

    let hint = 'good';
    if (avgLum < 45 || contrastSpread < 28) {
        hint = 'low_light';
    } else if (glareRatio > 0.22 && (pLow > 85 || sharpness < 26)) {
        hint = 'glare';
    } else if (sharpness < 18) {
        hint = 'blur_close';
    }

    return { sharpness, glareRatio, contrastSpread, avgLum, motionDiff: 0, hint };
}

/**
 * Chuyển chỉ số chất lượng khung hình thành thông điệp hướng dẫn trực quan cho người dùng.
 */
export function formatFrameQualityGuidance(metrics) {
    if (!metrics || typeof metrics !== 'object') {
        return { level: 'neutral', borderColor: '#04CA77', badgeText: 'Đưa mã QR CCCD vào khung xanh' };
    }
    const s = metrics.sharpness ?? 0;
    const glarePct = Math.round((metrics.glareRatio || 0) * 100);
    switch (metrics.hint) {
        case 'glare':
            return {
                level: 'warn',
                borderColor: '#f59e0b',
                badgeText: `💡 Bị lóa ánh đèn (${glarePct}%) — Hãy nghiêng nhẹ thẻ 10°–15°`,
            };
        case 'motion':
            return {
                level: 'warn',
                borderColor: '#f59e0b',
                badgeText: '✋ Đang rung tay — Giữ yên thẻ 1 giây để lấy nét',
            };
        case 'blur_close':
            return {
                level: 'warn',
                borderColor: '#f59e0b',
                badgeText: `🔍 Ảnh đang mờ nét (Độ nét: ${s}) — Lùi thẻ ra xa 15–20cm`,
            };
        case 'low_light':
            return {
                level: 'neutral',
                borderColor: '#38bdf8',
                badgeText: '📷 Đưa mặt trước thẻ CCCD có mã QR vào giữa khung xanh',
            };
        default:
            return {
                level: 'good',
                borderColor: '#04CA77',
                badgeText: `🎯 Độ nét tốt (${s}/100) — Giữ yên thẻ để giải mã`,
            };
    }
}

/**
 * Khử mờ quang học (Unsharp Mask) + Chuẩn hóa tương phản cục bộ (Difference of Box Filters - DoG) O(N):
 *   1. Dựng lại sườn biên các ô QR bị nhòe khi đưa thẻ CCCD gần webcam cố định tiêu cự.
 *   2. Trừ nền trung bình cục bộ để triệt tiêu bóng lóa đèn trần & hoa văn chìm trên thẻ nhựa.
 */
export function deblurQrImageData(sourceImageData, {
    fineRadius = 2,
    sharpenAmount = 2.5,
    coarseRadius = 8,
    contrastGain = 2.3,
} = {}) {
    if (!sourceImageData || !sourceImageData.data || !sourceImageData.width || !sourceImageData.height) {
        return sourceImageData;
    }
    const width = sourceImageData.width;
    const height = sourceImageData.height;
    const n = width * height;
    const src = sourceImageData.data;
    const lum = new Float32Array(n);
    for (let i = 0, p = 0; i < n; i++, p += 4) {
        lum[i] = (src[p] * 77 + src[p + 1] * 150 + src[p + 2] * 29) >> 8;
    }

    const boxFilter = (r) => {
        const tmp = new Float32Array(n);
        const res = new Float32Array(n);
        const span = 2 * r + 1;
        for (let y = 0; y < height; y++) {
            const row = y * width;
            let sum = 0;
            for (let dx = -r; dx <= r; dx++) {
                const xx = dx < 0 ? 0 : dx >= width ? width - 1 : dx;
                sum += lum[row + xx];
            }
            for (let x = 0; x < width; x++) {
                tmp[row + x] = sum / span;
                const xSub = x - r < 0 ? 0 : x - r;
                const xAdd = x + r + 1 >= width ? width - 1 : x + r + 1;
                sum += lum[row + xAdd] - lum[row + xSub];
            }
        }
        for (let x = 0; x < width; x++) {
            let sum = 0;
            for (let dy = -r; dy <= r; dy++) {
                const yy = dy < 0 ? 0 : dy >= height ? height - 1 : dy;
                sum += tmp[yy * width + x];
            }
            for (let y = 0; y < height; y++) {
                res[y * width + x] = sum / span;
                const ySub = y - r < 0 ? 0 : y - r;
                const yAdd = y + r + 1 >= height ? height - 1 : y + r + 1;
                sum += tmp[yAdd * width + x] - tmp[ySub * width + x];
            }
        }
        return res;
    };

    const fineBlur = boxFilter(Math.max(1, fineRadius));
    const coarseBlur = boxFilter(Math.max(2, coarseRadius));
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

    if (typeof ImageData !== 'undefined') {
        return new ImageData(out, width, height);
    }
    return { data: out, width, height };
}

let cachedBarcodeDetector = undefined;
function getNativeBarcodeDetector() {
    if (cachedBarcodeDetector !== undefined) return cachedBarcodeDetector;
    if (typeof window !== 'undefined' && typeof window.BarcodeDetector === 'function') {
        try {
            cachedBarcodeDetector = new window.BarcodeDetector({ formats: ['qr_code'] });
            return cachedBarcodeDetector;
        } catch (_) {}
    }
    cachedBarcodeDetector = null;
    return null;
}

export async function detectWithBarcodeDetector(source) {
    const detector = getNativeBarcodeDetector();
    if (!detector || !source) return '';
    try {
        const barcodes = await detector.detect(source);
        if (Array.isArray(barcodes) && barcodes.length > 0) {
            for (const item of barcodes) {
                if (item && typeof item.rawValue === 'string' && item.rawValue.trim()) {
                    return item.rawValue.trim();
                }
            }
        }
    } catch (_) {}
    return '';
}

/** Tỷ lệ khung ngắm xanh so với khung hình (phải khớp với #scanner-area trong scan_camera.js: 60%). */
export const SCAN_REGION_RATIO = 0.6;
/** Tỷ lệ vùng cận tâm (tập trung riêng vào mã QR ở giữa khung để khử mờ nhạy hơn). */
export const TIGHT_REGION_RATIO = 0.42;
/** Cạnh dài tối đa khi quét toàn khung hình (phòng trường hợp mã QR nằm ngoài khung xanh). */
export const MAX_FULL_FRAME_DIMENSION = 1280;
/** jsQR (dự phòng) đọc kém với ảnh nhỏ: vùng có cạnh dài dưới mức này sẽ được phóng to trước khi giải mã. */
const JSQR_MIN_ROI_DIMENSION = 720;

/**
 * Tính vùng cắt tương ứng khung ngắm xanh trên khung hình gốc của camera (độ phân giải gốc, không thu nhỏ).
 * Khi zoom số > 1, vùng cắt thu hẹp tương ứng quanh tâm.
 */
export function computeScanRegion(videoWidth, videoHeight, zoom = 1, ratio = SCAN_REGION_RATIO) {
    const vw = Math.max(1, Math.round(videoWidth || 0));
    const vh = Math.max(1, Math.round(videoHeight || 0));
    const z = Math.max(1, Number(zoom) || 1);
    const sw = Math.max(1, Math.min(vw, Math.round((vw * ratio) / z)));
    const sh = Math.max(1, Math.min(vh, Math.round((vh * ratio) / z)));
    return {
        sx: Math.round((vw - sw) / 2),
        sy: Math.round((vh - sh) / 2),
        sw,
        sh,
    };
}

function grabImageData(source, canvas, ctx, sx, sy, sw, sh, dw = sw, dh = sh) {
    canvas.width = Math.max(1, Math.round(dw));
    canvas.height = Math.max(1, Math.round(dh));
    ctx.drawImage(source, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);
    return ctx.getImageData(0, 0, canvas.width, canvas.height);
}

function grabFullFrame(source, canvas, ctx, sw, sh, maxDim) {
    const scale = Math.min(1, maxDim / Math.max(sw, sh));
    return grabImageData(source, canvas, ctx, 0, 0, sw, sh, sw * scale, sh * scale);
}

/**
 * Dự phòng khi ZXing WASM không dùng được: jsQR trên vùng khung xanh (phóng to nếu nhỏ) + toàn khung ở nhịp chẵn.
 */
function decodeVideoFrameWithJsQr(videoElem, canvas, ctx, attempt, region, applyPreprocess) {
    const longSide = Math.max(region.sw, region.sh);
    const up = longSide < JSQR_MIN_ROI_DIMENSION ? Math.min(2, JSQR_MIN_ROI_DIMENSION / longSide) : 1;
    let roi = applyPreprocess(grabImageData(
        videoElem, canvas, ctx,
        region.sx, region.sy, region.sw, region.sh,
        region.sw * up, region.sh * up
    ));
    setLastFrameMetrics(analyzeQrFrameQuality(roi));
    if (attempt % 3 === 1) {
        roi = deblurQrImageData(roi);
    } else if (attempt % 3 === 2) {
        roi = enhanceContrastImageData(roi, 1.65);
    }
    const roiHit = safeJsQr(roi, attempt % 3 === 0 ? 'attemptBoth' : 'dontInvert');
    if (roiHit) return roiHit;

    if (attempt % 2 === 0) {
        const vw = videoElem.videoWidth;
        const vh = videoElem.videoHeight;
        const full = applyPreprocess(grabFullFrame(videoElem, canvas, ctx, vw, vh, MAX_FULL_FRAME_DIMENSION));
        const fullHit = safeJsQr(full, 'dontInvert');
        if (fullHit) return fullHit;
    }
    return '';
}

/**
 * Quét đa tầng cho luồng Video Camera:
 *   1. BarcodeDetector gốc của trình duyệt (Android/macOS/ChromeOS; Windows không có).
 *   2. ZXing-C++ WASM (Web Worker) kèm khử mờ DoG + Unsharp trên vùng khung xanh (60%) hoặc vùng cận tâm (42%) ở ĐỘ PHÂN GIẢI GỐC.
 *   3. ZXing trên toàn khung (≤1280px) mỗi 4 nhịp, phòng khi mã nằm ngoài khung xanh.
 *   4. Nếu ZXing không dùng được → jsQR dự phòng (có kèm khử mờ DoG).
 */
export async function decodeQrFromVideoFrame(videoElem, canvas, ctx, attempt = 0, zoomScale = 1, preprocessFn = null) {
    if (!videoElem || !videoElem.videoWidth || !videoElem.videoHeight) return '';

    const nativeHit = await detectWithBarcodeDetector(videoElem);
    if (nativeHit) return nativeHit;

    const vw = videoElem.videoWidth;
    const vh = videoElem.videoHeight;
    // Mỗi 3 nhịp cắt vùng cận tâm 42% (tập trung riêng vào mã QR, bỏ nền thừa quanh thẻ)
    const activeRatio = attempt % 3 === 2 ? TIGHT_REGION_RATIO : SCAN_REGION_RATIO;
    const region = computeScanRegion(vw, vh, zoomScale, activeRatio);
    const applyPreprocess = (img) => (typeof preprocessFn === 'function' ? preprocessFn(img) : img);

    if (isZxingAvailable()) {
        // Lưu ý: buffer ảnh được chuyển sang Worker nên mỗi lần giải mã phải lấy ImageData mới.
        const roi = applyPreprocess(grabImageData(videoElem, canvas, ctx, region.sx, region.sy, region.sw, region.sh));
        const roiHit = await decodeWithZxing(roi, {
            options: ZXING_VIDEO_OPTIONS,
            deblurMode: 'video',
            presetIndex: attempt,
        });
        if (roiHit) return roiHit;

        if (roiHit === '') {
            if (attempt % 4 === 0) {
                const full = applyPreprocess(grabFullFrame(videoElem, canvas, ctx, vw, vh, MAX_FULL_FRAME_DIMENSION));
                const fullHit = await decodeWithZxing(full, {
                    options: ZXING_VIDEO_OPTIONS,
                    deblurMode: 'video',
                    presetIndex: attempt,
                });
                if (fullHit) return fullHit;
                if (fullHit === '') return '';
            } else {
                return '';
            }
        }
        // roiHit/fullHit === null → ZXing chưa sẵn sàng hoặc lỗi: dùng jsQR cho khung này.
    }

    return decodeVideoFrameWithJsQr(videoElem, canvas, ctx, attempt, region, applyPreprocess);
}

/**
 * Quét đa vùng & đa tỷ lệ cho ảnh tải lên (chụp nguyên thẻ CCCD hoặc ảnh cắt góc QR).
 * Thứ tự: BarcodeDetector → ZXing toàn ảnh (kèm 3 cấp độ khử mờ DoG) → các vùng cắt (ZXing trước, jsQR sau).
 */
export async function decodeQrFromImageElement(imgElem, canvas, ctx) {
    if (!imgElem || !imgElem.width || !imgElem.height) return '';

    // 1. Thử BarcodeDetector trực tiếp trên phần tử ảnh
    const nativeDirect = await detectWithBarcodeDetector(imgElem);
    if (nativeDirect) return nativeDirect;

    const iw = imgElem.naturalWidth || imgElem.width;
    const ih = imgElem.naturalHeight || imgElem.height;

    const zxingOpts = { dropIfBusy: false, timeoutMs: 8000, options: ZXING_IMAGE_OPTIONS, deblurMode: 'image' };
    let zxingUsable = isZxingAvailable();

    // 2. ZXing trên toàn ảnh (≤2000px) – thường đủ để đọc ảnh chụp nguyên thẻ CCCD
    if (zxingUsable) {
        const whole = grabFullFrame(imgElem, canvas, ctx, iw, ih, 2000);
        const wholeHit = await decodeWithZxing(whole, zxingOpts);
        if (wholeHit) return wholeHit;
        if (wholeHit === null) zxingUsable = false;
    }

    // 3. Danh sách các vùng cắt ưu tiên:
    // - Toàn ảnh ở nhiều độ phân giải (1400, 900, 600, 1800)
    // - Góc trên bên phải (nơi đặt mã QR trên mặt trước thẻ CCCD Việt Nam)
    // - Vùng trung tâm, góc trên bên trái (ảnh selfie bị lật gương), góc dưới
    const passes = [
        { sx: 0, sy: 0, sw: 1, sh: 1, maxDim: 1400 },
        { sx: 0, sy: 0, sw: 1, sh: 1, maxDim: 900 },
        { sx: 0.42, sy: 0, sw: 0.58, sh: 0.58, maxDim: 1000 }, // Góc trên phải CCCD
        { sx: 0.52, sy: 0.02, sw: 0.46, sh: 0.48, maxDim: 900 }, // Cận cảnh góc trên phải CCCD
        { sx: 0.15, sy: 0.15, sw: 0.7, sh: 0.7, maxDim: 1000 }, // Vùng trung tâm
        { sx: 0, sy: 0, sw: 0.58, sh: 0.58, maxDim: 1000 }, // Góc trên trái (ảnh bị lật)
        { sx: 0.42, sy: 0.42, sw: 0.58, sh: 0.58, maxDim: 1000 }, // Góc dưới phải (ảnh chụp ngược)
        { sx: 0, sy: 0.42, sw: 0.58, sh: 0.58, maxDim: 1000 }, // Góc dưới trái
        { sx: 0, sy: 0, sw: 1, sh: 1, maxDim: 560 },
        { sx: 0, sy: 0, sw: 1, sh: 1, maxDim: 1920 },
    ];

    for (const p of passes) {
        const cropX = Math.round(iw * p.sx);
        const cropY = Math.round(ih * p.sy);
        const cropW = Math.max(16, Math.round(iw * p.sw));
        const cropH = Math.max(16, Math.round(ih * p.sh));
        const scale = Math.min(2, p.maxDim / Math.max(cropW, cropH));
        const dw = cropW * scale;
        const dh = cropH * scale;

        const imgData = grabImageData(imgElem, canvas, ctx, cropX, cropY, cropW, cropH, dw, dh);

        const nativeCropHit = await detectWithBarcodeDetector(canvas);
        if (nativeCropHit) return nativeCropHit;

        if (zxingUsable && p.sw < 1) {
            // Vùng cắt góc: ZXing trên bản sao (buffer gửi sang Worker bị chuyển quyền sở hữu).
            // Toàn ảnh đã được ZXing thử ở bước 2 (ZXing tự thử nhiều tỷ lệ) nên bỏ qua các lượt toàn ảnh.
            const zHit = await decodeWithZxing(
                { data: imgData.data.slice(), width: imgData.width, height: imgData.height },
                zxingOpts
            );
            if (zHit) return zHit;
            if (zHit === null) zxingUsable = false;
        }

        const rawHit = safeJsQr(imgData, 'attemptBoth');
        if (rawHit) return rawHit;

        const boostedData = enhanceContrastImageData(imgData, 1.75);
        const boostedHit = safeJsQr(boostedData, 'attemptBoth');
        if (boostedHit) return boostedHit;

        const deblurredData = deblurQrImageData(imgData);
        const deblurredHit = safeJsQr(deblurredData, 'attemptBoth');
        if (deblurredHit) return deblurredHit;
    }

    return '';
}

/**
 * Điền trực tiếp dữ liệu CCCD (+ hồ sơ DB nếu có) vào form #supportForm trên trang Điền biểu mẫu.
 */
export function applyCccdToSupportForm(formElem, parsedCccd, dbCustomer = null, $ = typeof window !== 'undefined' ? window.jQuery : null) {
    if (!formElem || !parsedCccd?.isCccd) return { filledCount: 0, payload: {} };

    const payload = buildFormPayloadFromCccd(parsedCccd, dbCustomer);
    let filledCount = 0;

    // Nếu có dbCustomer, lưu vào window.currentCustomer để tương thích với bộ chọn tài khoản của transaction-form-clipboard.js
    if (typeof window !== 'undefined' && dbCustomer) {
        window.currentCustomer = dbCustomer;
        const accounts = Array.isArray(dbCustomer.accounts) ? dbCustomer.accounts : [];
        if (accounts.length) {
            window._lastAccounts = accounts;
        }
    }

    for (const [key, rawVal] of Object.entries(payload)) {
        const value = rawVal == null ? '' : String(rawVal).trim();
        if (value === '') continue;

        const escapedKey = typeof CSS !== 'undefined' && typeof CSS.escape === 'function' ? CSS.escape(key) : key;
        const controls = formElem.querySelectorAll(`[name="${key}"], #${escapedKey}`);
        if (!controls.length) continue;

        controls.forEach(control => {
            if (control.type === 'checkbox' || control.type === 'radio') {
                control.checked = control.value === value;
            } else if (control.tagName === 'SELECT') {
                let hasOption = Array.from(control.options).some(opt => opt.value === value);
                if (!hasOption) {
                    const opt = formElem.ownerDocument.createElement('option');
                    opt.value = value;
                    opt.textContent = value;
                    control.appendChild(opt);
                }
                control.value = value;
            } else {
                control.value = value;
            }
            filledCount++;
            if ($) {
                $(control).trigger('change');
            } else {
                control.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    if ($ && formElem) {
        $(formElem).trigger('supportform:filled');
    } else if (formElem) {
        formElem.dispatchEvent(new CustomEvent('supportform:filled', { bubbles: true }));
    }

    return { filledCount, payload };
}

if (typeof window !== 'undefined') {
    window.CccdQrParser = {
        toUppercaseWithoutAccents,
        normalizeVietnameseName,
        parseCccdDate,
        calculateCccdExpiryDate,
        normalizeGender,
        parseCccdQr,
        buildFormPayloadFromCccd,
        applyCccdToSupportForm,
        extractTextFromJsQrResult,
        safeJsQr,
        enhanceContrastImageData,
        deblurQrImageData,
        detectWithBarcodeDetector,
        decodeQrFromVideoFrame,
        decodeQrFromImageElement,
        computeScanRegion,
        analyzeQrFrameQuality,
        formatFrameQualityGuidance,
        getLastFrameMetrics,
        warmUpZxing,
        getZxingStatus,
    };
}
