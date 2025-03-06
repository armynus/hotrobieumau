<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupportFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('support_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên biểu mẫu
            $table->json('fields')->nullable(); // Danh sách các trường dữ liệu sử dụng để điền vào file Word (lưu dưới dạng JSON)
            $table->string('file_template'); // Đường dẫn file mẫu (ví dụ: /templates/contract.docx)
            $table->integer('form_type')->nullable(); //Thể loại biểu mẫu
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('support_forms');
    }
}
