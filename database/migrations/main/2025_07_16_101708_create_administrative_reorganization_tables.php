<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdministrativeReorganizationTables extends Migration
{
    public function up()
    {
        // Tạo bảng tỉnh trước sáp nhập
        Schema::create('old_provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        // Tạo bảng huyện/quận trước sáp nhập
        Schema::create('old_districts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('old_province_id');
            $table->string('name');
        });

        // Tạo bảng đơn vị hành chính cũ (xã, phường, thị trấn)
        Schema::create('old_wards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('old_district_id');
            $table->string('name');
        });

        // Tạo bảng tỉnh sau sáp nhập
        Schema::create('new_provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        // Tạo bảng đơn vị hành chính mới (xã, phường)
        Schema::create('new_wards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('new_province_id');
            $table->string('name');
        });
        // Tạo bảng ánh xạ đơn vị tỉnh cũ sang tỉnh mới
        Schema::create('province_mappings', function (Blueprint $table) {
            $table->unsignedBigInteger('old_province_id');
            $table->unsignedBigInteger('new_province_id');
            $table->primary(['old_province_id','new_province_id']);
        });
        // Tạo bảng ánh xạ từ đơn vị xã/phường/thị trấn cũ -> đơn vị mới
        Schema::create('ward_mappings', function (Blueprint $table) {
            $table->unsignedBigInteger('old_ward_id');
            $table->unsignedBigInteger('new_ward_id');
            $table->primary(['old_ward_id','new_ward_id']);
        });
    }

    public function down()
    {
        // Drop theo thứ tự ngược lại để không vi phạm FK
        Schema::dropIfExists('ward_mappings');
        Schema::dropIfExists('province_mappings');
        Schema::dropIfExists('new_wards');
        Schema::dropIfExists('new_provinces');
        Schema::dropIfExists('old_wards');
        Schema::dropIfExists('old_districts');
        Schema::dropIfExists('old_provinces');
    }
}
