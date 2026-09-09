<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class PdfDocumentTextExtractor
{
    /**
     * @return array{available:bool, embedded_text:bool, ocr:bool, message:string}
     */
    public function capabilities(): array
    {
        $pdftotext = $this->binary('pdftotext_binary', 'pdftotext');
        $pdftoppm = $this->binary('pdftoppm_binary', 'pdftoppm');
        $tesseract = $this->binary('tesseract_binary', 'tesseract');
        $ocr = $pdftoppm !== null && $tesseract !== null;

        $parts = [];
        if ($pdftotext !== null) {
            $parts[] = 'đọc lớp chữ PDF';
        }
        if ($ocr) {
            $parts[] = 'OCR ảnh bằng Tesseract';
        }

        return [
            'available' => $pdftotext !== null || $ocr,
            'embedded_text' => $pdftotext !== null,
            'ocr' => $ocr,
            'message' => $parts !== []
                ? 'Sẵn sàng: '.implode(', ', $parts).'.'
                : 'Chưa tìm thấy pdftotext hoặc bộ pdftoppm + Tesseract. Hãy cấu hình đường dẫn trong .env.',
        ];
    }

    /**
     * @return array{text:string, engine:string}
     */
    public function extract(string $pdfPath): array
    {
        if (! is_file($pdfPath) || ! is_readable($pdfPath)) {
            throw new RuntimeException('Không đọc được file PDF: '.$pdfPath);
        }

        $embeddedText = '';
        $pdftotext = $this->binary('pdftotext_binary', 'pdftotext');
        if ($pdftotext !== null) {
            $embeddedText = $this->extractEmbeddedText($pdftotext, $pdfPath);
            if ($this->hasUsefulText($embeddedText)) {
                return ['text' => $embeddedText, 'engine' => 'pdftotext'];
            }
        }

        $pdftoppm = $this->binary('pdftoppm_binary', 'pdftoppm');
        $tesseract = $this->binary('tesseract_binary', 'tesseract');
        if ($pdftoppm !== null && $tesseract !== null) {
            $ocrText = $this->extractWithOcr($pdftoppm, $tesseract, $pdfPath);
            if (trim($ocrText) !== '') {
                return ['text' => $ocrText, 'engine' => 'tesseract'];
            }
        }

        if (trim($embeddedText) !== '') {
            return ['text' => $embeddedText, 'engine' => 'pdftotext-low-quality'];
        }

        throw new RuntimeException('Không trích xuất được chữ từ PDF; máy chủ chưa có OCR phù hợp hoặc file không đọc được.');
    }

    private function extractEmbeddedText(string $binary, string $pdfPath): string
    {
        $process = new Process([
            $binary,
            '-f', '1',
            '-l', (string) $this->maxPages(),
            '-layout',
            '-enc', 'UTF-8',
            $pdfPath,
            '-',
        ]);
        $this->run($process);

        return $process->getOutput();
    }

    private function extractWithOcr(string $renderBinary, string $ocrBinary, string $pdfPath): string
    {
        $tempDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'document-ocr-'.Str::uuid();
        if (! File::makeDirectory($tempDirectory, 0700, true)) {
            throw new RuntimeException('Không tạo được thư mục tạm cho OCR.');
        }

        try {
            $prefix = $tempDirectory.DIRECTORY_SEPARATOR.'page';
            $render = new Process([
                $renderBinary,
                '-f', '1',
                '-l', (string) $this->maxPages(),
                '-r', (string) max(150, min(300, (int) config('documents.metadata_extraction.dpi', 220))),
                '-png',
                $pdfPath,
                $prefix,
            ]);
            $this->run($render);

            $images = glob($prefix.'-*.png') ?: [];
            natsort($images);
            if ($images === []) {
                throw new RuntimeException('Không render được trang PDF để OCR.');
            }

            $texts = [];
            foreach ($images as $image) {
                $ocr = new Process([
                    $ocrBinary,
                    $image,
                    'stdout',
                    '-l', (string) config('documents.metadata_extraction.tesseract_languages', 'vie+eng'),
                    '--psm', '6',
                ]);
                $this->run($ocr);
                $texts[] = $ocr->getOutput();
            }

            return implode("\n\n", $texts);
        } finally {
            File::deleteDirectory($tempDirectory);
        }
    }

    private function run(Process $process): void
    {
        $process->setTimeout(max(15, (int) config('documents.metadata_extraction.timeout_seconds', 150)));
        try {
            $process->mustRun();
        } catch (Throwable $exception) {
            $error = trim($process->getErrorOutput());
            throw new RuntimeException($error !== '' ? $error : $exception->getMessage(), 0, $exception);
        }
    }

    private function hasUsefulText(string $text): bool
    {
        $plain = preg_replace('/\s+/u', '', $text) ?? '';
        if (mb_strlen($plain) < max(20, (int) config('documents.metadata_extraction.minimum_text_characters', 80))) {
            return false;
        }

        $normalized = mb_strtolower(Str::ascii($text), 'UTF-8');

        $hasContentLabel = str_contains($normalized, 'noi dung') || str_contains($normalized, 'trich yeu');
        $hasDate = (bool) preg_match('/\b[0-3]?\d[\/.\-][01]?\d[\/.\-]20\d{2}\b/', $normalized)
            || str_contains($normalized, ' ngay ');

        return $hasContentLabel && $hasDate;
    }

    private function maxPages(): int
    {
        return max(1, min(5, (int) config('documents.metadata_extraction.max_pages', 2)));
    }

    private function binary(string $configKey, string $fallback): ?string
    {
        $configured = trim((string) config('documents.metadata_extraction.'.$configKey, ''));
        if ($configured !== '' && is_file($configured)) {
            // ExecutableFinder chỉ phù hợp với tên cần dò trong PATH. Trên
            // Windows, truyền thẳng C:/... vào finder sẽ bị ghép thêm PATH và
            // trả về null dù file thật sự tồn tại.
            return $configured;
        }

        return (new ExecutableFinder)->find($configured !== '' ? $configured : $fallback);
    }
}
