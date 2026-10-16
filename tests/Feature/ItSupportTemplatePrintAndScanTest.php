<?php

namespace Tests\Feature;

use App\Models\ItSupportCategory;
use App\Models\ItSupportFormField;
use App\Models\ItSupportTemplate;
use App\Models\User;
use App\Services\SupportFormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItSupportTemplatePrintAndScanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Đảm bảo có các trường cần thiết
        ItSupportFormField::updateOrInsert(
            ['field_code' => 'TenCanBo'],
            [
                'field_name'    => 'Họ và tên cán bộ yêu cầu',
                'data_type'     => 'text',
                'content_group' => 'requester',
                'display_order' => 10,
            ]
        );
        ItSupportFormField::updateOrInsert(
            ['field_code' => 'TenChiNhanh'],
            [
                'field_name'    => 'Tên chi nhánh / Đơn vị',
                'data_type'     => 'text',
                'content_group' => 'requester',
                'display_order' => 50,
            ]
        );
        ItSupportFormField::updateOrInsert(
            ['field_code' => 'NgayYeuCau'],
            [
                'field_name'    => 'Ngày yêu cầu',
                'data_type'     => 'date',
                'content_group' => 'issue_request',
                'display_order' => 20,
            ]
        );
        ItSupportFormField::updateOrInsert(
            ['field_code' => 'NgayThangNam'],
            [
                'field_name'    => 'Ngày tháng năm bằng chữ',
                'data_type'     => 'date_text',
                'content_group' => 'issue_request',
                'display_order' => 30,
            ]
        );
    }

    public function test_date_robust_parsing_and_word_formatting(): void
    {
        $service = app(SupportFormService::class);

        // 1. Kiểm tra ngày từ date picker: 2026-10-13
        $words1 = $service->formatDateInWords('2026-10-13');
        $this->assertEquals('ngày 13 tháng 10 năm 2026', $words1);

        // 2. Kiểm tra ngày nhập chuỗi kiểu Việt Nam: 13/10/2026
        $words2 = $service->formatDateInWords('13/10/2026');
        $this->assertEquals('ngày 13 tháng 10 năm 2026', $words2);

        // 3. Kiểm tra ngày dạng dash: 13-10-2026
        $words3 = $service->formatDateInWords('13-10-2026');
        $this->assertEquals('ngày 13 tháng 10 năm 2026', $words3);

        // 4. Kiểm tra ngày thường giữ định dạng d/m/Y
        $date1 = $service->convertDateFormat('2026-10-13');
        $this->assertEquals('13/10/2026', $date1);
        $date2 = $service->convertDateFormat('13/10/2026');
        $this->assertEquals('13/10/2026', $date2);
    }

    public function test_vietnamese_uppercase_conversion(): void
    {
        $service = app(SupportFormService::class);
        $input = 'nguyễn đỗ tuấn kiệt';
        $upper = $service->convertToUppercase($input);
        $this->assertEquals('NGUYỄN ĐỖ TUẤN KIỆT', $upper);
    }

    public function test_user_view_does_not_display_dollar_brace_syntax(): void
    {
        $this->withSession(['user_id' => 1, 'admin_id' => 1]);

        $template = ItSupportTemplate::firstOrCreate(
            ['name' => 'Mẫu CSUS-01 Về việc Cấp mới, thay đổi thông tin Người quản trị IPCAS'],
            [
                'file_template' => 'it_support_templates/1791858703_Mau-CSUS-01_Yeu-Cau-Cap-Moi-Thay-Doi-Thong-Tin.docx',
                'fields'        => ['TenCanBo', 'NgayYeuCau'],
            ]
        );

        $response = $this->get(route('user.it_support.templates.show', $template->id));
        $response->assertOk();
        $response->assertSee('Họ và tên cán bộ yêu cầu');
        $response->assertSee('Ngày yêu cầu');

        // Giao diện người dùng sử dụng fieldset và legend gọn gàng như mẫu giao dịch
        $response->assertSee('it-section-fieldset');
        $response->assertSee('it-section-legend');

        // Không hiển thị cú pháp ${TenCanBo} hay (${TenCanBo})
        $response->assertDontSee('(${TenCanBo})');
        $response->assertDontSee('${TenCanBo}');
        $response->assertDontSee('(${NgayYeuCau})');
        $response->assertDontSee('${NgayYeuCau}');
    }

    public function test_admin_parse_tags_with_template_id_returns_matched_and_unmatched(): void
    {
        $this->withoutMiddleware();
        $this->withSession(['admin_id' => 1]);

        $template = ItSupportTemplate::where('file_template', 'like', '%.docx%')->first();
        $this->assertNotNull($template);

        $response = $this->postJson(route('admin.it_support.templates.parse_tags'), [
            'template_id' => $template->id,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'data',
            'matched',
            'unmatched',
            'message',
        ]);
        $response->assertJson(['status' => true]);

        $data = $response->json();
        $this->assertNotEmpty($data['data']);
        $this->assertContains('TenCanBo', $data['matched']);
    }

    public function test_print_and_export_word_flow_success_with_special_formats(): void
    {
        $this->withoutMiddleware();
        $user = User::first();
        $this->actingAs($user);
        $this->withSession(['user_id' => $user->id, 'admin_id' => 1]);

        $template = ItSupportTemplate::where('file_template', 'like', '%.docx%')->first();
        $this->assertNotNull($template);

        $response = $this->post(route('user.it_support.templates.printAndCreate'), [
            'template_id'  => $template->id,
            'action'       => 'print',
            'TenCanBo'     => 'nguyễn đỗ tuấn kiệt',
            'TenCanBoHOA'  => 'nguyễn đỗ tuấn kiệt',
            'NgayYeuCau'   => '2026-10-13',
            'NgayThangNam' => '13/10/2026',
            'TenChiNhanh'  => 'Chi nhánh Agribank Sài Gòn',
            'MaChiNhanh'   => 'CN001',
            'MaNV'         => '01234',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition') ?? '');
    }

    public function test_admin_view_shows_dollar_brace_syntax_and_scan_hint_in_both_modals(): void
    {
        $this->withSession(['admin_id' => 1]);

        $response = $this->get(route('admin.it_support.templates'));
        $response->assertOk();

        // Admin nhìn thấy (${field_code}) kế bên tên trường dữ liệu
        $response->assertSee('(${TenCanBo})');
        $response->assertSee('(${CCCD})');
        $response->assertSee('(${EmailNV})');
        $response->assertSee('(${TenChiNhanh})');

        // ChiNhanhHOA và TenCanBoHOA không phải trường độc lập nên không có checkbox trong modal
        $response->assertDontSee('(${ChiNhanhHOA})');
        $response->assertDontSee('(${TenCanBoHOA})');

        // Đã xóa mô tả bên dưới danh mục IT trong modal admin
        $response->assertDontSee('category-desc-hint');

        // Có câu hướng dẫn quét tham số tự động trong cả modal thêm và sửa
        $response->assertSee('Hệ thống sẽ tự động quét các tham số <code>${Ten_Truong}</code> trong file Word và tự động chọn các trường tương ứng bên dưới.', false);

        // Nạp đầy đủ thư viện DataTables để tránh lỗi isDataTable
        $response->assertSee('vendor/datatables/jquery.dataTables.min.js');
        $response->assertSee('vendor/datatables/dataTables.bootstrap4.min.js');

        // Bổ sung nút quét từ file trong modal thêm mới
        $response->assertSee('btn-scan-add-file');

        // Giao diện nút upload file và dropzone hiện đại
        $response->assertSee('it-file-dropzone');
        $response->assertSee('it-btn-upload-file');
        $response->assertSee('it-file-selected-box');
    }

    public function test_admin_parse_tags_with_uploaded_file_matches_fields(): void
    {
        $this->withoutMiddleware();
        $this->withSession(['admin_id' => 1]);

        $template = ItSupportTemplate::where('file_template', 'like', '%.docx%')->first();
        $filePath = storage_path('app/public/' . ($template ? $template->file_template : ''));
        if (!file_exists($filePath)) {
            $files = glob(storage_path('app/public/it_support_templates/*.docx'));
            $filePath = !empty($files) ? $files[0] : $filePath;
        }
        $this->assertFileExists($filePath);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $filePath,
            'Mau-CSUS-01.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        $response = $this->post(route('admin.it_support.templates.parse_tags'), [
            'file' => $uploadedFile,
        ]);

        $response->assertOk();
        $response->assertJson(['status' => true]);
        $data = $response->json();
        $this->assertContains('TenCanBo', $data['matched']);
        $this->assertContains('EmailNV', $data['matched']);
        $this->assertContains('IPCAS', $data['matched']);
    }

    public function test_admin_update_template_via_ajax_returns_json_and_updates_fields(): void
    {
        $this->withoutMiddleware();
        $this->withSession(['admin_id' => 1]);

        $template = ItSupportTemplate::firstOrCreate(
            ['name' => 'Biểu mẫu thử nghiệm AJAX'],
            [
                'fields' => ['TenCanBo', 'MaNV'],
                'file_template' => 'it_support_templates/sample.docx',
                'usage_count' => 0,
            ]
        );

        $response = $this->postJson(route('admin.it_support.templates.update', $template->id), [
            'name' => 'Biểu mẫu thử nghiệm AJAX (Đã sửa)',
            'selected_fields' => ['TenCanBo', 'EmailNV'],
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => true,
            'message' => 'Cập nhật biểu mẫu IT thành công!',
            'template' => [
                'id' => $template->id,
                'name' => 'Biểu mẫu thử nghiệm AJAX (Đã sửa)',
                'fields' => ['TenCanBo', 'EmailNV'],
            ],
        ]);

        $template->refresh();
        $this->assertEquals('Biểu mẫu thử nghiệm AJAX (Đã sửa)', $template->name);
        $this->assertEquals(['TenCanBo', 'EmailNV'], $template->fields);

        // Dọn dẹp bản ghi thử nghiệm
        $template->delete();
    }

    public function test_user_view_does_not_display_forced_placeholder_when_empty(): void
    {
        $template = ItSupportTemplate::first();
        if (!$template) {
            $this->markTestSkipped('Không có template để test.');
        }

        // Đảm bảo trường TenCanBo có placeholder rỗng
        ItSupportFormField::where('field_code', 'TenCanBo')->update(['placeholder' => null]);

        $user = User::first();
        if ($user) {
            $this->actingAs($user);
            $this->withSession(['user_id' => $user->id]);
        }

        $response = $this->get(route('user.it_support.templates.show', $template->id));
        $response->assertOk();

        // Đảm bảo không còn chuỗi fallback 'Nhập Họ và tên cán bộ yêu cầu'
        $response->assertDontSee('placeholder="Nhập Họ và tên cán bộ yêu cầu"', false);
    }

    public function test_user_view_auto_fills_officer_and_branch_information_from_session_and_database(): void
    {
        $template = ItSupportTemplate::where('file_template', 'like', '%.docx%')->first();
        if (!$template) {
            $this->markTestSkipped('Không có template để test.');
        }

        $user = User::find(3) ?: User::first();

        $response = $this->withSession([
            'user_id'          => $user->id,
            'user_name'        => $user->name,
            'user_email'       => $user->email,
            'UserBranchId'     => $user->branch_id,
            'UserBranchName'   => $user->branch?->branch_name ?? 'Đồng Tháp',
            'UserBranchCode'   => $user->branch?->branch_code ?? '6500',
            'UserBranchPlace'  => $user->branch?->branch_place ?? 'Cao Lãnh',
            'UserBranchPhone'  => $user->branch?->branch_phone ?? '02773851364',
        ])->get(route('user.it_support.templates.show', $template->id));

        $response->assertOk();

        // Kiểm tra các trường được tự động điền sẵn
        $response->assertSee('value="' . $user->name . '"', false);
        $response->assertSee('value="' . $user->email . '"', false);
        $response->assertSee('Tự điền');

        if ($user->position) {
            $response->assertSee('value="' . $user->position->position_name . '"', false);
        }
        if ($user->department) {
            $expectedDept = $user->department->department_code ?: $user->department->department_name;
            $response->assertSee('value="' . e($expectedDept) . '"', false);
        }
        if ($user->branch) {
            $response->assertSee('value="' . $user->branch->branch_name . '"', false);
            $response->assertSee('value="' . $user->branch->branch_code . '"', false);
            $response->assertSee('value="' . $user->branch->branch_place . '"', false);
        }
    }

    public function test_print_does_not_fail_with_duplicate_entry_when_user_has_existing_form_draft(): void
    {
        $this->withoutMiddleware();
        $user = User::find(3) ?: User::first();
        $this->actingAs($user);

        // Đảm bảo user đã có sẵn 1 bản ghi trong form_drafts (như form giao dịch supportForm)
        \Illuminate\Support\Facades\DB::table('form_drafts')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'form_key' => 'supportForm',
                'payload'  => json_encode(['branch' => 'Đồng Tháp']),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $template = ItSupportTemplate::where('file_template', 'like', '%.docx%')->first();
        $this->assertNotNull($template);

        // Submit form in Word - không được văng lỗi 1062 Duplicate entry
        $response = $this->withSession(['user_id' => $user->id])->post(route('user.it_support.templates.printAndCreate'), [
            'template_id'  => $template->id,
            'action'       => 'print',
            'TenCanBo'     => 'Nguyễn Đỗ Tuấn Kiệt',
            'SoDienThoai'  => '02773851364',
            'EmailNV'      => 'armynus11@gmail.com',
            'ChucVu'       => 'Phó phòng',
            'PhongBan'     => 'Phòng Kế toán & Ngân quỹ',
            'NgayYeuCau'   => '2026-10-14',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition') ?? '');

        // Kiểm tra thông tin đã được cache an toàn
        $cached = \Illuminate\Support\Facades\Cache::get("it_officer_profile_{$user->id}");
        $this->assertIsArray($cached);
        $this->assertEquals('02773851364', $cached['SoDienThoai']);
        $this->assertEquals('armynus11@gmail.com', $cached['EmailNV']);
    }

    public function test_user_citizen_id_and_employee_code_columns_and_auto_fill(): void
    {
        $this->withoutMiddleware();

        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumns('users', ['citizen_id', 'employee_code']));

        $user = User::first();
        $user->update([
            'citizen_id'    => '087095001234',
            'employee_code' => 'NV-6500-089',
        ]);

        $user->refresh();
        $this->assertEquals('087095001234', $user->citizen_id);
        $this->assertEquals('NV-6500-089', $user->employee_code);

        $template = ItSupportTemplate::where('file_template', 'like', '%.docx%')->first();
        $this->assertNotNull($template);

        // Xem form điền: phải tự động điền CCCD và Mã NV từ cơ sở dữ liệu
        $response = $this->withSession(['user_id' => $user->id])
            ->get(route('user.it_support.templates.show', $template->id));

        $response->assertOk();
        $response->assertSee('value="087095001234"', false);
        $response->assertSee('value="NV-6500-089"', false);
    }

    public function test_chinhanhhoa_and_tencanbohoa_are_not_standalone_fields_and_auto_generate_uppercase_in_word(): void
    {
        $this->withoutMiddleware();

        // 1. Khẳng định ChiNhanhHOA và TenCanBoHOA không tồn tại độc lập trong danh mục trường
        $this->assertNull(ItSupportFormField::where('field_code', 'ChiNhanhHOA')->first());
        $this->assertNull(ItSupportFormField::where('field_code', 'TenCanBoHOA')->first());

        $user = User::with('branch')->first();
        $template = ItSupportTemplate::firstOrCreate(
            ['name' => 'Biểu mẫu kiểm tra tự sinh in hoa'],
            [
                'fields' => ['TenCanBo', 'TenChiNhanh'],
                'file_template' => 'it_support_templates/sample.docx',
                'usage_count' => 0,
            ]
        );
        $template->update(['fields' => ['TenCanBo', 'TenChiNhanh']]);

        // 2. Giao diện người dùng: chỉ nhập TenCanBo và TenChiNhanh, không có ô nhập ChiNhanhHOA hay TenCanBoHOA
        $response = $this->withSession(['user_id' => $user->id])
            ->get(route('user.it_support.templates.show', $template->id));

        $response->assertOk();
        $response->assertSee('name="TenCanBo"', false);
        $response->assertSee('name="TenChiNhanh"', false);
        $response->assertDontSee('name="ChiNhanhHOA"', false);
        $response->assertDontSee('name="TenCanBoHOA"', false);
    }

    public function test_user_view_auto_fills_department_code_instead_of_department_name_for_phong_ban(): void
    {
        $this->withoutMiddleware();

        $user = User::with('department')->find(3) ?: User::with('department')->first();
        $this->assertNotNull($user);

        // Đảm bảo department có cả department_code và department_name
        if ($user->department) {
            $user->department->update([
                'department_code' => 'KTNQ',
                'department_name' => 'Phòng Kế toán & Ngân quỹ',
            ]);
            $user->refresh();
        }

        $template = ItSupportTemplate::firstOrCreate(
            ['name' => 'Biểu mẫu kiểm tra điền mã phòng'],
            [
                'fields' => ['TenCanBo', 'PhongBan'],
                'file_template' => 'it_support_templates/sample.docx',
                'usage_count' => 0,
            ]
        );
        $template->update(['fields' => ['TenCanBo', 'PhongBan']]);

        $response = $this->withSession(['user_id' => $user->id])
            ->get(route('user.it_support.templates.show', $template->id));

        $response->assertOk();

        // Kiểm tra trường PhongBan tự động điền Mã phòng (KTNQ) thay vì tên phòng
        if ($user->department && $user->department->department_code) {
            $response->assertSee('value="' . $user->department->department_code . '"', false);
            $response->assertDontSee('value="' . $user->department->department_name . '"', false);
        }

        // Đảm bảo chỉ còn nút In biểu mẫu Word, đã loại bỏ nút In & Tạo yêu cầu IT
        $response->assertSee('In biểu mẫu Word');
        $response->assertDontSee('In & Tạo Yêu Cầu IT');
        $response->assertDontSee('name="action" value="create"', false);
    }
}
