<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentQueryService;
use App\Services\DocumentRecipientService;
use App\Services\DocumentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DocumentRecipientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Isolated database: never migrate or write to the configured application database.
        config(['database.default' => 'recipient_testing', 'database.connections.recipient_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_type');
            $table->string('status');
        });
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->integer('level');
            $table->string('position_name');
        });
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->integer('branch_id');
            $table->string('status');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('branch_id');
            $table->integer('department_id')->nullable();
            $table->integer('position_id')->nullable();
            $table->string('document_role')->nullable();
            $table->timestamps();
        });
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->integer('created_by');
            $table->integer('managing_branch_id');
            $table->string('visibility');
            $table->timestamps();
        });
        Schema::create('document_transfers', function (Blueprint $table) {
            $table->id();
            $table->integer('document_id');
            $table->integer('from_branch_id');
            $table->integer('to_branch_id')->nullable();
            $table->integer('to_department_id')->nullable();
            $table->integer('to_user_id')->nullable();
            $table->integer('transferred_by');
            $table->timestamp('transferred_at');
            $table->string('status');
            $table->text('note')->nullable();
            $table->timestamps();
        });
        Schema::create('document_permissions', function (Blueprint $table) {
            $table->id();
            $table->integer('document_id');
            $table->string('target_type');
            $table->integer('target_id');
            $table->string('permission');
            $table->integer('granted_by');
            $table->timestamp('granted_at');
            $table->timestamps();
        });
        Schema::create('document_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('document_id');
            $table->integer('user_id');
            $table->string('action');
            $table->text('details');
            $table->timestamps();
        });
        DB::table('branches')->insert([
            ['id' => 1, 'branch_type' => 'type_1', 'status' => 'active'],
            ['id' => 2, 'branch_type' => 'type_2', 'status' => 'active'],
            ['id' => 3, 'branch_type' => 'type_2', 'status' => 'inactive'],
        ]);
        DB::table('positions')->insert([
            ['id' => 1, 'level' => 1, 'position_name' => 'Giám đốc'],
            ['id' => 2, 'level' => 2, 'position_name' => 'Phó Giám đốc'],
            ['id' => 3, 'level' => 5, 'position_name' => 'Nhân viên'],
        ]);
        DB::table('departments')->insert([
            ['id' => 1, 'branch_id' => 1, 'status' => 'active'],
            ['id' => 2, 'branch_id' => 2, 'status' => 'active'],
            ['id' => 3, 'branch_id' => 1, 'status' => 'inactive'],
        ]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Văn thư I', 'branch_id' => 1, 'department_id' => null, 'position_id' => 3, 'document_role' => 'clerk'],
            ['id' => 2, 'name' => 'Giám đốc I', 'branch_id' => 1, 'department_id' => null, 'position_id' => 1, 'document_role' => null],
            ['id' => 3, 'name' => 'Phó Giám đốc I', 'branch_id' => 1, 'department_id' => null, 'position_id' => 2, 'document_role' => null],
            ['id' => 4, 'name' => 'Nhân viên I', 'branch_id' => 1, 'department_id' => 1, 'position_id' => 3, 'document_role' => null],
            ['id' => 5, 'name' => 'Văn thư II', 'branch_id' => 2, 'department_id' => null, 'position_id' => 3, 'document_role' => 'clerk'],
            ['id' => 6, 'name' => 'Giám đốc II', 'branch_id' => 2, 'department_id' => null, 'position_id' => 1, 'document_role' => null],
            ['id' => 7, 'name' => 'Nhân viên II', 'branch_id' => 2, 'department_id' => 2, 'position_id' => 3, 'document_role' => null],
        ]);
    }

    public function test_admin_created_user_keeps_position_department_and_document_role_and_appears_in_directors(): void
    {
        $user = \App\Models\Users::create([
            'name' => 'Giám đốc mới', 'branch_id' => 1, 'department_id' => 1,
            'position_id' => 1, 'document_role' => 'user',
        ]);
        $this->assertSame(1, (int) $user->fresh()->position_id);
        $this->assertSame(1, (int) $user->fresh()->department_id);
        $this->assertSame('user', $user->fresh()->document_role);
        $this->assertContains($user->id, app(DocumentRecipientService::class)->directors(1)->pluck('id')->all());
        $this->assertNotContains($user->id, app(DocumentRecipientService::class)->directors(2)->pluck('id')->all());
        $user->position_id = 2;
        $user->save();
        $this->assertContains($user->id, app(DocumentRecipientService::class)->directors(1)->pluck('id')->all());
    }

    public function test_department_or_display_name_alone_does_not_grant_director_selection(): void
    {
        DB::table('users')->where('id', 4)->update(['name' => 'Giám Đốc', 'position_id' => null]);
        $this->assertNotContains(4, app(DocumentRecipientService::class)->directors(1)->pluck('id')->all());
    }

    public function test_public_distribution_reaches_only_selected_director_and_department(): void
    {
        $document = $this->document();
        $recipients = app(DocumentRecipientService::class);
        $recipients->distribute($document, User::findOrFail(1), [2], []);

        $this->assertTrue($this->visibleTo($document, 1));
        $this->assertTrue($this->visibleTo($document, 2));
        $this->assertFalse($this->visibleTo($document, 3));
        $this->assertFalse($this->visibleTo($document, 4));
        $this->assertFalse($this->visibleTo($document, 6));

        $recipients->distribute($document, User::findOrFail(1), [], [1]);
        $this->assertTrue($this->visibleTo($document, 4));
        $this->assertFalse($this->visibleTo($document, 7));
    }

    public function test_forged_other_branch_directors_departments_and_inactive_targets_are_rejected(): void
    {
        $service = app(DocumentRecipientService::class);
        $sender = User::findOrFail(1);
        foreach ([[[6], []], [[4], []], [[], [2]], [[], [3]], [[999], []]] as [$users, $departments]) {
            try {
                $service->validateTargets($sender, $users, $departments);
                $this->fail('An invalid recipient was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('recipients', $exception->errors());
            }
        }
        foreach ([[1], [3], [999]] as $branches) {
            try {
                $service->validateBranches($branches);
                $this->fail('An invalid branch was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('to_branch_ids', $exception->errors());
            }
        }
    }

    public function test_type_two_clerk_distributes_received_document_only_to_own_directors_and_departments(): void
    {
        $document = $this->document();
        $clerk = User::findOrFail(5);
        app(DocumentService::class)->transferDocument($document, 1, 2, User::findOrFail(1));

        $this->assertFalse($document->canBeTransferredToBranchBy($clerk));
        $this->assertTrue($this->visibleTo($document, 5));
        $this->assertFalse($this->visibleTo($document, 6));
        app(DocumentRecipientService::class)->distribute($document, $clerk, [6], [2], 'Xử lý nội bộ');
        $this->assertTrue($this->visibleTo($document, 6));
        $this->assertTrue($this->visibleTo($document, 7));
        $this->assertFalse($this->visibleTo($document, 3));

        $this->expectException(ValidationException::class);
        app(DocumentRecipientService::class)->distribute($document, $clerk, [2], [1]);
    }

    public function test_type_two_clerk_cannot_distribute_document_that_was_not_transferred_to_their_branch(): void
    {
        $this->expectException(AuthorizationException::class);
        app(DocumentRecipientService::class)->distribute($this->document(), User::findOrFail(5), [6], [2]);
    }

    public function test_document_type_filters_keep_permissions_and_issue_date_sorting(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('direction')->nullable();
            $table->date('issued_date')->nullable();
            $table->date('received_date')->nullable();
        });
        $service = app(DocumentQueryService::class);
        $user = User::findOrFail(1);
        foreach (['incoming', 'outgoing', 'decision', 'unclassified'] as $type) {
            $this->document()->update(['direction' => $type, 'issued_date' => '2026-01-02', 'received_date' => '2026-03-01']);
            Document::create(['created_by' => 5, 'managing_branch_id' => 2, 'visibility' => Document::VISIBILITY_RESTRICTED,
                'direction' => $type, 'issued_date' => '2026-01-02']);
            $this->assertSame(1, $service->getDocumentsForUser($user, ['direction' => $type])->count());
        }
        $this->assertSame(4, $service->getDocumentsForUser($user)->count());
        $early = $this->document();
        $early->update(['direction' => 'incoming', 'issued_date' => '2025-12-31']);
        $missing = $this->document();
        $missing->update(['direction' => 'incoming']);
        $filters = ['direction' => 'incoming', 'sort_by' => 'issued_date', 'sort_dir' => 'asc'];
        $ids = $service->getDocumentsForUser($user, $filters)->pluck('id')->all();
        $this->assertSame($early->id, $ids[0]);
        $this->assertSame($missing->id, end($ids));
        $ids = $service->getDocumentsForUser($user, array_replace($filters, ['sort_dir' => 'desc']))->pluck('id')->all();
        $this->assertSame($missing->id, end($ids));
        $this->assertSame(1, $service->getDocumentsForUser($user, [
            'direction' => 'incoming', 'issued_date_from' => '2026-01-01', 'issued_date_to' => '2026-01-31',
        ])->count());
    }

    public function test_stt_sort_orders_documents_by_id_in_both_directions_across_pages(): void
    {
        $first = $this->document();
        $middle = $this->document();
        $last = $this->document();
        Document::create(['created_by' => 5, 'managing_branch_id' => 2, 'visibility' => Document::VISIBILITY_PUBLIC]);
        $query = app(DocumentQueryService::class);
        $user = User::findOrFail(1);
        $this->assertSame([$first->id, $middle->id, $last->id],
            $query->getDocumentsForUser($user, ['sort_by' => 'id', 'sort_dir' => 'asc'])->pluck('id')->all());
        $this->assertSame([$last->id, $middle->id, $first->id],
            $query->getDocumentsForUser($user, ['sort_by' => 'id', 'sort_dir' => 'desc'])->pluck('id')->all());
        $this->assertSame([$first->id],
            $query->getDocumentsForUser($user, ['sort_by' => 'id', 'sort_dir' => 'desc'])->offset(2)->limit(2)->pluck('id')->all());
    }

    public function test_private_is_only_own_clerks_and_selected_directors_even_with_old_grants(): void
    {
        $document = $this->document();
        $document->update(['visibility' => Document::VISIBILITY_PRIVATE]);
        $service = app(DocumentService::class);
        $service->assignPermission($document, 'user', 2, User::findOrFail(1));
        $service->assignPermission($document, 'department', 1, User::findOrFail(1));
        $service->assignPermission($document, 'user', 6, User::findOrFail(1));
        $this->assertTrue($this->visibleTo($document, 1));
        $this->assertTrue($this->visibleTo($document, 2));
        foreach ([3, 4, 5, 6, 7] as $id) $this->assertFalse($this->visibleTo($document, $id));
        $this->assertFalse($document->canBeTransferredToBranchBy(User::findOrFail(1)));
        foreach ([fn () => $service->transferDocument($document, 1, 2, User::findOrFail(1)),
            fn () => $service->transferDocumentToDepartment($document, 1, User::findOrFail(1)),
            fn () => app(DocumentRecipientService::class)->distribute($document, User::findOrFail(1), [], [1])] as $attempt) {
            try {
                $attempt();
                $this->fail('Private department/branch transfer accepted');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('recipients', $exception->errors());
            }
        }
        $this->assertSame(0, DB::table('document_transfers')->count());
    }

    public function test_normal_selected_departments_include_leaders_but_not_employees_and_public_adds_employees(): void
    {
        DB::table('positions')->insert(['id' => 4, 'level' => 3, 'position_name' => 'Trưởng phòng']);
        DB::table('users')->insert(['id' => 8, 'name' => 'Trưởng phòng I', 'branch_id' => 1, 'department_id' => 1, 'position_id' => 4]);
        DB::table('users')->insert(['id' => 9, 'name' => 'Trưởng phòng II', 'branch_id' => 2, 'department_id' => 2, 'position_id' => 4]);
        $document = $this->document();
        $document->update(['visibility' => Document::VISIBILITY_NORMAL]);
        $sender = User::findOrFail(1);
        $recipients = app(DocumentRecipientService::class);
        $recipients->distribute($document, $sender, [2], [1]);
        foreach ([1, 2, 8] as $id) $this->assertTrue($this->visibleTo($document, $id));
        foreach ([3, 4, 5, 6, 7, 9] as $id) $this->assertFalse($this->visibleTo($document, $id));
        app(DocumentService::class)->transferDocument($document, 1, 2, $sender);
        $this->assertTrue($this->visibleTo($document, 5));
        $this->assertFalse($this->visibleTo($document, 6));
        $this->assertFalse($this->visibleTo($document, 9));
        $recipients->distribute($document, User::findOrFail(5), [6], [2]);
        foreach ([6, 9] as $id) $this->assertTrue($this->visibleTo($document, $id));
        foreach ([4, 7] as $id) $this->assertFalse($this->visibleTo($document, $id));
        $document->update(['visibility' => Document::VISIBILITY_PUBLIC]);
        foreach ([4, 7] as $id) $this->assertTrue($this->visibleTo($document, $id));
        $this->assertFalse($this->visibleTo($document, 3));
        $document->update(['visibility' => Document::VISIBILITY_PRIVATE]);
        foreach ([4, 5, 6, 7, 8, 9] as $id) $this->assertFalse($this->visibleTo($document, $id));
        $this->assertTrue($this->visibleTo($document, 2));
    }

    public function test_file_download_rechecks_document_permission(): void
    {
        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            $table->integer('document_id');
            $table->string('file_path');
            $table->string('file_name');
            $table->timestamps();
        });
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('documents/test.pdf', "%PDF-1.4\n% test\n");
        $document = $this->document();
        $document->update(['visibility' => Document::VISIBILITY_PRIVATE]);
        $file = \App\Models\DocumentAttachment::create(['document_id' => $document->id, 'file_path' => 'documents/test.pdf', 'file_name' => 'test.pdf']);
        $controller = app(\App\Http\Controllers\User\DocumentAttachmentController::class);
        \Illuminate\Support\Facades\Session::put('user_id', 1);
        $response = $controller($file->id, app(DocumentQueryService::class));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('/documents/attachments/'.$file->id, $file->view_url);
        foreach ([4, 6] as $userId) {
            \Illuminate\Support\Facades\Session::put('user_id', $userId);
            try {
                $controller($file->id, app(DocumentQueryService::class));
                $this->fail('Unauthorized file download accepted');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                $this->assertSame(404, $exception->getStatusCode());
            }
        }
    }

    public function test_private_upload_rejects_forged_department_or_branch_before_saving(): void
    {
        \Illuminate\Support\Facades\Session::put('user_id', 1);
        foreach ([['to_department_ids' => [1]], ['to_branch_ids' => [2]]] as $targets) {
            $request = \Illuminate\Http\Request::create('/', 'POST', $targets + [
                'direction' => 'incoming', 'title' => 'Private', 'document_code' => 'PRIVATE',
                'issued_date' => '2026-01-01', 'received_date' => '2026-01-02',
                'is_public_level' => 'private', 'to_user_ids' => [2],
            ]);
            try {
                app(\App\Http\Controllers\User\DocumentApiController::class)->store($request);
                $this->fail('Private upload accepted broad targets');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('recipients', $exception->errors());
            }
        }
        $this->assertSame(0, Document::count());
        $this->assertSame(0, DB::table('document_transfers')->count());
    }

    public function test_legacy_visibility_migration_preserves_rows_and_maps_to_selected_scope(): void
    {
        $ids = [];
        foreach (['private', 'restricted', 'branch', 'system'] as $visibility) {
            $document = $this->document();
            $document->update(['visibility' => $visibility]);
            $ids[] = $document->id;
        }
        (require database_path('migrations/main/2026_09_15_000001_document_selected_visibility.php'))->up();
        $this->assertSame(['normal', 'private', 'public', 'public'], Document::whereIn('id', $ids)->orderBy('id')->pluck('visibility')->all());
        $this->assertSame(4, Document::count());
        $this->assertSame(0, DB::table('document_permissions')->count());
        foreach ($ids as $id) $this->assertFalse($this->visibleTo(Document::findOrFail($id), 4));
    }

    public function test_edit_distribution_prefills_replaces_and_revokes_downstream_without_deleting_history(): void
    {
        $document = $this->document();
        $service = app(DocumentRecipientService::class);
        $owner = User::findOrFail(1);
        $service->sync($document, $owner, [2], [1], [2]);
        $service->distribute($document, User::findOrFail(5), [6], [2]);
        $this->assertEquals(['to_user_ids' => [2], 'to_department_ids' => [1], 'to_branch_ids' => [2]], $service->selection($document));
        foreach ([2, 4, 5, 6, 7] as $userId) $this->assertTrue($this->visibleTo($document, $userId));
        $transfersBefore = $document->transfers()->count();

        // Keeping a branch does not wipe selections made by its own clerk.
        $service->sync($document, $owner, [2], [1], [2]);
        $this->assertTrue($this->visibleTo($document, 7));
        $this->assertSame($transfersBefore, $document->transfers()->count());

        $service->sync($document, $owner, [3], [], []);
        foreach ([2, 4, 5, 6, 7] as $userId) $this->assertFalse($this->visibleTo($document, $userId));
        foreach ([1, 3] as $userId) $this->assertTrue($this->visibleTo($document, $userId));
        $this->assertFalse($document->canBeDistributedToDepartmentBy(User::findOrFail(5)));
        $this->assertSame($transfersBefore + 1, $document->transfers()->count());
        $this->assertSame($transfersBefore, $document->transfers()->where('status', 'revoked')->count());
        $this->assertSame(1, $document->activeTransfers()->count());
        $this->assertDatabaseHas('document_logs', ['action' => 'distribution_updated']);

        // Re-adding the branch must not silently restore downstream recipients.
        $service->sync($document, $owner, [3], [], [2]);
        $this->assertTrue($this->visibleTo($document, 5));
        foreach ([6, 7] as $userId) $this->assertFalse($this->visibleTo($document, $userId));
    }

    public function test_edit_distribution_rejects_foreign_targets_and_non_owner_without_mutation(): void
    {
        $document = $this->document();
        $service = app(DocumentRecipientService::class);
        $owner = User::findOrFail(1);
        $service->sync($document, $owner, [2], [1], [2]);
        $before = $service->selection($document);
        foreach ([[[6], [], []], [[], [2], []], [[], [], [3]]] as [$users, $departments, $branches]) {
            try {
                $service->sync($document, $owner, $users, $departments, $branches);
                $this->fail('Invalid recipient accepted');
            } catch (ValidationException $exception) {
                $this->assertEquals($before, $service->selection($document));
            }
        }
        try {
            $service->sync($document, User::findOrFail(5), [], [], []);
            $this->fail('Non-owner changed distribution');
        } catch (AuthorizationException $exception) {
            $this->assertEquals($before, $service->selection($document));
        }
        $document->update(['visibility' => 'private']);
        try {
            $service->sync($document, $owner, [2], [1], []);
            $this->fail('Private department accepted');
        } catch (ValidationException $exception) {
            $this->assertEquals($before, $service->selection($document));
        }
        $service->sync($document, $owner, [2], [], []);
        $document->update(['visibility' => 'public']);
        foreach ([4, 5, 6, 7] as $userId) $this->assertFalse($this->visibleTo($document, $userId));
        $this->assertTrue($this->visibleTo($document, 2));
    }

    public function test_edit_api_saves_metadata_and_recipients_atomically_and_accepts_empty_selection(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            foreach (['title', 'document_code', 'direction', 'priority', 'security_level'] as $column) $table->string($column)->nullable();
        });
        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            $table->integer('document_id');
        });
        $document = $this->document();
        $service = app(DocumentRecipientService::class);
        $owner = User::findOrFail(1);
        $service->sync($document, $owner, [2], [1], [2]);
        \Illuminate\Support\Facades\Session::put('user_id', 1);
        $input = ['title' => 'Updated', 'document_code' => '123/TEST', 'direction' => 'incoming',
            'priority' => 'normal', 'security_level' => 'normal', 'is_public_level' => 'private',
            'sync_recipients' => '1', 'to_user_ids' => [2], 'to_department_ids' => [1]];
        $controller = app(\App\Http\Controllers\User\DocumentApiController::class);
        try {
            $controller->update(\Illuminate\Http\Request::create('/', 'PUT', $input), $document->id);
            $this->fail('Private department accepted');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('recipients', $exception->errors());
        }
        $this->assertNull($document->fresh()->title);
        $this->assertSame('public', $document->fresh()->visibility);
        $this->assertTrue($this->visibleTo($document, 4));
        unset($input['to_department_ids']);
        $response = $controller->update(\Illuminate\Http\Request::create('/', 'PUT', $input), $document->id);
        $this->assertTrue($response->getData(true)['success']);
        $this->assertSame('Updated', $document->fresh()->title);
        $this->assertSame('private', $document->fresh()->visibility);
        $this->assertEquals(['to_user_ids' => [2], 'to_department_ids' => [], 'to_branch_ids' => []], $service->selection($document));
        unset($input['to_user_ids']);
        $controller->update(\Illuminate\Http\Request::create('/', 'PUT', $input), $document->id);
        $this->assertSame(['to_user_ids' => [], 'to_department_ids' => [], 'to_branch_ids' => []], $service->selection($document));
        $this->assertFalse($this->visibleTo($document, 2));
        $this->assertTrue($this->visibleTo($document, 1));
    }

    private function document(): Document
    {
        return Document::create(['created_by' => 1, 'managing_branch_id' => 1, 'visibility' => Document::VISIBILITY_PUBLIC]);
    }

    private function visibleTo(Document $document, int $userId): bool
    {
        return app(DocumentQueryService::class)->getDocumentsForUser(User::findOrFail($userId))->whereKey($document->id)->exists();
    }
}
