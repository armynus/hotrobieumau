<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_name');
            $table->string('branch_code')->nullable();
            $table->string('database_name')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        
    }


    public function down()
    {
      
    }
};
