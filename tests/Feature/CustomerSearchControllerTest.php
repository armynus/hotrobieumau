<?php

namespace Tests\Feature;

use App\Models\AccountInfo;
use App\Models\CustomerInfo;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerSearchControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
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

    public function test_search_finds_customer_by_name_and_loads_accounts(): void
    {
        $this->withoutMiddleware();
        $user = User::first() ?? new User(['id' => 1]);
        $this->actingAs($user);
        session(['UserBranchId' => 1]);

        CustomerInfo::create([
            'custno' => '6500123456',
            'name' => 'NGUYEN THI MAI',
            'nameloc' => 'Nguyễn Thị Mai',
            'identity_no' => '079123456789',
            'phone_no' => '0901234567',
            'custtpcd' => 'Cá nhân',
        ]);

        AccountInfo::create([
            'idxacno' => '6500205001111',
            'custseq' => '6500123456',
            'ccycd' => 'VND',
        ]);

        $response = $this->getJson(route('customer.search', ['query' => 'Mai']));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertSame('6500123456', $data[0]['customer']['custno']);
        $this->assertSame('Nguyễn Thị Mai', $data[0]['customer']['nameloc']);
        $this->assertCount(1, $data[0]['customer']['accounts']);
        $this->assertSame('6500205001111', $data[0]['customer']['accounts'][0]['idxacno']);
    }

    public function test_search_finds_customer_by_cif_cccd_phone_and_account_number(): void
    {
        $this->withoutMiddleware();
        $user = User::first() ?? new User(['id' => 1]);
        $this->actingAs($user);
        session(['UserBranchId' => 1]);

        CustomerInfo::create([
            'custno' => '6500999888',
            'name' => 'TRAN VAN DONG',
            'nameloc' => 'Trần Văn Đông',
            'identity_no' => '012345678999',
            'phone_no' => '0988776655',
            'custtpcd' => 'Cá nhân',
        ]);

        AccountInfo::create([
            'idxacno' => '6500205999888',
            'custseq' => '6500999888',
            'ccycd' => 'VND',
        ]);

        // 1. By CIF
        $resCif = $this->getJson(route('customer.search', ['query' => '999888']));
        $resCif->assertOk();
        $this->assertCount(1, $resCif->json());

        // 2. By CCCD
        $resId = $this->getJson(route('customer.search', ['query' => '45678999']));
        $resId->assertOk();
        $this->assertCount(1, $resId->json());

        // 3. By Phone
        $resPhone = $this->getJson(route('customer.search', ['query' => '098877']));
        $resPhone->assertOk();
        $this->assertCount(1, $resPhone->json());

        // 4. By Account number
        $resAcc = $this->getJson(route('customer.search', ['query' => '205999888']));
        $resAcc->assertOk();
        $this->assertCount(1, $resAcc->json());
        $this->assertSame('6500999888', $resAcc->json()[0]['customer']['custno']);
    }

    public function test_search_requires_at_least_two_characters(): void
    {
        $this->withoutMiddleware();
        $user = User::first() ?? new User(['id' => 1]);
        $this->actingAs($user);

        $response = $this->getJson(route('customer.search', ['query' => 'M']));
        $response->assertStatus(422);
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
