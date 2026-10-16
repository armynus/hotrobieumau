<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\DocumentRead;
use App\Models\DocumentTransfer;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DocumentIncomingPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'incoming_page_testing', 'database.connections.incoming_page_testing' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        DB::purge('incoming_page_testing');

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_name')->default('CN Test');
            $table->string('branch_type')->default('type_1');
            $table->string('status')->default('active');
        });
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->integer('level')->default(10);
            $table->string('position_name')->default('Nhân viên');
        });
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('department_name')->default('Phòng Test');
            $table->unsignedBigInteger('branch_id');
            $table->string('status')->default('active');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->string('document_role')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('status')->default('active');
        });
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('direction', 20);
            $table->string('registry_number')->nullable();
            $table->string('document_code')->nullable();
            $table->text('title')->nullable();
            $table->unsignedBigInteger('document_type_id')->nullable();
            $table->unsignedBigInteger('managing_branch_id');
            $table->string('visibility', 20);
            $table->date('issued_date')->nullable();
            $table->date('received_date')->nullable();
            $table->date('forwarded_date')->nullable();
            $table->string('issuing_agency')->nullable();
            $table->string('signer')->nullable();
            $table->text('recipient')->nullable();
            $table->text('archive_recipient')->nullable();
            $table->integer('copy_count')->nullable();
            $table->string('receipt_signature')->nullable();
            $table->text('notes')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->string('security_level', 20)->default('normal');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_extension')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
        Schema::create('document_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('from_branch_id')->nullable();
            $table->unsignedBigInteger('to_branch_id')->nullable();
            $table->unsignedBigInteger('to_department_id')->nullable();
            $table->unsignedBigInteger('to_user_id')->nullable();
            $table->unsignedBigInteger('transferred_by')->nullable();
            $table->dateTime('transferred_at')->nullable();
            $table->text('note')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });
        Schema::create('document_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->string('target_type', 20);
            $table->unsignedBigInteger('target_id');
            $table->string('permission', 20)->default('view');
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->dateTime('granted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('document_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('user_id');
            $table->dateTime('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('document_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 50);
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    public function test_incoming_documents_page_requires_authentication(): void
    {
        $response = $this->get('/documents/incoming');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_incoming_documents_page(): void
    {
        $branchId = DB::table('branches')->insertGetId(['branch_name' => 'CN 1', 'branch_type' => 'type_1', 'status' => 'active']);
        $deptId = DB::table('departments')->insertGetId(['department_name' => 'Phòng Tổng hợp', 'branch_id' => $branchId, 'status' => 'active']);
        $userId = DB::table('users')->insertGetId([
            'name' => 'Nguyễn Văn A',
            'branch_id' => $branchId,
            'department_id' => $deptId,
        ]);

        $response = $this->withSession(['user_id' => $userId, 'user_name' => 'Nguyễn Văn A'])
            ->get('/documents/incoming');

        $response->assertStatus(200);
        $response->assertViewIs('user.page.document_incoming');
        $response->assertSee('Văn bản đến');
        $response->assertSee('Chưa đọc');
        $response->assertSee('document-date-presets', false);
        $response->assertSee('Hôm nay');
        $response->assertSee('7 ngày');
        $response->assertSee('Tháng này');
        $response->assertSee('Xóa ngày');
    }

    public function test_incoming_stats_api_returns_unread_and_read_counts(): void
    {
        $branchId = DB::table('branches')->insertGetId(['branch_name' => 'CN 1', 'branch_type' => 'type_1', 'status' => 'active']);
        $deptId = DB::table('departments')->insertGetId(['department_name' => 'Phòng Kế toán', 'branch_id' => $branchId, 'status' => 'active']);
        $userId = DB::table('users')->insertGetId([
            'name' => 'Trần Thị B',
            'branch_id' => $branchId,
            'department_id' => $deptId,
        ]);

        // Doc 1: incoming direction, distributed to department, unread
        $doc1 = DB::table('documents')->insertGetId([
            'direction' => 'incoming',
            'document_code' => '10/CV-DEN',
            'title' => 'Công văn đến số 10',
            'managing_branch_id' => $branchId,
            'visibility' => 'public',
            'issued_date' => '2026-10-01',
            'received_date' => '2026-10-01',
        ]);
        DB::table('document_permissions')->insert([
            'document_id' => $doc1,
            'target_type' => 'department',
            'target_id' => $deptId,
            'permission' => 'view',
        ]);

        // Doc 2: outgoing direction, but transferred to department, marked as read
        $doc2 = DB::table('documents')->insertGetId([
            'direction' => 'outgoing',
            'document_code' => '20/CV-DI',
            'title' => 'Công văn đi chuyển đến phòng',
            'managing_branch_id' => $branchId,
            'visibility' => 'public',
            'issued_date' => '2026-10-01',
        ]);
        DB::table('document_permissions')->insert([
            'document_id' => $doc2,
            'target_type' => 'department',
            'target_id' => $deptId,
            'permission' => 'view',
        ]);
        DB::table('document_transfers')->insert([
            'document_id' => $doc2,
            'from_branch_id' => $branchId,
            'to_department_id' => $deptId,
            'status' => 'received',
            'transferred_at' => now(),
        ]);
        DB::table('document_reads')->insert([
            'document_id' => $doc2,
            'user_id' => $userId,
            'read_at' => now(),
        ]);

        $response = $this->withSession(['user_id' => $userId])
            ->getJson('/api/documents/incoming-stats');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'total' => 2,
            'unread' => 1,
            'read' => 1,
        ]);
    }

    public function test_api_documents_with_scope_incoming_and_mark_read(): void
    {
        $branchId = DB::table('branches')->insertGetId(['branch_name' => 'CN 1', 'branch_type' => 'type_1', 'status' => 'active']);
        $deptId = DB::table('departments')->insertGetId(['department_name' => 'Phòng IT', 'branch_id' => $branchId, 'status' => 'active']);
        $senderId = DB::table('users')->insertGetId([
            'name' => 'Người gửi Văn thư',
            'branch_id' => $branchId,
            'document_role' => 'clerk',
        ]);
        $recipientId = DB::table('users')->insertGetId([
            'name' => 'Lê Văn C',
            'branch_id' => $branchId,
            'department_id' => $deptId,
        ]);

        $docId = DB::table('documents')->insertGetId([
            'direction' => 'incoming',
            'document_code' => '99/TB-NHNO',
            'title' => 'Thông báo quan trọng mới đăng tải',
            'managing_branch_id' => $branchId,
            'visibility' => 'public',
            'issued_date' => '2026-10-01',
            'received_date' => '2026-10-01',
            'created_by' => $senderId,
        ]);
        DB::table('document_permissions')->insert([
            'document_id' => $docId,
            'target_type' => 'department',
            'target_id' => $deptId,
            'permission' => 'view',
        ]);
        DB::table('document_transfers')->insert([
            'document_id' => $docId,
            'from_branch_id' => $branchId,
            'to_department_id' => $deptId,
            'transferred_by' => $senderId,
            'transferred_at' => '2026-10-01 10:00:00',
            'note' => 'Chuyển xử lý gấp',
            'status' => 'received',
        ]);

        // Insert an archive imported document in same branch to ensure it is excluded from scope=incoming
        $archiveDocId = DB::table('documents')->insertGetId([
            'direction' => 'incoming',
            'document_code' => 'ARCHIVE-01',
            'title' => 'Sổ lưu trữ cũ',
            'managing_branch_id' => $branchId,
            'visibility' => 'public',
            'issued_date' => '2025-01-01',
        ]);
        DB::table('document_logs')->insert([
            'document_id' => $archiveDocId,
            'user_id' => $senderId,
            'action' => 'archive_imported',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1. Query incoming documents for recipient: should show is_read = false and sender_info
        $response = $this->withSession(['user_id' => $recipientId])
            ->getJson('/api/documents?scope=incoming');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('recordsTotal'));
        $this->assertEquals(1, $response->json('recordsFiltered'));
        $data = $response->json('data');
        $this->assertEquals($docId, $data[0]['id']);
        $this->assertEquals('99/TB-NHNO', $data[0]['document_code']);
        $this->assertFalse($data[0]['is_read']);
        $this->assertEquals('Người gửi Văn thư', $data[0]['sender_info']['sender_name']);
        $this->assertEquals('Chuyển xử lý gấp', $data[0]['sender_info']['note']);

        // 2. Mark document as read
        $markReadResponse = $this->withSession(['user_id' => $recipientId])
            ->postJson('/api/documents/' . $docId . '/read', ['is_read' => true]);

        $markReadResponse->assertStatus(200);
        $markReadResponse->assertJson(['success' => true, 'is_read' => true]);

        // Verify in database
        $this->assertDatabaseHas('document_reads', [
            'document_id' => $docId,
            'user_id' => $recipientId,
        ]);

        // 3. Query with is_read=1
        $readQueryResponse = $this->withSession(['user_id' => $recipientId])
            ->getJson('/api/documents?scope=incoming&is_read=1');
        $this->assertCount(1, $readQueryResponse->json('data'));
        $this->assertTrue($readQueryResponse->json('data.0.is_read'));

        // 4. Mark document as unread again
        $markUnreadResponse = $this->withSession(['user_id' => $recipientId])
            ->postJson('/api/documents/' . $docId . '/read', ['is_read' => false]);
        $markUnreadResponse->assertStatus(200);
        $markUnreadResponse->assertJson(['success' => true, 'is_read' => false]);
        $this->assertDatabaseMissing('document_reads', [
            'document_id' => $docId,
            'user_id' => $recipientId,
        ]);
    }

    public function test_quick_transfer_supports_bgd_departments_and_branches_simultaneously(): void
    {
        $branch1Id = DB::table('branches')->insertGetId(['branch_name' => 'CN Đồng Tháp', 'branch_type' => 'type_1', 'status' => 'active']);
        $branch2Id = DB::table('branches')->insertGetId(['branch_name' => 'CN Cao Lãnh', 'branch_type' => 'type_2', 'status' => 'active']);

        $directorPosId = DB::table('positions')->insertGetId(['level' => 1, 'position_name' => 'Giám đốc']);
        $staffPosId = DB::table('positions')->insertGetId(['level' => 10, 'position_name' => 'Cán bộ']);

        $deptId = DB::table('departments')->insertGetId(['department_name' => 'Phòng KT&NQ', 'branch_id' => $branch1Id, 'status' => 'active']);

        $clerkId = DB::table('users')->insertGetId([
            'name' => 'Văn thư CN1',
            'branch_id' => $branch1Id,
            'document_role' => 'clerk',
            'position_id' => $staffPosId,
        ]);
        $directorId = DB::table('users')->insertGetId([
            'name' => 'Võ Văn Quốc',
            'branch_id' => $branch1Id,
            'position_id' => $directorPosId,
        ]);

        $docId = DB::table('documents')->insertGetId([
            'direction' => 'incoming',
            'document_code' => 'TRANSFER-MULTI/2026',
            'title' => 'Văn bản chuyển đa điểm',
            'managing_branch_id' => $branch1Id,
            'visibility' => 'normal',
            'created_by' => $clerkId,
            'issued_date' => '2026-10-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession(['user_id' => $clerkId])
            ->postJson('/api/documents/' . $docId . '/transfer', [
                'to_user_ids' => [$directorId],
                'to_department_ids' => [$deptId],
                'to_branch_ids' => [$branch2Id],
                'note' => 'Chuyển đồng thời BGD, phòng ban và chi nhánh loại II',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Check transfers in DB
        $this->assertDatabaseHas('document_transfers', [
            'document_id' => $docId,
            'to_user_id' => $directorId,
            'note' => 'Chuyển đồng thời BGD, phòng ban và chi nhánh loại II',
        ]);
        $this->assertDatabaseHas('document_transfers', [
            'document_id' => $docId,
            'to_department_id' => $deptId,
            'note' => 'Chuyển đồng thời BGD, phòng ban và chi nhánh loại II',
        ]);
        $this->assertDatabaseHas('document_transfers', [
            'document_id' => $docId,
            'to_branch_id' => $branch2Id,
            'note' => 'Chuyển đồng thời BGD, phòng ban và chi nhánh loại II',
        ]);

        // Check show API returns distribution
        $showResponse = $this->withSession(['user_id' => $clerkId])
            ->getJson('/api/documents/' . $docId);
        $showResponse->assertStatus(200);
        $this->assertEquals([$directorId], $showResponse->json('data.distribution.to_user_ids'));
        $this->assertEquals([$deptId], $showResponse->json('data.distribution.to_department_ids'));
        $this->assertEquals([$branch2Id], $showResponse->json('data.distribution.to_branch_ids'));
    }
}
