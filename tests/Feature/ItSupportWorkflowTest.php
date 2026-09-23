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
        $this->get(route('user.it_support.index', ['status' => ['pending', 'processing']]))
            ->assertOk()->assertSee('Máy in không hoạt động');
        $this->get(route('user.it_support.index', ['status' => ['resolved', 'closed']]))
            ->assertOk()->assertDontSee('Máy in không hoạt động');
        $this->get(route('user.it_support.create'))->assertOk()->assertSee('Mô tả sự cố');
        $this->get(route('user.it_support.show', $ticket))->assertOk()->assertSee('Trao đổi và lịch sử xử lý');
        $this->withSession(['admin_id' => 2, 'admin_name' => 'IT Minh'])
            ->get(route('admin.it_support.manage'))->assertOk()->assertSee('Máy in không hoạt động');
        $this->get(route('admin.it_support.manage', ['status' => ['pending', 'processing']]))
            ->assertOk()->assertSee('Máy in không hoạt động');
        $this->get(route('admin.it_support.show', $ticket))->assertOk()->assertSee('Phản hồi và cập nhật');
        $file = $ticket->attachmentFiles()[0];
        $this->assertStringStartsWith('it-support/CHO-XU-LY/PHIEU-'.$ticket->id.'/', $file['path']);
        Storage::disk('local')->assertExists($file['path']);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->withSession(['user_id' => 2])->get(route('user.it_support.show', $ticket))->assertNotFound();
        $this->withSession(['user_id' => 2])->get(route('user.it_support.download', [$ticket, 0]))->assertNotFound();
        $this->withSession(['user_id' => 1])->get(route('user.it_support.download', [$ticket, 0]))->assertOk();

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
        $this->get(route('admin.it_support.manage', ['completed_from' => $day, 'completed_to' => $day]))
            ->assertOk()->assertSee('Không vào được ứng dụng');
        $this->get(route('admin.it_support.manage', ['completed_from' => $ticket->completed_at->copy()->addDay()->format('Y-m-d')]))
            ->assertOk()->assertDontSee('Không vào được ứng dụng');
        $this->post(route('admin.it_support.update_status', $ticket), ['status' => 'processing', 'message' => 'Mở lại'])
            ->assertSessionHasErrors('status');
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
            route('admin_merger_data_import_view'),
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
}
