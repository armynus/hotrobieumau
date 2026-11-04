// Sao chép thư viện zxing-wasm (ZXing-C++ WebAssembly) từ node_modules vào public/vendor
// để phục vụ nội bộ, không phụ thuộc CDN. Chạy lại sau mỗi lần nâng cấp: npm run vendor:zxing
import { copyFileSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const pkgDir = join(root, 'node_modules', 'zxing-wasm');
const outDir = join(root, 'public', 'vendor', 'zxing-wasm');

const { version } = JSON.parse(readFileSync(join(pkgDir, 'package.json'), 'utf8'));

mkdirSync(outDir, { recursive: true });
copyFileSync(join(pkgDir, 'dist', 'iife', 'reader', 'index.js'), join(outDir, 'zxing-reader.iife.js'));
copyFileSync(join(pkgDir, 'dist', 'reader', 'zxing_reader.wasm'), join(outDir, 'zxing_reader.wasm'));
writeFileSync(join(outDir, 'VERSION'), `zxing-wasm@${version}\n`);

console.log(`Đã sao chép zxing-wasm@${version} vào ${outDir}`);
