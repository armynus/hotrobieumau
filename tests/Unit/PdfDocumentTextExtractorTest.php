<?php

namespace Tests\Unit;

use App\Services\PdfDocumentTextExtractor;
use Illuminate\Support\Str;
use Tests\TestCase;

class PdfDocumentTextExtractorTest extends TestCase
{
    public function test_it_accepts_configured_absolute_binary_paths(): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'document-tools-'.Str::uuid();
        mkdir($directory, 0700, true);

        $pdftotext = $directory.DIRECTORY_SEPARATOR.'pdftotext.exe';
        $pdftoppm = $directory.DIRECTORY_SEPARATOR.'pdftoppm.exe';
        $tesseract = $directory.DIRECTORY_SEPARATOR.'tesseract.exe';

        try {
            file_put_contents($pdftotext, 'test');
            file_put_contents($pdftoppm, 'test');
            file_put_contents($tesseract, 'test');

            config()->set('documents.metadata_extraction.pdftotext_binary', $pdftotext);
            config()->set('documents.metadata_extraction.pdftoppm_binary', $pdftoppm);
            config()->set('documents.metadata_extraction.tesseract_binary', $tesseract);

            $capabilities = app(PdfDocumentTextExtractor::class)->capabilities();

            $this->assertTrue($capabilities['available']);
            $this->assertTrue($capabilities['embedded_text']);
            $this->assertTrue($capabilities['ocr']);
        } finally {
            @unlink($pdftotext);
            @unlink($pdftoppm);
            @unlink($tesseract);
            @rmdir($directory);
        }
    }
}
