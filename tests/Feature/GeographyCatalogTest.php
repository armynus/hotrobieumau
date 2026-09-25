<?php

namespace Tests\Feature;

use App\Services\Geography\GeographyImportService;
use App\Services\Geography\GeographyStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GeographyCatalogTest extends TestCase
{
    private GeographyStore $store;

    private GeographyImportService $importer;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'geo_test', 'geography.connection' => 'geo_test',
            'database.connections.geo_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        DB::purge('geo_test');
        Storage::fake('local');
        (require base_path('database/migrations/main/2026_09_25_140000_create_geography_catalog_tables.php'))->up();
        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name');
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Người dùng thử']);
        Schema::create('old_wards', function ($t) {
            $t->id();
            $t->string('name');
        });
        DB::table('old_wards')->insert(['name' => 'Giữ nguyên']);
        $this->store = app(GeographyStore::class);
        $this->importer = app(GeographyImportService::class);
    }

    private function data(): array
    {
        $units = [['code' => '00001', 'name' => 'Xã Mới A', 'province' => 'Tỉnh Mới'], ['code' => '00002', 'name' => 'Xã Mới B', 'province' => 'Tỉnh Mới']];
        $links = [];
        foreach ($units as $i => $unit) {
            $links[] = ['oldProvince' => 'Tỉnh Cũ', 'oldDistrict' => 'Huyện A', 'oldName' => 'Xã Tân Thành',
                'newCode' => $unit['code'], 'newName' => $unit['name'], 'newProvince' => $unit['province'],
                'sourceRow' => $i + 2, 'sourceRelation' => 'Một phần', 'scope' => $i ? 'remainder' : 'part', 'verification' => 'pending', 'notes' => ''];
        }

        return ['schemaVersion' => 1, 'snapshotDate' => '2025-07-01', 'units' => $units, 'links' => $links, 'issues' => []];
    }

    private function import(?array $data = null): int
    {
        Storage::disk('local')->put('test-input.json', json_encode($data ?? $this->data(), JSON_UNESCAPED_UNICODE));

        return $this->importer->importFile(Storage::disk('local')->path('test-input.json'), 'Thử');
    }

    public function test_import_is_immediately_searchable_in_both_directions_without_approval(): void
    {
        $id = $this->import();
        $this->withSession(['user_id' => 1]);
        $this->get('/merger_lookup')->assertOk()->assertSee('Trước sáp nhập')->assertDontSee('duyệt')->assertDontSee('công bố');
        $options = $this->getJson('/merger_lookup/options?kind=old_ward&province='.urlencode('Tỉnh Cũ').'&district='.urlencode('Huyện A').'&q=tan+thanh')->assertOk()->json();
        $this->assertCount(1, $options);
        $this->getJson('/merger_lookup/detail?kind=old_ward&id='.$options[0]['id'])->assertOk()->assertJsonCount(2, 'targets')->assertJsonPath('split', true);
        $this->getJson('/merger_lookup/detail?kind=new_ward&id=00001')->assertOk()->assertJsonPath('targets.0.code', '00001')->assertJsonCount(1, 'targets.0.members');
        $this->assertSame(1, DB::table('old_wards')->count());
        $this->assertSame($id, $this->store->current()->id);
    }

    public function test_duplicate_rows_are_not_inserted_and_have_a_note(): void
    {
        $data = $this->data();
        $data['links'][] = $data['links'][0];
        $this->import($data);
        $this->assertSame(2, $this->store->rows()->count());
        $this->assertStringContainsString('trùng', $this->store->current()->notes);
        $this->assertSame(1, $this->import($data));
    }

    public function test_invalid_import_keeps_current_data_and_source(): void
    {
        $this->import();
        $data = $this->data();
        $data['links'][0]['newName'] = 'Sai tên';
        try {
            $this->import($data);
            $this->fail('Must reject mismatched catalogue');
        } catch (ValidationException $e) {
        }
        $this->assertSame(1, $this->store->current()->id);
        $this->assertSame(2, $this->store->rows()->count());
        Storage::disk('local')->assertExists($this->store->current()->source_path);
    }

    public function test_admin_only_routes_and_simple_edit_with_stale_tab_protection(): void
    {
        $this->import();
        $row = $this->store->rows()->first();
        $this->get('/admin/geography')->assertRedirect(route('login_admin'));
        $this->withSession(['admin_id' => 1]);
        $this->get('/admin/geography')->assertOk()->assertSee('Nhập / cập nhật dữ liệu')->assertDontSee('Duyệt hàng loạt');
        $changes = ['old_province' => $row->old_province, 'old_district' => $row->old_district, 'old_name' => $row->old_name,
            'scope' => 'part', 'enabled' => 0, 'note' => 'Loại để kiểm tra', 'revision' => 0];
        $this->post('/admin/geography/records/'.$row->id, $changes)->assertRedirect();
        $this->assertFalse((bool) $this->store->rows()->find($row->id)->enabled);
        $this->post('/admin/geography/records/'.$row->id, $changes)->assertSessionHasErrors('revision');
    }

    public function test_separate_accent_sensitive_identity_and_parent_filters(): void
    {
        $data = $this->data();
        $extra = $data['links'][0];
        $extra['oldName'] = 'Xã Tân Thạnh';
        $data['links'][] = $extra;
        $this->import($data);
        $this->assertSame(2, $this->store->rows()->distinct()->count('old_key'));
        $this->withSession(['user_id' => 1]);
        $this->getJson('/merger_lookup/options?kind=old_ward&province=X&district=Y')->assertExactJson([]);
        $this->getJson('/merger_lookup/options?kind=new_ward')->assertExactJson([]);
        $this->getJson('/merger_lookup/detail?kind=old_province&id='.urlencode('Tỉnh Cũ'))->assertJsonPath('provinces.0.name', 'Tỉnh Mới');
    }

    public function test_national_data_is_loaded_with_notes_and_known_bad_links_excluded(): void
    {
        $file = config('geography.prepared.national.path');
        if (! is_file($file)) {
            $this->markTestSkipped('National source missing');
        }
        $this->importer->importFile($file, 'Toàn quốc');
        $rows = $this->store->rows();
        $this->assertSame(10596, (clone $rows)->where('enabled', true)->count());
        $this->assertSame(6, (clone $rows)->where('enabled', false)->count());
        $this->assertSame(3321, (clone $rows)->where('enabled', true)->distinct()->count('new_code'));
        $this->assertSame(34, (clone $rows)->where('enabled', true)->distinct()->count('new_province'));
        $this->assertSame(308, (clone $rows)->where('verified', true)->count());
        $this->assertCount(1130, json_decode($this->store->current()->notes, true));
        $gao = (clone $rows)->where('old_name', 'Xã Gáo Giồng')->first();
        $this->withSession(['user_id' => 1]);
        $this->getJson('/merger_lookup/detail?kind=old_ward&id='.$gao->old_key)->assertJsonCount(2, 'targets');
        $this->getJson('/merger_lookup/options?kind=new_province')->assertJsonCount(34);
        $this->getJson('/merger_lookup/detail?kind=new_ward&id=31261')->assertOk();
        $this->getJson('/merger_lookup/detail?kind=new_ward&id=29869')->assertOk()->assertJsonMissing(['district' => 'Thành phố Mỹ Tho']);
    }

    public function test_csv_import_keeps_leading_zeroes_skips_bad_rows_and_supports_historical_districts(): void
    {
        $csv = "tinh_cu,huyen_cu,xa_cu,tinh_moi,xa_moi,ma_xa_moi,pham_vi,ghi_chu\nTỉnh Cũ,Huyện A,,Tỉnh Mới,Đặc khu A,00001,Toàn bộ,\nTỉnh Cũ,Huyện A,Xã B,Tỉnh Mới,Xã B,abc,,\n";
        Storage::disk('local')->put('test.csv', $csv);
        $this->importer->importFile(Storage::disk('local')->path('test.csv'), 'CSV');
        $this->assertSame('00001', $this->store->rows()->first()->new_code);
        $this->assertNull($this->store->rows()->first()->old_name);
        $this->assertStringContainsString('Bỏ qua', $this->store->current()->notes);
    }

    public function test_new_import_replaces_view_not_old_data_and_untrusted_source_cannot_claim_verified(): void
    {
        $this->import();
        $old = $this->store->current();
        $data = $this->data();
        $data['links'][0]['verification'] = 'verified_nq1663';
        $data['links'][1]['verification'] = 'rejected_nq1663';
        $this->import($data);
        $this->assertSame(2, $this->store->current()->id);
        $this->assertSame(2, $this->store->rows($old->id)->count());
        $this->assertSame(0, $this->store->rows()->where('verified', true)->count());
        $this->withSession(['user_id' => 1]);
        $this->getJson('/merger_lookup/detail?kind=new_ward&id=00002')->assertNotFound();
    }

    public function test_admin_upload_and_notes_download_and_old_link_redirect(): void
    {
        $data = $this->data();
        $data['issues'][] = ['message' => '=HYPERLINK("test")', 'source' => 'Excel', 'row' => 2];
        $this->withSession(['admin_id' => 1]);
        $this->post('/admin/geography/import', ['source' => 'file', 'replace' => 1,
            'file' => UploadedFile::fake()->createWithContent('data.json', json_encode($data))])->assertRedirect(route('admin.geography.index'));
        $this->get('/admin/geography/source')->assertDownload('du-lieu-goc.json');
        $csv = $this->get('/admin/geography/notes')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->get('/admin/merger-data/import')->assertRedirect(route('admin.geography.index'));
    }

    public function test_same_file_preserves_manual_edits_and_old_records_are_not_editable(): void
    {
        $this->import();
        $row = $this->store->rows()->first();
        $this->store->rows()->where('id', $row->id)->update(['enabled' => false, 'note' => 'Sửa tay']);
        $this->import();
        $this->assertSame('Sửa tay', $this->store->rows()->find($row->id)->note);
        $data = $this->data();
        $data['links'][0]['notes'] = 'Bộ mới';
        $this->import($data);
        $this->withSession(['admin_id' => 1])->get('/admin/geography/records/'.$row->id)->assertNotFound();
    }

    public function test_original_excel_reuses_three_source_corrections(): void
    {
        $file = 'C:/Users/Admin/Downloads/vietnam-sap-nhap-phuong-xa.xlsx';
        if (! is_file($file) || hash_file('sha256', $file) !== config('geography.mapping_source_hash')) {
            $this->markTestSkipped('Original workbook not available');
        }
        $this->importer->importFile($file, 'Excel gốc');
        $this->assertSame(10596, $this->store->rows()->where('enabled', true)->count());
        $this->assertSame(6, $this->store->rows()->where('enabled', false)->count());
        $this->assertSame(308, $this->store->rows()->where('verified', true)->count());
    }

    public function test_shared_data_and_missing_schema_are_handled_without_touching_branch_data(): void
    {
        $this->import();
        config(['database.connections.geo_branch' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'database.default' => 'geo_branch']);
        $this->withSession(['user_id' => 1]);
        $this->getJson('/merger_lookup/options?kind=new_province')->assertJsonCount(1);
        $this->assertSame(2, $this->store->rows()->count());
        config(['geography.connection' => 'geo_branch']);
        $this->getJson('/merger_lookup/options?kind=new_province')->assertExactJson([]);
    }

    public function test_conflicting_duplicate_and_wrong_excel_headers_are_rejected_without_replacement(): void
    {
        $this->import();
        $data = $this->data();
        $data['links'][] = $data['links'][0];
        $data['links'][2]['scope'] = 'whole';
        try {
            $this->import($data);
            $this->fail('Conflicting duplicate must fail');
        } catch (ValidationException $e) {
        }
        Storage::disk('local')->put('bad.csv', "Tên tỉnh,Tên xã\nTỉnh A,Xã A\n");
        try {
            $this->importer->importFile(Storage::disk('local')->path('bad.csv'), 'Sai cột');
            $this->fail('Wrong columns must fail');
        } catch (ValidationException $e) {
        }
        $this->assertSame(1, $this->store->current()->id);
        $this->assertSame(2, $this->store->rows()->count());
    }
}
