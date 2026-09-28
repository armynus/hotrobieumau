<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

/** Read-only PDF scanning; isolated from the existing auto-update queue. */
class PdfDocumentReviewScanner
{
    public function __construct(private readonly DocumentReviewMetadataParser $parser) {}

    public function scan(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Không đọc được file PDF.');
        }
        $hash = hash_file('sha256', $path);
        $cache = storage_path('app/private/ocr-review-cache/'.hash('sha256', $hash.'|v1|'.json_encode(config('documents.review'))).'.json');
        if (is_file($cache)) {
            try {
                $saved = json_decode(file_get_contents($cache), true, flags: JSON_THROW_ON_ERROR);
                if (! empty($saved['candidates'])) {
                    return $this->choose($saved['candidates']) + ['sha256' => $hash];
                }
                if (($saved['region'] ?? null) === 'Ô nội dung trình') {
                    $saved['metadata'] = $this->parser->parse($saved['text'], true);

                    return $saved;
                }
            } catch (\JsonException) {
                // An interrupted cache write must not permanently block a PDF.
            }
        }

        $candidates = [];
        $warnings = [];
        $maxPages = max(1, min(5, (int) config('documents.review.max_pages', 2)));
        $pdftotext = $this->binary('documents.metadata_extraction.pdftotext_binary', 'pdftotext');
        if ($pdftotext !== null) {
            try {
                $text = $this->run([$pdftotext, '-f', '1', '-l', (string) $maxPages, '-layout', '-enc', 'UTF-8', $path, '-']);
                foreach (explode("\f", $text) as $index => $page) {
                    if (trim($page) !== '') {
                        $candidates[] = ['page' => $index + 1, 'region' => 'Lớp chữ PDF', 'text' => $page, 'is_slip' => false];
                    }
                }
            } catch (Throwable $e) {
                $warnings[] = 'Đọc lớp chữ lỗi; thử OCR: '.$e->getMessage();
            }
        }
        $best = $this->choose($candidates);
        // Slips with columns are re-read spatially to exclude handwritten routing notes.
        if (! $best || ! $best['metadata']['issued_date'] || ! $best['metadata']['title'] || $best['metadata']['is_slip']) {
            $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pdf-review-'.Str::uuid();
            File::makeDirectory($directory, 0700, true);
            try {
                $render = $this->binary('documents.metadata_extraction.pdftoppm_binary', 'pdftoppm');
                $node = $this->binary('documents.review.node_binary', 'node');
                if (! $render || ! $node) {
                    throw new RuntimeException('Cần pdftoppm và Node.js để OCR. Xem docs/document-ocr-review.md.');
                }
                $this->run([$render, '-f', '1', '-l', (string) $maxPages, '-r', (string) config('documents.review.dpi', 220), '-png', $path, $directory.'/page']);
                $images = glob($directory.'/page-*.png') ?: [];
                natsort($images);
                $pages = [];
                foreach ($images as $image) {
                    [$width, $height] = getimagesize($image);
                    preg_match('/-(\d+)\.png$/', $image, $match);
                    $pages[] = ['path' => $image, 'page' => (int) $match[1], 'width' => $width, 'height' => $height];
                }
                if ($pages === []) {
                    throw new RuntimeException('PDF không có trang đọc được.');
                }
                $manifest = $directory.'/request.json';
                file_put_contents($manifest, json_encode(['models' => config('documents.review.models_path'), 'pages' => $pages], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                $ocr = json_decode($this->run([$node, base_path('scripts/documents/ocr-review.cjs'), $manifest]), true, flags: JSON_THROW_ON_ERROR);
                $candidates = array_merge($candidates, $ocr);
                $best = $this->choose($candidates);
            } catch (Throwable $e) {
                if (! $best) {
                    throw $e;
                }
                $warnings[] = 'OCR lỗi: '.$e->getMessage();
            } finally {
                File::deleteDirectory($directory);
            }
        }
        if (! $best) {
            throw new RuntimeException('Không đọc được nội dung từ PDF.');
        }
        $best['sha256'] = $hash;
        $best['metadata']['warnings'] = array_merge($warnings, $best['metadata']['warnings']);
        // Keep partial OCR results for inspection; engine failures should be retried next time.
        if ($warnings === []) {
            File::ensureDirectoryExists(dirname($cache));
            File::replace($cache, json_encode(['candidates' => $candidates], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        }

        return $best;
    }

    private function choose(array $candidates): ?array
    {
        $best = null;
        $score = -1;
        // Prefer the earliest presentation slip; otherwise use the first document page.
        $slipPages = array_column(array_filter($candidates, fn ($c) => $c['is_slip'] || $this->parser->parse($c['text'])['is_slip']), 'page');
        $page = $slipPages !== [] ? min($slipPages) : ($candidates !== [] ? min(array_column($candidates, 'page')) : null);
        foreach ($candidates as $candidate) {
            if ($candidate['page'] !== $page) {
                continue;
            }
            $metadata = $this->parser->parse($candidate['text'], $candidate['is_slip']);
            $current = ($metadata['issued_date'] ? 4 : 0) + ($metadata['title'] ? 4 : 0)
                + ($metadata['document_code'] ? 1 : 0) + ($metadata['issuing_agency'] ? 1 : 0);
            if ($metadata['is_slip']) {
                $current += 20;
            }
            if ($candidate['region'] === 'Ô nội dung trình') {
                $current += 2;
            }
            if ($current > $score) {
                $score = $current;
                $best = $candidate + ['metadata' => $metadata];
            }
        }

        return $best;
    }

    private function binary(string $key, string $fallback): ?string
    {
        $configured = trim((string) config($key, ''));

        return $configured !== '' && is_file($configured) ? $configured : (new ExecutableFinder)->find($configured ?: $fallback);
    }

    private function run(array $arguments): string
    {
        $process = new Process($arguments, base_path());
        $process->setTimeout(max(30, (int) config('documents.review.timeout_seconds', 180)));
        $process->run();
        if (! $process->isSuccessful()) {
            throw new RuntimeException(mb_substr(trim($process->getErrorOutput()) ?: 'Không chạy được bộ đọc PDF.', 0, 1000));
        }

        return $process->getOutput();
    }
}
