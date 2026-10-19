<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\SupportForm;
use App\Models\User;
use App\Services\FormWorkspaceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminFormFieldCascadeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_updating_field_code_cascades_to_all_support_forms(): void
    {
        $this->withoutMiddleware();
        $this->withSession(['admin_id' => 1, 'user_id' => 1]);

        $oldCode = 'test_field_old_' . uniqid();
        $newCode = 'test_field_new_' . uniqid();
        $unrelatedCode = 'test_field_other_' . uniqid();

        // 1. Create a FormField
        $formField = FormField::create([
            'field_name'    => 'Test Field Cascade',
            'field_code'    => $oldCode,
            'data_type'     => 'text',
            'content_group' => 'customer',
            'display_order' => 999,
            'placeholder'   => 'Placeholder',
            'value'         => '',
        ]);

        // 2. Create SupportForms: one with oldCode, one without oldCode
        $supportForm1 = SupportForm::create([
            'name'          => 'Form Test Sync 1',
            'file_template' => 'forms/test1.docx',
            'fields'        => json_encode(['nameloc', $oldCode, 'phone_no']),
        ]);

        $supportForm2 = SupportForm::create([
            'name'          => 'Form Test Sync 2',
            'file_template' => 'forms/test2.docx',
            'fields'        => json_encode([$oldCode, $unrelatedCode]),
        ]);

        $supportFormUnrelated = SupportForm::create([
            'name'          => 'Form Test Sync Unrelated',
            'file_template' => 'forms/test3.docx',
            'fields'        => json_encode(['nameloc', 'phone_no']),
        ]);

        // 3. Post update to change field_code from $oldCode to $newCode
        $response = $this->postJson(route('admin_update_field'), [
            'field_id'      => $formField->id,
            'field_name'    => 'Test Field Cascade Renamed',
            'field_code'    => $newCode,
            'data_type'     => 'text',
            'content_group' => 'customer',
            'display_order' => 999,
            'placeholder'   => 'Placeholder',
            'value'         => '',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => true]);

        // Check FormField updated
        $formField->refresh();
        $this->assertSame($newCode, $formField->field_code);

        // Check SupportForm 1 updated
        $supportForm1->refresh();
        $codes1 = FormWorkspaceService::fieldCodes($supportForm1->fields);
        $this->assertContains($newCode, $codes1);
        $this->assertNotContains($oldCode, $codes1);
        $this->assertEquals(['nameloc', $newCode, 'phone_no'], $codes1);

        // Check SupportForm 2 updated
        $supportForm2->refresh();
        $codes2 = FormWorkspaceService::fieldCodes($supportForm2->fields);
        $this->assertContains($newCode, $codes2);
        $this->assertNotContains($oldCode, $codes2);
        $this->assertEquals([$newCode, $unrelatedCode], $codes2);

        // Check unrelated SupportForm remains unchanged
        $supportFormUnrelated->refresh();
        $codesUnrelated = FormWorkspaceService::fieldCodes($supportFormUnrelated->fields);
        $this->assertEquals(['nameloc', 'phone_no'], $codesUnrelated);
    }

    public function test_updating_field_with_exact_matching_does_not_affect_substring_codes(): void
    {
        $this->withoutMiddleware();
        $this->withSession(['admin_id' => 1, 'user_id' => 1]);

        $baseCode = 'ExactCode_' . uniqid();
        $extendedCode = $baseCode . '_EXT';
        $newBaseCode = $baseCode . '_NEW';

        $field = FormField::create([
            'field_name'    => 'Base Field',
            'field_code'    => $baseCode,
            'data_type'     => 'text',
            'content_group' => 'customer',
            'display_order' => 10,
        ]);

        $form = SupportForm::create([
            'name'          => 'Exact Match Test Form',
            'file_template' => 'forms/exact.docx',
            'fields'        => json_encode([$baseCode, $extendedCode]),
        ]);

        $response = $this->postJson(route('admin_update_field'), [
            'field_id'      => $field->id,
            'field_name'    => 'Base Field',
            'field_code'    => $newBaseCode,
            'data_type'     => 'text',
            'content_group' => 'customer',
            'display_order' => 10,
        ]);

        $response->assertOk();

        $form->refresh();
        $codes = FormWorkspaceService::fieldCodes($form->fields);
        $this->assertEquals([$newBaseCode, $extendedCode], $codes);
    }

    public function test_deleting_field_removes_it_from_support_forms(): void
    {
        $this->withoutMiddleware();
        $this->withSession(['admin_id' => 1, 'user_id' => 1]);

        $codeToDelete = 'del_code_' . uniqid();

        $field = FormField::create([
            'field_name'    => 'Delete Test Field',
            'field_code'    => $codeToDelete,
            'data_type'     => 'text',
            'content_group' => 'customer',
            'display_order' => 10,
        ]);

        $form = SupportForm::create([
            'name'          => 'Delete Test Form',
            'file_template' => 'forms/del.docx',
            'fields'        => json_encode(['nameloc', $codeToDelete, 'phone_no']),
        ]);

        $response = $this->postJson(route('admin_delete_field'), [
            'field_id' => $field->id,
        ]);

        $response->assertOk();
        $response->assertJson(['status' => true]);

        $this->assertDatabaseMissing('form_fields', ['id' => $field->id]);

        $form->refresh();
        $codes = FormWorkspaceService::fieldCodes($form->fields);
        $this->assertEquals(['nameloc', 'phone_no'], $codes);
    }
}
