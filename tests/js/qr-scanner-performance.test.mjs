import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';

const script = readFileSync(new URL('../../public/js/user/scan_camera.js', import.meta.url), 'utf8');

test('QR analysis is capped below full HD while the camera preview stays unchanged', () => {
    assert.match(script, /MAX_ANALYSIS_DIMENSION\s*=\s*1280/);
    assert.match(script, /TARGET_SCAN_PERIOD_MS\s*=\s*120/);
    assert.match(script, /Math\.min\(1, MAX_ANALYSIS_DIMENSION \/ Math\.max\(sourceWidth, sourceHeight\)\)/);
    assert.match(script, /canvas\.width = Math\.max\(1, Math\.round\(sourceWidth \* scale\)\)/);
    assert.doesNotMatch(script, /canvas\.width = videoElem\.videoWidth/);
});

test('QR scanning schedules the next pass after work and pauses in hidden tabs', () => {
    assert.match(script, /scanTimer = isScanning && !document\.hidden/);
    assert.match(script, /TARGET_SCAN_PERIOD_MS - \(performance\.now\(\) - scanStartedAt\)/);
    assert.doesNotMatch(script, /scanInterval = setInterval/);
    assert.match(script, /document\.addEventListener\('visibilitychange'/);
    assert.match(script, /window\.addEventListener\('pagehide', stopScan\)/);
});

test('default image settings reuse the captured pixel buffer', () => {
    assert.match(script, /if \(brightness === 1 && contrast === 1\)\s*{\s*return imageData;/);
    assert.doesNotMatch(script, /new Uint8ClampedArray\(imageData\.data\)/);
    assert.match(script, /videoElem\.readyState < videoElem\.HAVE_ENOUGH_DATA/);
});

test('QR scanner never passes broken onlyInvert mode to jsQR 1.4.0 and scopes manual select toggle to #supportForm', () => {
    const workspaceScript = readFileSync(new URL('../../public/js/user/form-workspace.js', import.meta.url), 'utf8');
    const clipboardScript = readFileSync(new URL('../../public/js/user/transaction-form-clipboard.js', import.meta.url), 'utf8');

    assert.doesNotMatch(script, /['"]onlyInvert['"]/);
    assert.doesNotMatch(workspaceScript, /['"]onlyInvert['"]/);
    assert.match(clipboardScript, /\$\("#supportForm select\[id\]"\)\.each/);
    assert.doesNotMatch(clipboardScript, /\$\("select\[id\]"\)\.each/);
});
