// Local OCR only. Language files must already exist; no document is uploaded.
const fs = require('node:fs');
const path = require('node:path');
const { createWorker, PSM } = require('tesseract.js');

const normalize = (s) => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').toLowerCase();

async function main() {
    const manifest = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
    for (const lang of ['vie', 'eng']) {
        if (!fs.existsSync(path.join(manifest.models, `${lang}.traineddata`))) {
            throw new Error(`Thiếu ${lang}.traineddata trong ${manifest.models}`);
        }
    }
    const worker = await createWorker(['vie', 'eng'], 1, {
        langPath: manifest.models, gzip: false, cacheMethod: 'none',
        errorHandler: (error) => { process.stderr.write(String(error)); process.exit(1); },
    });
    const candidates = [];
    try {
        for (const page of manifest.pages) {
            await worker.setParameters({ tessedit_pageseg_mode: PSM.AUTO, preserve_interword_spaces: '1' });
            const { data } = await worker.recognize(page.path, {}, { text: true, tsv: true });
            const isSlip = /phieu\s+trinh|noi\s+dung\s+trinh/.test(normalize(data.text));
            candidates.push({ page: page.page, region: 'Toàn trang', text: data.text, is_slip: isSlip });
            if (isSlip) {
                // Find the Số label in the right-hand panel. The original page stays intact.
                const words = (data.tsv || '').split('\n').slice(1).map((line) => line.split('\t'));
                const label = words.find((w) => w.length >= 12 && /^so\s*:?.*$/.test(normalize(w[11]).trim())
                    && Number(w[6]) > page.width * 0.35 && Number(w[6]) < page.width * 0.7
                    && Number(w[7]) > page.height * 0.2);
                const left = label ? Math.max(0, Number(label[6]) - 12) : Math.floor(page.width * 0.5);
                const top = label ? Math.max(0, Number(label[7]) - 12) : 0;
                await worker.setParameters({ tessedit_pageseg_mode: PSM.SINGLE_BLOCK });
                const panel = await worker.recognize(page.path, {
                    rectangle: { left, top, width: page.width - left, height: page.height - top },
                });
                candidates.push({ page: page.page, region: 'Ô nội dung trình', text: panel.data.text, is_slip: true });
                // A complete slip is sufficient; later pages may refer to different documents.
                const text = normalize(panel.data.text);
                if (/ngay\s*[:.\-]?\s*\d/.test(text) && /noi\s+dung\s*[:.]/.test(text)) break;
            }
        }
        process.stdout.write(JSON.stringify(candidates));
    } finally {
        await worker.terminate();
    }
}
main().catch((error) => { process.stderr.write(error.message + '\n'); process.exitCode = 1; });
