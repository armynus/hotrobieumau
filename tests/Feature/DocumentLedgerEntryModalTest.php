<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class DocumentLedgerEntryModalTest extends TestCase
{
    public function test_full_ledger_form_renders_all_fields_and_keeps_save_outside_scroll_body(): void
    {
        $html = view('user.page.documents.partials.ledger_entry_modal', [
            'bookLabels' => ['incoming' => 'Sổ đến', 'outgoing' => 'Sổ đi', 'decision' => 'Quyết định'],
            'book' => 'incoming', 'year' => 2026,
        ])->render();
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new DOMXPath($dom);
        foreach (['number', 'registered_date', 'document_code', 'title', 'issued_date', 'issuing_agency', 'recipient', 'forwarded_date', 'receipt_signature', 'notes', 'signer', 'archive_recipient', 'copy_count'] as $name) {
            $this->assertSame(1, $xpath->query('//*[@id="ledgerEntryForm"]//*[@name="'.$name.'"]')->length, $name);
        }
        $this->assertSame(1, $xpath->query('//*[@id="ledgerEntryForm"]/*[contains(@class,"modal-footer")]//*[@id="ledgerSave"]')->length);
        $this->assertSame(0, $xpath->query('//*[contains(@class,"modal-body")]//*[@id="ledgerSave"]')->length);
        $this->assertStringContainsString('modal-dialog-scrollable', $html);
        $this->assertSame(0, $xpath->query('//input[@type="file"]')->length);
        foreach (['code', 'title', 'recipient', 'archive'] as $kind) {
            $this->assertSame(1, $xpath->query('//*[@data-history-kind="'.$kind.'"]')->length, $kind);
        }
    }
}
