<?php

namespace Tests\Unit;

use App\Jobs\ExtractDocumentMetadata;
use App\Jobs\GenerateDocumentLedgerExport;
use App\Jobs\ProcessDataImport;
use Tests\TestCase;

class DocumentOcrQueueConfigurationTest extends TestCase
{
    public function test_ocr_job_uses_the_dedicated_queue_and_expected_retry_policy(): void
    {
        $job = new ExtractDocumentMetadata(123);

        $this->assertSame('document-ocr', $job->queue);
        $this->assertSame(180, $job->timeout);
        $this->assertSame(2, $job->tries);
        $this->assertSame([60], $job->backoff());
    }

    public function test_supported_queue_reservations_outlast_the_ocr_job_timeout(): void
    {
        $ocrJob = new ExtractDocumentMetadata(123);
        $importJob = new ProcessDataImport(456);
        $exportJob = new GenerateDocumentLedgerExport(789);
        $longestTimeout = max($ocrJob->timeout, $importJob->timeout, $exportJob->timeout);

        $this->assertSame('data-imports', $importJob->queue);
        $this->assertSame(2, $importJob->tries);
        $this->assertSame('document-exports', $exportJob->queue);
        $this->assertSame(2, $exportJob->tries);

        foreach (['database', 'beanstalkd', 'redis'] as $connection) {
            $retryAfter = (int) config("queue.connections.{$connection}.retry_after");

            $this->assertGreaterThan(
                $longestTimeout,
                $retryAfter,
                "Queue [{$connection}] must reserve a job longer than the OCR timeout."
            );
        }
    }
}
