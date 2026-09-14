<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class DocumentLedgerImportModalTest extends TestCase
{
    public function test_import_actions_are_outside_the_scrollable_body_and_inside_the_form(): void
    {
        $html = view('user.page.documents.partials.ledger_import_modal', ['year' => 2026])->render();
        $dom = new DOMDocument;
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new DOMXPath($dom);
        $form = '//form[@id="ledgerImportForm"]';

        // Bootstrap clips modal-content to the viewport: its direct flex children
        // must be the header, scrollable body, and non-shrinking footer.
        $this->assertSame(1, $xpath->query($form.'[contains(@class,"modal-content")]')->length);
        $this->assertSame(1, $xpath->query($form.'/parent::div[contains(@class,"modal-dialog-scrollable")]')->length);
        foreach (['modal-header', 'modal-body', 'modal-footer'] as $class) {
            $this->assertSame(1, $xpath->query($form.'/div[@class="'.$class.'"]')->length);
        }
        $this->assertSame(0, $xpath->query($form.'/div[@class="modal-body"]//button[@id="ledgerConfirm"]')->length);
        $this->assertSame(1, $xpath->query($form.'/div[@class="modal-footer"]/button[@id="ledgerPreview"][@type="submit"]')->length);
        $this->assertSame(1, $xpath->query($form.'/div[@class="modal-footer"]/button[@id="ledgerConfirm"][@type="button"][@disabled]')->length);
        $this->assertSame(1, $xpath->query($form.'//input[@name="file"][@required]')->length);
        $this->assertSame(1, $xpath->query($form.'//input[@name="year"][@value="2026"]')->length);
    }
}
