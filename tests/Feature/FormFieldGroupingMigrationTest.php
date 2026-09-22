<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FormFieldGroupingMigrationTest extends TestCase
{
    public function test_migration_backfills_groups_and_spaced_display_order(): void
    {
        config(['database.default' => 'mysql', 'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('mysql');

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->string('field_code');
            $table->string('field_name');
            $table->string('data_type')->default('text');
            $table->string('placeholder')->nullable();
            $table->string('value')->nullable();
            $table->timestamps();
        });
        DB::table('form_fields')->insert([
            ['field_code' => 'nameloc', 'field_name' => 'Tên khách hàng'],
            ['field_code' => 'phone_no', 'field_name' => 'Điện thoại'],
            ['field_code' => 'idxacno', 'field_name' => 'Số tài khoản'],
            ['field_code' => 'branch', 'field_name' => 'Chi nhánh'],
        ]);

        (require database_path('migrations/main/2026_09_17_000001_add_grouping_to_form_fields_table.php'))->up();

        $rows = DB::table('form_fields')->orderBy('id')->get()->keyBy('field_code');
        $this->assertSame('customer', $rows['nameloc']->content_group);
        $this->assertSame(10, $rows['nameloc']->display_order);
        $this->assertSame(20, $rows['phone_no']->display_order);
        $this->assertSame('account', $rows['idxacno']->content_group);
        $this->assertSame('preparation', $rows['branch']->content_group);
    }
}
