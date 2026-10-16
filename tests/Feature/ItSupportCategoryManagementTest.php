<?php

namespace Tests\Feature;

use App\Models\ItSupportCategory;
use App\Models\ItSupportFormField;
use App\Models\ItSupportTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItSupportCategoryManagementTest extends TestCase
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

    public function test_categories_table_has_initial_seeded_data(): void
    {
        $this->assertGreaterThanOrEqual(5, ItSupportCategory::count());

        $this->assertDatabaseHas('it_support_categories', [
            'name' => 'Hệ thống IPCAS',
            'icon' => 'fas fa-university',
        ]);

        $this->assertDatabaseHas('it_support_categories', [
            'name' => 'Các phần mềm nghiệp vụ',
            'icon' => 'fas fa-cubes',
        ]);

        $this->assertDatabaseHas('it_support_categories', [
            'name' => 'Chứng thư số PKI',
            'icon' => 'fas fa-key',
        ]);
    }

    public function test_admin_can_view_category_list(): void
    {
        $this->withSession(['admin_id' => 1, 'user_id' => 1]);

        $response = $this->get(route('admin.it_support.categories.index'));

        $response->assertOk();
        $response->assertSee('Danh mục Mẫu biểu IT');
        $response->assertSee('Hệ thống IPCAS');
        $response->assertSee('Các phần mềm nghiệp vụ');
        $response->assertSee('vendor/datatables/jquery.dataTables.min.js');
        $response->assertSee('vendor/datatables/dataTables.bootstrap4.min.js');
    }

    public function test_admin_can_create_update_and_delete_category(): void
    {
        $this->withSession(['admin_id' => 1, 'user_id' => 1]);

        // 1. Create
        $response = $this->post(route('admin.it_support.categories.store'), [
            'name'          => 'Sao lưu & Cơ sở dữ liệu',
            'description'   => 'Dành cho các yêu cầu trích xuất dữ liệu, backup và phục hồi hệ thống.',
            'icon'          => 'fas fa-database',
            'display_order' => 60,
            'status'        => 'active',
        ]);

        $response->assertRedirect(route('admin.it_support.categories.index'));
        $this->assertDatabaseHas('it_support_categories', [
            'name' => 'Sao lưu & Cơ sở dữ liệu',
            'icon' => 'fas fa-database',
        ]);

        $category = ItSupportCategory::where('name', 'Sao lưu & Cơ sở dữ liệu')->first();
        $this->assertNotNull($category);

        // 2. Edit JSON
        $editResponse = $this->getJson(route('admin.it_support.categories.edit', $category->id));
        $editResponse->assertOk();
        $editResponse->assertJson(['status' => true]);
        $this->assertSame('Sao lưu & Cơ sở dữ liệu', $editResponse->json('category.name'));

        // 3. Update
        $updateResponse = $this->post(route('admin.it_support.categories.update', $category->id), [
            'name'          => 'Sao lưu & Cơ sở dữ liệu Cập Nhật',
            'description'   => 'Mô tả đã cập nhật.',
            'icon'          => 'fas fa-server',
            'display_order' => 65,
            'status'        => 'active',
        ]);

        $updateResponse->assertRedirect(route('admin.it_support.categories.index'));
        $this->assertDatabaseHas('it_support_categories', [
            'id'   => $category->id,
            'name' => 'Sao lưu & Cơ sở dữ liệu Cập Nhật',
            'icon' => 'fas fa-server',
        ]);

        // 4. Create a template linked to this category
        $template = ItSupportTemplate::create([
            'name'          => 'Biểu mẫu Test Phân loại',
            'category_id'   => $category->id,
            'file_template' => 'it_support_templates/test.docx',
            'fields'        => ['TenCanBo'],
        ]);

        $this->assertSame($category->id, $template->fresh()->category_id);
        $this->assertSame('Sao lưu & Cơ sở dữ liệu Cập Nhật', $template->fresh()->category->name);

        // 5. Delete category: verify safe cascade (category_id becomes null, template is NOT deleted)
        $deleteResponse = $this->delete(route('admin.it_support.categories.destroy', $category->id));
        $deleteResponse->assertRedirect(route('admin.it_support.categories.index'));

        $this->assertDatabaseMissing('it_support_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('it_support_templates', ['id' => $template->id]);
        $this->assertNull($template->fresh()->category_id);
    }

    public function test_admin_can_save_template_with_category(): void
    {
        Storage::fake('public');
        $this->withSession(['admin_id' => 1, 'user_id' => 1]);

        $category = ItSupportCategory::first();

        $file = UploadedFile::fake()->create('test_cat_template.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->post(route('admin.it_support.templates.store'), [
            'name'            => 'Phiếu Đề Nghị Cấp Phép Điển Hình',
            'category_id'     => $category->id,
            'form_file'       => $file,
            'selected_fields' => ['TenCanBo', 'SoDienThoai'],
        ]);

        $response->assertRedirect(route('admin.it_support.templates'));
        $this->assertDatabaseHas('it_support_templates', [
            'name'        => 'Phiếu Đề Nghị Cấp Phép Điển Hình',
            'category_id' => $category->id,
        ]);
    }

    public function test_user_can_view_templates_list_with_category_tabs_and_scope_banner(): void
    {
        $category = ItSupportCategory::first();

        $template = ItSupportTemplate::create([
            'name'          => 'Phiếu Hỗ Trợ Core Banking IPCAS',
            'category_id'   => $category->id,
            'file_template' => 'it_support_templates/ipcas.docx',
            'fields'        => ['TenCanBo'],
        ]);

        $this->actingAs($this->adminUser);
        $this->withSession(['user_id' => $this->adminUser->id]);

        $response = $this->get(route('user.it_support.templates.index'));

        $response->assertOk();
        $response->assertSee('Mẫu biểu Yêu cầu Hỗ trợ IT');
        $response->assertSee('Phiếu Hỗ Trợ Core Banking IPCAS');
        $response->assertSee($category->name);
        $response->assertSee('it-category-pill', false);
        $response->assertSee('scopeDescriptionBanner', false);
        $response->assertSee('itTemplateSearchInput', false);
    }
}
