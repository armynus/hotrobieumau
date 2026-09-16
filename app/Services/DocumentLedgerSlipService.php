<?php

namespace App\Services;

use App\Models\DocumentLedgerEntry;
use App\Models\User;
use App\Support\DocumentLedgerTemplateProcessor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Settings;
use RuntimeException;

/** Xuất một dòng sổ vào mẫu Word; không cập nhật sổ, kho hoặc phân phối văn bản. */
class DocumentLedgerSlipService
{
    public function values(User $clerk, DocumentLedgerEntry $entry, array $options): array
    {
        abort_unless($clerk->isClerk() && $clerk->branch_id && (int) $clerk->branch_id === (int) $entry->branch_id, 403);
        $date = \Carbon\Carbon::createFromFormat('!Y-m-d', $options['print_date']);
        $department = $options['department_name'] ?? 'Phòng Tổng hợp';
        $placeName = $options['place_name'] ?? (string) $clerk->branch?->branch_place;
        $place = trim($placeName);
        $signatureTitle = $options['signature_title'] ?? 'Trưởng phòng Tổng hợp';

        return [
            'branch_name' => mb_strtoupper((string) $clerk->branch?->branch_name, 'UTF-8'),
            'department_name' => mb_strtoupper($department, 'UTF-8'),
            'place_name' => $placeName,
            'place_line' => $place === '' ? '' : $place.',',
            'print_date' => $date->format('d'),
            'print_month' => $date->format('m'),
            'print_year' => $date->format('Y'),
            'submitted_to' => $options['submitted_to'] ?? 'Ban Giám đốc',
            'signature_title' => mb_strtoupper($signatureTitle, 'UTF-8'),
            'prepared_by' => $options['prepared_by'] ?? '',
            'number' => $entry->number, 'year' => (string) $entry->year,
            'book' => ['incoming' => 'Văn bản đến', 'outgoing' => 'Văn bản đi', 'decision' => 'Quyết định'][$entry->book],
            'document_code' => $this->text($entry->document_code),
            'issued_date' => $entry->issued_date?->format('d/m/Y') ?? '—',
            'registered_date' => $entry->registered_date?->format('d/m/Y') ?? '—',
            'forwarded_date' => $entry->forwarded_date?->format('d/m/Y') ?? '—',
            'issuing_agency' => $this->text($entry->issuing_agency),
            'title' => $this->text($entry->title),
            'signer' => $entry->signer ?? '', 'recipient' => $entry->recipient ?? '',
            'archive_recipient' => $entry->archive_recipient ?? '', 'copy_count' => (string) $entry->copy_count,
            'receipt_signature' => $entry->receipt_signature ?? '', 'notes' => $entry->notes ?? '',
        ];
    }

    private function text(?string $value): string
    {
        return trim(str_replace("\u{00A0}", '', $value ?? '')) === '' ? '—' : $value;
    }

    public function createFile(User $clerk, DocumentLedgerEntry $entry, array $options): string
    {
        $values = $this->values($clerk, $entry, $options);
        $template = config('documents.ledger.presentation_slip_template');
        if (! is_string($template) || ! is_file($template) || filesize($template) > 2 * 1024 * 1024) {
            throw new RuntimeException('Mẫu phiếu trình chưa được cấu hình hoặc vượt dung lượng cho phép.');
        }
        $directory = storage_path('app/private/document-ledger-slips');
        File::ensureDirectoryExists($directory, 0700);
        $path = $directory.'/'.Str::uuid().'.docx';
        $previousEscaping = Settings::isOutputEscapingEnabled();
        $previousTempDir = Settings::getTempDir();
        try {
            Settings::setTempDir($directory);
            // wordSafe đã escape XML như support_form, không escape lần thứ hai.
            Settings::setOutputEscapingEnabled(false);
            $processor = new DocumentLedgerTemplateProcessor($template);
            $temporaryPath = $processor->temporaryPath();
            $variables = $processor->getVariables();
            if (array_diff($variables, array_keys($values)) || array_diff(['document_code', 'issued_date', 'issuing_agency', 'title'], $variables)) {
                throw new RuntimeException('Mẫu phiếu trình có trường không hỗ trợ hoặc thiếu trường văn bản.');
            }
            $safe = app(SupportFormService::class);
            foreach ($variables as $variable) {
                $value = str_replace(["\r\n", "\r"], "\n", (string) $values[$variable]);
                $processor->setValue($variable, $safe->wordSafe($value));
            }
            $processor->saveAs($path);

            return $path;
        } catch (\Throwable $exception) {
            File::delete($path);
            throw $exception;
        } finally {
            unset($processor);
            if (isset($temporaryPath)) {
                File::delete($temporaryPath);
            }
            Settings::setOutputEscapingEnabled($previousEscaping);
            Settings::setTempDir($previousTempDir);
        }
    }
}
