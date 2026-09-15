<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class DocumentDistributionEditorTest extends TestCase
{
    public function test_quick_edit_contains_recipient_controls_inside_form_and_fixed_footer(): void
    {
        $html = view('user.page.documents.partials.quick_action_modals', [
            'documentTypes' => collect(),
            'directors' => collect([(object) ['id' => 2, 'name' => 'Giám đốc', 'position' => null]]),
            'departments' => collect([(object) ['id' => 3, 'department_name' => 'Phòng A']]),
            'type2Branches' => collect([(object) ['id' => 4, 'branch_name' => 'Chi nhánh B']]),
        ])->render();
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new DOMXPath($dom);
        foreach (['to_user_ids[]', 'to_department_ids[]', 'to_branch_ids[]', 'is_public_level', 'sync_recipients'] as $name) {
            $this->assertSame(1, $xpath->query('//*[@id="quickEditDocumentForm"]//*[@name="'.$name.'"]')->length, $name);
        }
        $this->assertSame(3, $xpath->query('//*[@id="quickEditVisibility"]/option')->length);
        $this->assertSame(1, $xpath->query('//*[@id="quickEditDocumentForm"]/*[contains(@class,"modal-footer")]//*[@id="quickEditSubmit"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="quickEditDocumentModal"]/*[contains(@class,"modal-dialog-scrollable")]')->length);
        $this->assertStringContainsString('thu hồi quyền xem', $html);
    }
}
