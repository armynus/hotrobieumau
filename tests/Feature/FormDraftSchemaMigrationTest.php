<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FormDraftSchemaMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'draft_schema_testing', 'database.connections.draft_schema_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('draft_schema_testing');

        Schema::create('form_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('form_key', 100);
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique('user_id', 'user_id');
            $table->unique('form_key', 'form_key');
        });
    }

    public function test_form_scoped_drafts_are_merged_back_to_one_shared_draft_per_user(): void
    {
        DB::table('form_drafts')->insert([
            'user_id' => 1,
            'form_key' => 'single:1',
            'payload' => json_encode(['name' => 'Cũ', 'identity_no' => '0123456789']),
            'created_at' => '2026-09-18 08:00:00',
            'updated_at' => '2026-09-18 08:00:00',
        ]);

        (require database_path('migrations/main/2026_09_18_000002_fix_form_draft_unique_scope.php'))->up();

        DB::table('form_drafts')->insert([
            [
                'user_id' => 1,
                'form_key' => 'bundle:1',
                'payload' => json_encode(['name' => 'Mới', 'account_no' => '123456789']),
                'created_at' => '2026-09-18 09:00:00',
                'updated_at' => '2026-09-18 09:00:00',
            ],
            [
                'user_id' => 2,
                'form_key' => 'single:1',
                'payload' => json_encode(['name' => 'Người khác']),
                'created_at' => '2026-09-18 09:00:00',
                'updated_at' => '2026-09-18 09:00:00',
            ],
        ]);

        (require database_path('migrations/main/2026_09_18_000003_restore_shared_form_draft.php'))->up();

        $this->assertSame(2, DB::table('form_drafts')->count());
        $draft = DB::table('form_drafts')->where('user_id', 1)->first();
        $this->assertSame('bundle:1', $draft->form_key);
        $this->assertSame([
            'name' => 'Mới',
            'identity_no' => '0123456789',
            'account_no' => '123456789',
        ], json_decode($draft->payload, true));

        $indexes = collect(Schema::getIndexes('form_drafts'));
        $this->assertTrue($indexes->contains(fn (array $index) => ($index['unique'] ?? false)
            && ($index['columns'] ?? []) === ['user_id']));
        $this->assertFalse($indexes->contains(fn (array $index) => ! ($index['primary'] ?? false)
            && in_array($index['columns'] ?? [], [['form_key'], ['user_id', 'form_key']], true)));

        $this->expectException(QueryException::class);
        DB::table('form_drafts')->insert([
            'user_id' => 1,
            'form_key' => 'another-form',
            'payload' => '{}',
        ]);
    }
}
