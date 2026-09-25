<?php

namespace Tests\Feature;

use App\Models\ItSupportRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItSupportWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'it_support_testing', 'database.connections.it_support_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('it_support_testing');
        Storage::fake('local');
        Storage::fake('public');
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_name');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
        });
        Schema::create('it_support_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('status')->default('pending');
            $table->json('attachment_paths')->nullable();
            $table->timestamps();
        });
        (require base_path('database/migrations/main/2026_09_23_120000_upgrade_it_support_requests.php'))->up();
        DB::table('branches')->insert(['id' => 1, 'branch_name' => 'Chi nhánh thử nghiệm']);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Lan', 'branch_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Minh', 'branch_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_ticket_file_is_private_and_archived_by_completion_date(): void
    {
        $this->withSession(['user_id' => 1, 'user_name' => 'Lan'])->post(route('user.it_support.store'), [
            'title' => 'Máy in không hoạt động',
            'description' => 'Máy in tại quầy 1 báo lỗi kết nối từ sáng nay.',
            'category' => 'Phần cứng',
            'attachments' => [UploadedFile::fake()->create('loi.pdf', 8, 'application/pdf')],
        ])->assertRedirect();
        $ticket = ItSupportRequest::firstOrFail();
        $this->withSession(['user_id' => 1, 'user_name' => 'Lan'])
            ->get(route('user.it_support.index'))->assertOk()->assertSee('Máy in không hoạt động');
        $this->get(route('user.it_support.index', ['status' => 'pending']))
            ->assertOk()->assertSee('Máy in không hoạt động');
        $this->get(route('user.it_support.index', ['status' => 'resolved']))
            ->assertOk()->assertDontSee('Máy in không hoạt động');
        $this->get(route('user.it_support.create'))->assertOk()->assertSee('Mô tả sự cố');
        $this->get(route('user.it_support.show', $ticket))->assertOk()->assertSee('Trao đổi và lịch sử xử lý');
        $this->withSession(['admin_id' => 2, 'admin_name' => 'IT Minh'])
            ->get(route('admin.it_support.manage'))->assertOk()->assertSee('Máy in không hoạt động');
        $this->get(route('admin.it_support.manage', ['status' => 'pending']))
            ->assertOk()->assertSee('Máy in không hoạt động');
        $this->get(route('admin.it_support.show', $ticket))->assertOk()->assertSee('Phản hồi và cập nhật');
        $file = $ticket->attachmentFiles()[0];
        $this->assertStringStartsWith('it-support/CHO-XU-LY/PHIEU-'.$ticket->id.'/', $file['path']);
        Storage::disk('local')->assertExists($file['path']);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->withSession(['user_id' => 2])->get(route('user.it_support.show', $ticket))->assertNotFound();
        $this->withSession(['user_id' => 2])->get(route('user.it_support.download', [$ticket, 0]))->assertNotFound();
        $this->withSession(['user_id' => 1])->get(route('user.it_support.download', [$ticket, 0]))->assertOk();

        foreach ([route('user.it_support.index'), route('admin.it_support.manage')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
            $tabs = (new \DOMXPath($dom))->query('//nav[contains(@class,"it-tabs")]/a');
            $this->assertSame(3, $tabs->length);
            $this->assertSame(['Chờ tiếp nhận 1', 'Đang xử lý 0', 'Đã xử lý 0'], array_map(
                fn ($tab) => trim(preg_replace('/\s+/u', ' ', $tab->textContent)), iterator_to_array($tabs)
            ));
        }

        $this->withSession(['admin_id' => 2, 'admin_name' => 'IT Minh'])
            ->post(route('admin.it_support.update_status', $ticket), [
                'status' => 'resolved', 'message' => 'Đã cài lại trình điều khiển máy in.',
            ])->assertRedirect(route('admin.it_support.show', $ticket));
        $ticket->refresh();
        $this->assertNotNull($ticket->completed_at);
        $this->assertStringContainsString('/NAM '.$ticket->completed_at->format('Y').'/THANG '.$ticket->completed_at->format('m-Y').'/NGAY '.$ticket->completed_at->format('d-m-Y').'/PHIEU-'.$ticket->id.'/', $ticket->attachmentFiles()[0]['path']);
        Storage::disk('local')->assertExists($ticket->attachmentFiles()[0]['path']);
        Storage::disk('local')->assertMissing($file['path']);
        $manifest = dirname($ticket->attachmentFiles()[0]['path']).'/PHIEU-'.$ticket->id.'.json';
        Storage::disk('local')->assertExists($manifest);
        $archive = json_decode(Storage::disk('local')->get($manifest), true);
        $this->assertSame('Đã cài lại trình điều khiển máy in.', $archive['resolution_note']);
        $this->assertCount(2, $archive['history']);
        $this->get(route('user.it_support.index', ['status' => 'resolved']))
            ->assertOk()->assertSee('Máy in không hoạt động')->assertSee('Đã xử lý');
        $this->withSession(['user_id' => 1, 'user_name' => 'Lan'])
            ->post(route('user.it_support.reply', $ticket), ['message' => 'Đã in thử thành công, cảm ơn IT.'])
            ->assertRedirect();
        $this->assertCount(3, json_decode(Storage::disk('local')->get($manifest), true)['history']);
    }

    public function test_completion_requires_result_and_legacy_files_can_be_secured(): void
    {
        Storage::disk('public')->put('it_supports/legacy.pdf', 'old-file');
        $ticket = ItSupportRequest::create([
            'user_id' => 1, 'title' => 'Phiếu cũ', 'status' => 'pending',
            'attachment_paths' => ['it_supports/legacy.pdf'],
        ]);
        $this->withSession(['admin_id' => 2])->post(route('admin.it_support.update_status', $ticket), [
            'status' => 'resolved',
        ])->assertSessionHasErrors('message');
        $this->assertNull($ticket->fresh()->completed_at);

        $this->artisan('it-support:secure-archive', ['--execute' => true])->assertExitCode(0);
        $ticket->refresh();
        $this->assertStringStartsWith('it-support/CHO-XU-LY/PHIEU-'.$ticket->id.'/', $ticket->attachmentFiles()[0]['path']);
        Storage::disk('public')->assertMissing('it_supports/legacy.pdf');
        Storage::disk('local')->assertExists($ticket->attachmentFiles()[0]['path']);
    }

    public function test_ticket_without_files_still_gets_a_dated_archive_record(): void
    {
        $ticket = ItSupportRequest::create([
            'user_id' => 1, 'title' => 'Không vào được ứng dụng',
            'description' => 'Không thể mở ứng dụng giao dịch.', 'status' => 'pending',
        ]);
        $this->withSession(['admin_id' => 2, 'admin_name' => 'IT Minh'])
            ->post(route('admin.it_support.update_status', $ticket), [
                'status' => 'resolved', 'message' => 'Đã khởi động lại dịch vụ.',
            ])->assertRedirect();
        $ticket->refresh();
        $path = app(\App\Services\ItSupportStorage::class)->archiveDirectory($ticket, $ticket->completed_at)
            .'/PHIEU-'.$ticket->id.'.json';
        Storage::disk('local')->assertExists($path);
        $this->assertSame('Không vào được ứng dụng', json_decode(Storage::disk('local')->get($path), true)['title']);
        $day = $ticket->completed_at->format('Y-m-d');
        $this->get(route('admin.it_support.manage', ['status' => 'resolved', 'completed_from' => $day, 'completed_to' => $day]))
            ->assertOk()->assertSee('Không vào được ứng dụng');
        $this->get(route('admin.it_support.manage', ['status' => 'resolved', 'completed_from' => $ticket->completed_at->copy()->addDay()->format('Y-m-d')]))
            ->assertOk()->assertDontSee('Không vào được ứng dụng');
        $this->post(route('admin.it_support.update_status', $ticket), ['status' => 'processing', 'message' => 'Mở lại'])
            ->assertSessionHasErrors('status');
    }

    public function test_old_closed_tickets_appear_under_processed_without_allowing_new_closed_tickets(): void
    {
        $closed = ItSupportRequest::create([
            'user_id' => 1, 'title' => 'Phiếu đã đóng trước đây', 'status' => 'closed', 'completed_at' => now(),
        ]);
        $pending = ItSupportRequest::create([
            'user_id' => 1, 'title' => 'Phiếu mới', 'status' => 'pending',
        ]);
        $this->withSession(['user_id' => 1, 'admin_id' => 2]);

        $this->get(route('user.it_support.index', ['status' => 'resolved']))
            ->assertOk()->assertSee('Phiếu đã đóng trước đây')->assertDontSee('Phiếu mới');
        $this->get(route('user.it_support.index', ['status' => 'closed']))
            ->assertOk()->assertSee('Phiếu đã đóng trước đây')->assertDontSee('Phiếu mới');
        $this->get(route('admin.it_support.manage', ['status' => 'resolved']))
            ->assertOk()->assertSee('Phiếu đã đóng trước đây')->assertDontSee('Phiếu mới');
        $this->assertSame('Đã xử lý', $closed->statusLabel());
        $this->post(route('admin.it_support.update_status', $pending), [
            'status' => 'closed', 'message' => 'Thử đóng phiếu',
        ])->assertSessionHasErrors('status');
        $this->assertSame('pending', $pending->fresh()->status);
    }

    public function test_ticket_lists_filter_by_scope_branch_category_dates_and_order(): void
    {
        DB::table('branches')->insert(['id' => 2, 'branch_name' => 'Chi nhánh khác']);
        DB::table('users')->insert(['id' => 3, 'name' => 'Hùng', 'branch_id' => 2, 'created_at' => now(), 'updated_at' => now()]);
        $tickets = [
            ['user_id' => 1, 'title' => 'Máy in ở chi nhánh một', 'category' => 'Phần cứng', 'status' => 'pending', 'created_at' => '2026-09-01 09:00:00'],
            ['user_id' => 3, 'title' => 'Mạng ở chi nhánh hai', 'category' => 'Mạng', 'status' => 'processing', 'created_at' => '2026-09-02 10:00:00'],
            ['user_id' => 1, 'title' => 'Phần mềm đã xong', 'category' => 'Phần mềm', 'status' => 'resolved', 'created_at' => '2026-08-30 11:00:00', 'completed_at' => '2026-09-03 12:00:00'],
            ['user_id' => 3, 'title' => 'Phiếu cũ đã đóng', 'category' => 'Khác', 'status' => 'closed', 'created_at' => '2026-08-31 11:00:00', 'completed_at' => '2026-09-04 12:00:00'],
        ];
        foreach ($tickets as $ticket) {
            DB::table('it_support_requests')->insert($ticket + ['updated_at' => $ticket['created_at']]);
        }
        $this->withSession(['admin_id' => 2, 'user_id' => 1]);

        $this->get(route('admin.it_support.manage'))->assertOk()
            ->assertSee('Máy in ở chi nhánh một')->assertSee('Phiếu cũ đã đóng')
            ->assertSee('Tất cả phiếu')->assertSee('Đang xử lý');
        $this->get(route('admin.it_support.manage', ['status' => 'open']))->assertOk()
            ->assertSee('Máy in ở chi nhánh một')->assertSee('Mạng ở chi nhánh hai')
            ->assertDontSee('Phần mềm đã xong')->assertDontSee('Phiếu cũ đã đóng');
        $this->get(route('admin.it_support.manage', [
            'status' => 'all', 'branch_id' => '1', 'category' => 'Phần cứng',
            'date_from' => '2026-09-01', 'date_to' => '2026-09-01',
        ]))->assertOk()->assertSee('Máy in ở chi nhánh một')
            ->assertDontSee('Mạng ở chi nhánh hai')->assertDontSee('Phần mềm đã xong');
        $this->get(route('admin.it_support.manage', [
            'status' => 'resolved', 'date_type' => 'processed',
            'date_from' => '2026-09-04', 'date_to' => '2026-09-04',
        ]))->assertOk()->assertSee('Phiếu cũ đã đóng')->assertDontSee('Phần mềm đã xong');

        $html = $this->get(route('admin.it_support.manage', ['status' => 'open', 'sort' => 'oldest']))
            ->assertOk()->getContent();
        $this->assertTrue(strpos($html, 'Máy in ở chi nhánh một') < strpos($html, 'Mạng ở chi nhánh hai'));

        $this->get(route('user.it_support.index'))->assertOk()
            ->assertSee('Máy in ở chi nhánh một')->assertSee('Phần mềm đã xong')
            ->assertDontSee('Mạng ở chi nhánh hai');
        $this->get(route('user.it_support.index', [
            'status' => 'all', 'category' => 'Phần mềm',
            'date_from' => '2026-08-30', 'date_to' => '2026-08-30',
        ]))->assertOk()->assertSee('Phần mềm đã xong')->assertDontSee('Máy in ở chi nhánh một');
    }

    public function test_new_pages_load_navigation_plugins_once_and_in_dependency_order(): void
    {
        $ticket = ItSupportRequest::create(['user_id' => 1, 'title' => 'Kiểm tra menu', 'status' => 'pending']);
        $this->withSession(['user_id' => 1, 'user_name' => 'Lan', 'admin_id' => 2, 'admin_name' => 'IT Minh']);
        $urls = [
            route('user.it_support.index'),
            route('user.it_support.create'),
            route('user.it_support.show', $ticket),
            route('admin.it_support.manage'),
            route('admin.it_support.show', $ticket),
            route('admin.geography.index'),
        ];
        foreach ($urls as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            foreach (['js/ajax/libs/jquery/3.6.0/jquery.min.js', 'bootstrap.bundle.min.js', 'jquery.easing.min.js', 'sb-admin-2.min.js'] as $script) {
                $this->assertSame(1, substr_count($html, $script), "{$url}: {$script} must load exactly once");
            }
            $this->assertTrue(strpos($html, 'jquery.min.js') < strpos($html, 'bootstrap.bundle.min.js'));
            $this->assertTrue(strpos($html, 'bootstrap.bundle.min.js') < strpos($html, 'sb-admin-2.min.js'));
            if (str_contains($html, 'it-support-upload.js')) {
                $this->assertTrue(strpos($html, 'sb-admin-2.min.js') < strpos($html, 'it-support-upload.js'));
            }
        }
    }

    public function test_user_document_navigation_uses_its_own_icon(): void
    {
        $this->withSession(['user_id' => 1, 'user_name' => 'Lan']);
        $html = $this->get(route('user.it_support.index'))->assertOk()->getContent();
        $this->assertStringContainsString('css/shared/shell-theme.css', $html);

        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//a[@data-target="#collapseDocuments"]/i[contains(@class,"fa-file-alt")]')->length);
        $this->assertSame(1, $xpath->query('//a[@data-target="#collapseOne"]/svg[contains(@class,"bi-database-fill")]')->length);
        $this->assertStringContainsString('.user-sidebar .nav-item .nav-link > i', file_get_contents(public_path('css/user/user-experience.css')));
    }

    public function test_processing_sidebar_link_matches_the_processing_tab_on_both_sides(): void
    {
        ItSupportRequest::create(['user_id' => 1, 'title' => 'Phiếu đang chờ', 'status' => 'pending']);
        ItSupportRequest::create(['user_id' => 1, 'title' => 'Phiếu đang được xử lý', 'status' => 'processing']);
        $this->withSession(['user_id' => 1, 'admin_id' => 2]);

        foreach ([
            [route('user.it_support.index', ['status' => 'processing']), 'collapseItSupport'],
            [route('admin.it_support.manage', ['status' => 'processing']), 'collapseAdminItSupport'],
        ] as [$url, $sidebarId]) {
            $html = $this->get($url)->assertOk()
                ->assertSee('Phiếu đang được xử lý')->assertDontSee('Phiếu đang chờ')
                ->getContent();
            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="UTF-8"'.$html);
            $xpath = new \DOMXPath($dom);
            $sidebarLink = $xpath->query("//div[@id='{$sidebarId}']//a[contains(@class,'collapse-item') and normalize-space(.)='Đang xử lý']");
            $this->assertSame(1, $sidebarLink->length);
            $this->assertSame($url, $sidebarLink->item(0)->getAttribute('href'));
            $this->assertStringContainsString('active', $sidebarLink->item(0)->getAttribute('class'));
            $this->assertSame(1, $xpath->query("//nav[contains(@class,'it-tabs')]/a[contains(@class,'active') and contains(normalize-space(.),'Đang xử lý')]")->length);
        }
    }
}
