<?php

namespace Tests\Feature;

use App\Http\Controllers\User\DataImportController;
use App\Jobs\ProcessDataImport;
use App\Models\AccountInfo;
use App\Models\CustomerInfo;
use App\Models\DataImport;
use App\Services\DataImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class DataImportJobTest extends TestCase
{
    private string $tenantDatabase;

    private string $storageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantDatabase = tempnam(sys_get_temp_dir(), 'tenant-import-');
        $this->storageRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'data-imports-'.Str::uuid();
        mkdir($this->storageRoot, 0700, true);

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => $this->tenantDatabase, 'prefix' => ''],
            'filesystems.disks.local' => ['driver' => 'local', 'root' => $this->storageRoot, 'throw' => true],
        ]);
        DB::purge('mysql');
        DB::purge('tenant');

        (require database_path('migrations/main/2026_09_18_000001_create_data_imports_table.php'))->up();
        $this->createTenantTable(new CustomerInfo);
        $this->createTenantTable(new AccountInfo);
    }

    protected function tearDown(): void
    {
        DB::disconnect('tenant');
        @unlink($this->tenantDatabase);
        File::deleteDirectory($this->storageRoot);

        parent::tearDown();
    }

    public function test_upload_is_stored_privately_and_dispatched_to_the_import_queue(): void
    {
        Queue::fake();
        $file = UploadedFile::fake()->createWithContent('khach-hang.csv', "custno,nm\n0001,NGUYEN VAN A\n");

        $operation = app(DataImportService::class)->queue($file, 'customer', 5, 7, $this->tenantDatabase);

        $this->assertSame('queued', $operation->status);
        $this->assertSame(5, $operation->user_id);
        $this->assertTrue(Storage::disk('local')->exists($operation->path));
        Queue::assertPushedOn('data-imports', ProcessDataImport::class);
    }

    public function test_job_switches_to_the_recorded_tenant_updates_progress_and_removes_the_file(): void
    {
        $path = 'data-imports/7/customers.xlsx';
        Storage::disk('local')->makeDirectory('data-imports/7');
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['custno', 'nm', 'nmloc'],
            ['0001', 'NGUYEN VAN A', 'Nguyễn Văn A'],
            ['0002', 'NGUYEN VAN B', 'Nguyễn Văn B'],
        ]);
        (new Xlsx($spreadsheet))->save(Storage::disk('local')->path($path));
        $spreadsheet->disconnectWorksheets();

        $operation = DataImport::create([
            'user_id' => 5,
            'branch_id' => 7,
            'type' => 'customer',
            'tenant_database' => $this->tenantDatabase,
            'original_name' => 'customers.xlsx',
            'disk' => 'local',
            'path' => $path,
            'status' => 'queued',
        ]);

        config(['database.connections.tenant.database' => $this->tenantDatabase.'.wrong']);
        DB::purge('tenant');
        (new ProcessDataImport($operation->id))->handle();

        $operation->refresh();
        $this->assertSame('completed', $operation->status);
        $this->assertSame(2, $operation->total_rows);
        $this->assertSame(2, $operation->processed_rows);
        $this->assertSame(2, $operation->inserted_rows);
        $this->assertSame(0, $operation->updated_rows);
        $this->assertFalse(Storage::disk('local')->exists($path));

        config(['database.connections.tenant.database' => $this->tenantDatabase]);
        DB::purge('tenant');
        $this->assertSame(['0001', '0002'], CustomerInfo::orderBy('custno')->pluck('custno')->all());
    }

    public function test_status_response_is_limited_to_the_user_and_branch_that_started_the_import(): void
    {
        $operation = DataImport::create([
            'user_id' => 5,
            'branch_id' => 7,
            'type' => 'account',
            'tenant_database' => $this->tenantDatabase,
            'original_name' => 'accounts.csv',
            'disk' => 'local',
            'path' => 'missing.csv',
            'status' => 'running',
            'total_rows' => 10,
            'processed_rows' => 4,
        ]);
        session(['user_id' => 5, 'UserBranchId' => 7]);

        $payload = app(DataImportController::class)->show($operation)->getData(true);
        $this->assertSame(40, $payload['progress']);

        session(['user_id' => 6]);
        $this->expectException(NotFoundHttpException::class);
        app(DataImportController::class)->show($operation);
    }

    private function createTenantTable(CustomerInfo|AccountInfo $model): void
    {
        Schema::connection('tenant')->create($model->getTable(), function (Blueprint $table) use ($model): void {
            $table->id();
            foreach ($model->getFillable() as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
    }
}
