import assert from 'node:assert/strict';
import test from 'node:test';
import {
    toUppercaseWithoutAccents,
    parseCccdDate,
    calculateCccdExpiryDate,
    parseCccdQr,
    buildFormPayloadFromCccd,
    applyCccdToSupportForm,
    safeJsQr,
    extractTextFromJsQrResult,
} from '../../public/js/user/cccd-qr-parser.js';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const jsQrCode = readFileSync(new URL('../../public/js/jsQR.min.js', import.meta.url), 'utf8');
const sandbox = { Uint8ClampedArray, Array, Math, Object, Error, decodeURIComponent };
sandbox.self = sandbox;
vm.runInNewContext(jsQrCode, sandbox);
const jsQR = sandbox.jsQR;

test('parses standard 7-field Vietnamese CCCD QR code and normalizes all fields', () => {
    const raw = '087095001234|341789123|Nguyễn Đỗ Tuấn Kiệt|15021995|Nam|Ấp 1, Xã Mỹ Tân, TP Cao Lãnh, Đồng Tháp|10052021';
    const result = parseCccdQr(raw);

    assert.equal(result.isCccd, true);
    assert.equal(result.fields.identity_no, '087095001234');
    assert.equal(result.fields.old_identity_no, '341789123');
    assert.equal(result.fields.nameloc, 'Nguyễn Đỗ Tuấn Kiệt');
    assert.equal(result.fields.name, 'NGUYEN DO TUAN KIET');
    assert.equal(result.fields.birthday, '1995-02-15');
    assert.equal(result.fields.gender, 'Nam');
    assert.equal(result.fields.addrfull, 'Ấp 1, Xã Mỹ Tân, TP Cao Lãnh, Đồng Tháp');
    assert.equal(result.fields.identity_date, '2021-05-10');
    assert.equal(result.fields.identity_outdate, '2035-02-15'); // Sinh năm 1995, cấp 2021 (26 tuổi) -> hết hạn ở mốc 40 tuổi (2035)
    assert.equal(result.fields.identity_place, 'Bộ Công An');
    assert.equal(result.fields.identity_type, 'Căn cước công dân');
    assert.equal(result.fields.QuocTich, 'Việt Nam');

    assert.equal(result.display.birthday, '15/02/1995');
    assert.equal(result.display.identity_date, '10/05/2021');
    assert.equal(result.display.identity_outdate, '15/02/2035');
});

test('parses CCCD QR code when old CMND field is empty', () => {
    const raw = '087198005678||Trần Thị Hồng Nhung|20111998|Nữ|Phường 2, Thành phố Sa Đéc, Đồng Tháp|15082022';
    const result = parseCccdQr(raw);

    assert.equal(result.isCccd, true);
    assert.equal(result.fields.identity_no, '087198005678');
    assert.equal(result.fields.old_identity_no, '');
    assert.equal(result.display.old_identity_no, 'Không có');
    assert.equal(result.fields.nameloc, 'Trần Thị Hồng Nhung');
    assert.equal(result.fields.name, 'TRAN THI HONG NHUNG');
    assert.equal(result.fields.gender, 'Nữ');
    assert.equal(result.fields.birthday, '1998-11-20');
    // Sinh 20/11/1998, mốc 25 tuổi là 20/11/2023. Cấp ngày 15/08/2022 (trong vòng 2 năm trước mốc 25 tuổi) -> hạn đến mốc 40 tuổi (20/11/2038)
    assert.equal(result.fields.identity_outdate, '2038-11-20');
});

test('calculates indefinite expiry for citizens aged 58 or older at issue time', () => {
    const expiry = calculateCccdExpiryDate('1960-04-10', '2021-06-01');
    assert.equal(expiry.iso, '');
    assert.equal(expiry.display, 'Không thời hạn');
});

test('merges CCCD data with matched database customer while prioritizing fresh CCCD identity info', () => {
    const parsed = parseCccdQr('087095001234|341789123|Nguyễn Văn Mới|15021995|Nam|Địa chỉ mới trên CCCD|10052021');
    const dbCustomer = {
        custno: '6500123456',
        nameloc: 'Nguyễn Văn Cũ',
        phone_no: '0912345678',
        profnm: 'Kỹ sư',
        taxno: '8012345678',
        accounts: [
            { idxacno: '6500205123456', ccycd: 'VND' },
        ],
    };

    const payload = buildFormPayloadFromCccd(parsed, dbCustomer);
    assert.equal(payload.custno, '6500123456');
    assert.equal(payload.custno_hidden, '6500123456');
    assert.equal(payload.phone_no, '0912345678');
    assert.equal(payload.NgheNghiepKH, 'Kỹ sư');
    assert.equal(payload.idxacno, '6500205123456');
    assert.equal(payload.ccycd, 'VND');
    // Thông tin nhân thân lấy theo CCCD mới quét
    assert.equal(payload.nameloc, 'Nguyễn Văn Mới');
    assert.equal(payload.name, 'NGUYEN VAN MOI');
    assert.equal(payload.addrfull, 'Địa chỉ mới trên CCCD');
    assert.equal(payload.identity_no, '087095001234');
});

test('returns isCccd=false for non-CCCD QR strings', () => {
    const result = parseCccdQr('https://agribank.com.vn');
    assert.equal(result.isCccd, false);
    assert.equal(parseCccdDate('invalid-date'), null);
    assert.equal(toUppercaseWithoutAccents('Đặng Thị Ánh Tuyết'), 'DANG THI ANH TUYET');
});

test('applyCccdToSupportForm populates matching form controls and emits change + supportform:filled events', () => {
    const parsed = parseCccdQr('087095001234|341789123|Nguyễn Đỗ Tuấn Kiệt|15021995|Nam|Ấp 1, Xã Mỹ Tân, TP Cao Lãnh, Đồng Tháp|10052021');
    const controlsByKey = {
        nameloc: { name: 'nameloc', type: 'text', tagName: 'INPUT', value: '', dispatchEvent() {} },
        name: { name: 'name', type: 'text', tagName: 'INPUT', value: '', dispatchEvent() {} },
        identity_no: { name: 'identity_no', type: 'text', tagName: 'INPUT', value: '', dispatchEvent() {} },
        birthday: { name: 'birthday', type: 'date', tagName: 'INPUT', value: '', dispatchEvent() {} },
        gender: {
            name: 'gender',
            tagName: 'SELECT',
            options: [{ value: 'Nam' }, { value: 'Nữ' }],
            value: '',
            appendChild(opt) { this.options.push(opt); },
            dispatchEvent() {},
        },
        addrfull: { name: 'addrfull', type: 'text', tagName: 'INPUT', value: '', dispatchEvent() {} },
        identity_date: { name: 'identity_date', type: 'date', tagName: 'INPUT', value: '', dispatchEvent() {} },
        custno_hidden: { name: 'custno_hidden', type: 'hidden', tagName: 'INPUT', value: '', dispatchEvent() {} },
    };

    const triggeredEvents = [];
    const mockForm = {
        ownerDocument: {
            createElement() { return { value: '', textContent: '' }; },
        },
        querySelectorAll(selector) {
            const match = selector.match(/\[name="([^"]+)"\]/);
            const key = match ? match[1] : '';
            return controlsByKey[key] ? [controlsByKey[key]] : [];
        },
        dispatchEvent(evt) {
            triggeredEvents.push(evt.type);
        },
    };

    const { filledCount } = applyCccdToSupportForm(mockForm, parsed, { custno: '6500999888' }, null);
    assert.ok(filledCount >= 8);
    assert.equal(controlsByKey.nameloc.value, 'Nguyễn Đỗ Tuấn Kiệt');
    assert.equal(controlsByKey.name.value, 'NGUYEN DO TUAN KIET');
    assert.equal(controlsByKey.identity_no.value, '087095001234');
    assert.equal(controlsByKey.birthday.value, '1995-02-15');
    assert.equal(controlsByKey.gender.value, 'Nam');
    assert.equal(controlsByKey.addrfull.value, 'Ấp 1, Xã Mỹ Tân, TP Cao Lãnh, Đồng Tháp');
    assert.equal(controlsByKey.identity_date.value, '2021-05-10');
    assert.equal(controlsByKey.custno_hidden.value, '6500999888');
    assert.ok(triggeredEvents.includes('supportform:filled'));
});

test('parses 2D scanner AIM-prefixed strings and standalone 12-digit CCCD numbers', () => {
    const aimRaw = ']Q1087095001234｜341789123｜Nguyễn Đỗ Tuấn Kiệt｜15021995｜Nam｜Đồng Tháp｜10052021';
    const aimParsed = parseCccdQr(aimRaw);
    assert.equal(aimParsed.isCccd, true);
    assert.equal(aimParsed.fields.identity_no, '087095001234');
    assert.equal(aimParsed.fields.nameloc, 'Nguyễn Đỗ Tuấn Kiệt');

    const idOnly = parseCccdQr('087095001234');
    assert.equal(idOnly.isCccd, true);
    assert.equal(idOnly.isIdOnly, true);
    assert.equal(idOnly.fields.identity_no, '087095001234');

    const mergedWithDb = buildFormPayloadFromCccd(idOnly, {
        custno: '6500112233',
        nameloc: 'Lê Hoàng Nam',
        name: 'LE HOANG NAM',
    });
    assert.equal(mergedWithDb.custno, '6500112233');
    assert.equal(mergedWithDb.nameloc, 'Lê Hoàng Nam');
    assert.equal(mergedWithDb.identity_no, '087095001234');
});

test('safeJsQr prevents jsQR 1.4.0 onlyInvert crash and recovers UTF-8 from binaryData', () => {
    const dummyImage = {
        data: new Uint8ClampedArray(64 * 64 * 4),
        width: 64,
        height: 64,
    };

    assert.doesNotThrow(() => {
        const res = safeJsQr(dummyImage, 'onlyInvert', jsQR);
        assert.equal(res, '');
    });

    const utf8Bytes = Array.from(new TextEncoder().encode('087095001234||Nguyễn Văn A|01011990|Nam|Đồng Tháp|01012022'));
    assert.equal(
        extractTextFromJsQrResult({ data: '', binaryData: utf8Bytes }),
        '087095001234||Nguyễn Văn A|01011990|Nam|Đồng Tháp|01012022'
    );
});

