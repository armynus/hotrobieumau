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

    public function test_private_distribution_reaches_selected_director_and_department_without_implicit_leadership_access(): void
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

    private function document(): Document
    {
        return Document::create(['created_by' => 1, 'managing_branch_id' => 1, 'visibility' => Document::VISIBILITY_RESTRICTED]);
    }

    private function visibleTo(Document $document, int $userId): bool
    {
        return app(DocumentQueryService::class)->getDocumentsForUser(User::findOrFail($userId))->whereKey($document->id)->exists();
    }
}
