<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ItSupportFormField;
use App\Models\ItSupportTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItSupportFormStandardizationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::first() ?? User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_it_support_form_fields_table_has_initial_seeded_data(): void
    {
        $this->assertGreaterThanOrEqual(17, ItSupportFormField::count());
        $this->assertDatabaseHas('it_support_form_fields', [
            'field_code' => 'TenCanBo',
            'content_group' => 'requester',
        ]);
        $this->assertDatabaseHas('it_support_form_fields', [
            'field_code' => 'TenThietBi',
            'content_group' => 'system_device',
        ]);
    }

    public function test_admin_can_create_edit_and_delete_it_form_field(): void
    {
        $this->withoutMiddleware();
        $this->withSession(['admin_id' => 1, 'user_id' => 1]);

        // 1. Create
        $response = $this->postJson(route('admin.it_support.form_fields.store'), [
            'field_name'    => 'Mã Khách Hàng IT Test',
            'field_code'    => 'MaKhachHangItTest',
            'data_type'     => 'text',
            'content_group' => 'requester',
            'display_order' => 15,
            'placeholder'   => 'Nhập mã...',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => true]);
        $this->assertDatabaseHas('it_support_form_fields', ['field_code' => 'MaKhachHangItTest']);

        $fieldId = $response->json('form_field.id');

        // 2. Edit
        $editResponse = $this->getJson(route('admin.it_support.form_fields.edit', ['field_id' => $fieldId]));
        $editResponse->assertOk();
        $editResponse->assertJson(['status' => true]);
        $this->assertSame('MaKhachHangItTest', $editResponse->json('field.field_code'));

        // 3. Update
        $updateResponse = $this->postJson(route('admin.it_support.form_fields.update'), [
            'field_id'      => $fieldId,
            'field_name'    => 'Mã Khách Hàng IT Test Cập Nhật',
            'field_code'    => 'MaKhachHangItTest',
            'data_type'     => 'number',
            'content_group' => 'system_device',
            'display_order' => 25,
            'placeholder'   => 'Nhập số...',
        ]);

        $updateResponse->assertOk();
        $updateResponse->assertJson(['status' => true]);
        $this->assertDatabaseHas('it_support_form_fields', [
            'id' => $fieldId,
            'field_name' => 'Mã Khách Hàng IT Test Cập Nhật',
            'data_type' => 'number',
        ]);

        // 4. Delete & cascade from template
        $template = ItSupportTemplate::create([
            'name'          => 'Biểu Mẫu Test Cascade',
            'file_template' => 'it_support_templates/cascade.docx',
            'fields'        => ['TenCanBo', 'MaKhachHangItTest'],
        ]);

        $deleteResponse = $this->postJson(route('admin.it_support.form_fields.destroy'), [
            'field_id' => $fieldId,
        ]);

        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('it_support_form_fields', ['id' => $fieldId]);

        $template->refresh();
        $this->assertEquals(['TenCanBo'], $template->fields);
    }

    public function test_admin_form_fields_view_renders_both_tabs(): void
    {
        $this->withSession(['admin_id' => 1, 'user_id' => 1]);

        $response = $this->get(route('admin_form_fields'));
        $response->assertOk();
        $response->assertSee('Mẫu Giao Dịch');
        $response->assertSee('Biểu Mẫu IT');
        $response->assertSee('dataTableIt');
    }

    public function test_sidebar_opens_it_support_when_on_it_fields(): void
    {
        $this->withSession(['admin_id' => 1, 'user_id' => 1]);

        $response = $this->get(route('admin.it_support.form_fields.index'));
        $response->assertOk();
        $response->assertSee('<li class="nav-item active" id="sidebar-item-it-templates">', false);
        $response->assertSee('<li class="nav-item " id="sidebar-item-forms">', false);
    }

    public function test_user_template_fill_view_groups_fields_correctly(): void
    {
        $this->withSession(['admin_id' => 1, 'user_id' => 1]);

        $template = ItSupportTemplate::create([
            'name'          => 'Biểu Mẫu Test Nhóm Người Dùng',
            'file_template' => 'it_support_templates/test_group.docx',
            'fields'        => ['TenCanBo', 'TenThietBi', 'NoiDungYeuCau'],
        ]);

        $response = $this->get(route('user.it_support.templates.show', $template->id));
        $response->assertOk();
        $response->assertSee('Thông tin cán bộ & Đơn vị');
        $response->assertSee('Thiết bị & Hệ thống');
        $response->assertSee('Nội dung sự cố & Đề nghị');
        $response->assertSee('TenCanBo');
    }
}
