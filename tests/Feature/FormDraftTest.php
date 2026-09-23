<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FormDraftTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'draft_testing', 'database.connections.draft_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('users', fn (Blueprint $table) => $table->id());
        (require database_path('migrations/main/2025_09_16_074656_support_form_draft.php'))->up();
        DB::table('users')->insert([['id' => 1], ['id' => 2]]);
    }

    public function test_new_draft_round_trips_checkbox_arrays_and_empty_values(): void
    {
        $this->withSession(['user_id' => 1])->getJson('/form-draft/supportForm')
            ->assertOk()->assertJson(['revision' => 'missing', 'payload' => null]);
        $payload = ['MobileBanking' => ['MB_APLUS'], 'name' => '', 'gender' => null];
        $response = $this->saveDraft($payload)->assertOk();
        $this->getJson('/form-draft/supportForm')->assertOk()->assertJson([
            'payload' => $payload, 'revision' => $response->json('revision'),
        ]);
    }

    public function test_two_tabs_cannot_create_over_each_other(): void
    {
        $this->saveDraft(['name' => 'Tab A'])->assertOk();
        $this->saveDraft(['name' => 'Tab B'])->assertStatus(409);
        $this->getJson('/form-draft/supportForm')->assertJsonPath('payload.name', 'Tab A');
    }

    public function test_one_user_has_one_shared_draft_across_different_forms(): void
    {
        $revision = $this->saveDraft([
            'name' => 'Mẫu đơn',
            'identity_no' => '0123456789',
        ])->assertOk()->json('revision');
        $bundleKey = 'bundle:'.str_repeat('a', 64);
        $response = $this->saveDraft([
            'name' => 'Bộ hồ sơ',
            'account_no' => '123456789',
        ], $revision, $bundleKey)->assertOk();

        $this->assertSame(1, DB::table('form_drafts')->where('user_id', 1)->count());
        $this->assertSame($bundleKey, DB::table('form_drafts')->where('user_id', 1)->value('form_key'));
        foreach (['supportForm', $bundleKey] as $formKey) {
            $this->getJson('/form-draft/'.urlencode($formKey))->assertOk()->assertJson([
                'payload' => [
                    'name' => 'Bộ hồ sơ',
                    'identity_no' => '0123456789',
                    'account_no' => '123456789',
                ],
                'revision' => $response->json('revision'),
            ]);
        }
    }

    public function test_replace_mode_clears_old_shared_fields_when_user_resets_the_draft(): void
    {
        $revision = $this->saveDraft([
            'name' => 'Nội dung cũ',
            'old_form_only' => 'xóa trường này',
        ])->assertOk()->json('revision');

        $this->saveDraft([
            'name' => '',
            'GDichVien' => 'Giao dịch viên',
        ], $revision, 'supportForm', 'replace')->assertOk();

        $this->getJson('/form-draft/another-form')->assertOk()
            ->assertJsonPath('payload.name', '')
            ->assertJsonPath('payload.GDichVien', 'Giao dịch viên')
            ->assertJsonMissingPath('payload.old_form_only');
    }

    public function test_stale_update_is_rejected_and_explicit_latest_revision_can_save(): void
    {
        $revision = $this->saveDraft(['name' => 'Initial'])->json('revision');
        $latest = $this->saveDraft(['name' => 'New'], $revision)->assertOk()->json('revision');
        $this->saveDraft(['name' => 'Stale'], $revision)->assertStatus(409);
        $this->saveDraft(['name' => 'Chosen'], $latest)->assertOk();
        $this->getJson('/form-draft/supportForm')->assertJsonPath('payload.name', 'Chosen');
    }

    public function test_user_drafts_are_isolated(): void
    {
        $this->saveDraft(['name' => 'Private'])->assertOk();
        $this->withSession(['user_id' => 2])->getJson('/form-draft/supportForm')->assertJsonPath('payload', null);
        $this->withSession(['user_id' => 2])->postJson('/form-draft/save', [
            'form_key' => 'supportForm', 'payload' => '{"name":"Second"}', 'revision' => 'missing',
        ])->assertOk();
        $this->withSession(['user_id' => 1])->getJson('/form-draft/supportForm')->assertJsonPath('payload.name', 'Private');
    }

    public function test_invalid_or_unversioned_payload_cannot_overwrite_draft(): void
    {
        $this->withSession(['user_id' => 1]);
        foreach (['null', '"string"', '1', '{broken'] as $payload) {
            $this->postJson('/form-draft/save', ['form_key' => 'supportForm', 'payload' => $payload, 'revision' => 'missing'])->assertStatus(422);
        }
        $this->postJson('/form-draft/save', ['form_key' => 'supportForm', 'payload' => '{}'])->assertStatus(422);
        $this->assertSame(0, DB::table('form_drafts')->count());
    }

    public function test_expired_session_returns_json_for_ajax_and_redirect_for_navigation(): void
    {
        $this->getJson('/form-draft/supportForm')->assertStatus(401);
        $this->postJson('/form-draft/save', [])->assertStatus(401);
        $this->get('/user')->assertRedirect(route('login'));
    }

    public function test_rendered_transaction_page_loads_plugins_once_in_dependency_order(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        cache()->put('form_types', collect(), 60);
        $this->withSession(['user_id' => 1]);
        $html = view('user.page.transaction_form', [
            'form' => (object) ['id' => 1, 'name' => 'Biểu mẫu kiểm thử'], 'type' => 1,
            'fields' => ['nameloc' => ['field_name' => 'Tên khách hàng'], 'MobileBanking' => ['field_name' => 'Dịch vụ']],
            'MobileBanking' => ['Agribank Plus' => 'MB_APLUS', 'SMS Banking' => 'MB_SMS'],
        ])->render();
        $this->assertSame(1, substr_count($html, 'js/ajax/libs/jquery/3.6.0/jquery.min.js'));
        $this->assertSame(1, substr_count($html, 'jquery.validate.min.js'));
        $this->assertSame(1, substr_count($html, 'jquery-ui.min.js'));
        $this->assertSame(1, substr_count($html, 'bootstrap.bundle.min.js'));
        $this->assertSame(1, substr_count($html, 'jquery.easing.min.js'));
        $this->assertSame(1, substr_count($html, 'sb-admin-2.min.js'));
        $this->assertStringNotContainsString('vendor/jquery/jquery.min.js', $html);
        $this->assertTrue(strpos($html, 'jquery.min.js') < strpos($html, 'bootstrap.bundle.min.js'));
        $this->assertStringContainsString('id="search_topbar_mobile"', $html);
        $this->assertStringContainsString('id="draftStatusText"', $html);
    }

    private function saveDraft(
        array $payload,
        string $revision = 'missing',
        string $formKey = 'supportForm',
        string $mode = 'merge'
    )
    {
        return $this->withSession(['user_id' => 1])->postJson('/form-draft/save', [
            'form_key' => $formKey,
            'payload' => json_encode($payload),
            'revision' => $revision,
            'mode' => $mode,
        ]);
    }
}
