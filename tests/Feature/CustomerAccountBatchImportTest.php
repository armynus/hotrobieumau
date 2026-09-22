<?php

namespace Tests\Feature;

use App\Imports\AccountInfoImport;
use App\Imports\CustomerInfoImport;
use App\Models\AccountInfo;
use App\Models\CustomerInfo;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CustomerAccountBatchImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'tenant',
            'database.connections.tenant' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);
        DB::purge('tenant');

        $this->createTable(new CustomerInfo);
        $this->createTable(new AccountInfo);
    }

    public function test_customer_import_inserts_1200_rows_with_six_queries(): void
    {
        $rows = collect(range(1, 1200))->map(fn (int $number) => [
            'custno' => str_pad((string) $number, 6, '0', STR_PAD_LEFT),
            'nm' => 'KHACH HANG '.$number,
            'nmloc' => 'Khách hàng '.$number,
            'regno' => 'ID'.$number,
        ]);
        $queryCount = 0;
        DB::connection('tenant')->listen(function (QueryExecuted $query) use (&$queryCount): void {
            $queryCount++;
        });

        $import = new CustomerInfoImport;
        $import->collection($rows);
        $importQueryCount = $queryCount;

        $this->assertSame(6, $importQueryCount);
        $this->assertSame(1200, $import->inserted);
        $this->assertSame(0, $import->updated);
        $this->assertSame(1200, CustomerInfo::count());
    }

    public function test_reimport_preserves_blank_customer_fields_and_accepts_excel_dates(): void
    {
        CustomerInfo::create([
            'custno' => '000123',
            'name' => 'TÊN CŨ',
            'phone_no' => '0900000000',
            'identity_date' => '2020-02-03',
            'addr1' => 'Xã cũ',
            'addr2' => 'Huyện cũ',
            'addrfull' => 'Xã cũ Huyện cũ',
        ]);
        $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTimeImmutable('1990-05-06'));

        $import = new CustomerInfoImport;
        $import->collection(collect([
            [
                'custno' => '000123',
                'nm' => 'TÊN MỚI',
                'name_4' => ' ',
                'issuedt1' => null,
                'name_1' => $excelDate,
                'addr1' => 'Xã mới',
                'addr2' => '',
            ],
            ['custno' => '', 'nm' => 'Không có mã'],
        ]));

        $customer = CustomerInfo::where('custno', '000123')->firstOrFail();
        $this->assertSame('TÊN MỚI', $customer->name);
        $this->assertSame('0900000000', $customer->phone_no);
        $this->assertSame('2020-02-03', $customer->identity_date);
        $this->assertSame('1990-05-06', $customer->birthday);
        $this->assertSame('Xã mới Huyện cũ', $customer->addrfull);
        $this->assertSame(0, $import->inserted);
        $this->assertSame(1, $import->updated);
        $this->assertSame(1, $import->skipped);
    }

    public function test_account_import_merges_duplicate_rows_and_writes_each_code_once(): void
    {
        AccountInfo::create([
            'idxacno' => 'A001',
            'custseq' => 'C001',
            'custnm' => 'Tên cũ',
            'addr1' => 'Địa chỉ cũ',
            'addrfull' => 'Địa chỉ cũ',
        ]);

        $import = new AccountInfoImport;
        $import->collection(collect([
            ['idxacno' => 'A001', 'custseq' => '', 'custnm' => 'Tên mới', 'addr1' => ''],
            ['idxacno' => 'A001', 'ccycd' => 'VND'],
            ['idxacno' => 'A002', 'custseq' => 'C002', 'addr1' => 'Phường 1', 'addr2' => 'Quận 1'],
        ]));

        $existing = AccountInfo::where('idxacno', 'A001')->firstOrFail();
        $created = AccountInfo::where('idxacno', 'A002')->firstOrFail();
        $this->assertSame('C001', $existing->custseq);
        $this->assertSame('Tên mới', $existing->custnm);
        $this->assertSame('VND', $existing->ccycd);
        $this->assertSame('Địa chỉ cũ', $existing->addrfull);
        $this->assertSame('Phường 1 Quận 1', $created->addrfull);
        $this->assertSame(1, $import->inserted);
        $this->assertSame(1, $import->updated);
    }

    public function test_real_excel_file_is_read_with_the_heading_and_chunk_concerns(): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['custno', 'nm', 'nmloc', 'name_4', 'issuedt1'],
            ['009001', 'NGUYEN VAN A', 'Nguyễn Văn A', '0901000001', '20240131'],
            ['009002', 'NGUYEN VAN B', 'Nguyễn Văn B', '0901000002', '20240201'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'customer-import-');
        @unlink($path);
        $path .= '.xlsx';

        try {
            (new Xlsx($spreadsheet))->save($path);
            $import = new CustomerInfoImport;
            Excel::import($import, $path);

            $this->assertSame(2, $import->inserted);
            $this->assertSame('2024-01-31', CustomerInfo::where('custno', '009001')->value('identity_date'));
            $this->assertSame('Nguyễn Văn B', CustomerInfo::where('custno', '009002')->value('nameloc'));
        } finally {
            $spreadsheet->disconnectWorksheets();
            @unlink($path);
        }
    }

    public function test_branch_migration_adds_lookup_indexes_without_requiring_unique_data(): void
    {
        CustomerInfo::insert([
            ['custno' => 'DUPLICATE'],
            ['custno' => 'DUPLICATE'],
        ]);

        $migration = require database_path('migrations/branch/2026_09_17_000001_add_customer_account_lookup_indexes.php');
        $migration->up();

        $customerIndexes = collect(Schema::getIndexes('customer_info'))->pluck('name');
        $accountIndexes = collect(Schema::getIndexes('account_info'))->pluck('name');
        $this->assertContains('customer_info_custno_index', $customerIndexes);
        $this->assertContains('customer_info_identity_no_index', $customerIndexes);
        $this->assertContains('account_info_idxacno_index', $accountIndexes);
        $this->assertContains('account_info_custseq_index', $accountIndexes);
    }

    private function createTable(CustomerInfo|AccountInfo $model): void
    {
        Schema::connection('tenant')->create($model->getTable(), function (Blueprint $table) use ($model): void {
            $table->id();
            foreach ($model->getFillable() as $column) {
                $definition = in_array($column, ['identity_date', 'identity_outdate', 'birthday'], true)
                    ? $table->date($column)
                    : $table->string($column);
                $definition->nullable();
            }
            $table->timestamps();
        });
    }
}
