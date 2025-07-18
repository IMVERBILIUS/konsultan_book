<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consultation_services', function (Blueprint $table) {
            $table->bigIncrements('id'); // bigint(20) UNSIGNED PRIMARY KEY, Auto Increment
            $table->string('title'); // varchar(255), Tidak Null
            $table->decimal('price', 10, 2); // decimal(10,2), Tidak Null
            $table->string('short_description'); // varchar(255), Tidak Null
            $table->text('product_description'); // text, Tidak Null
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_services');
    }
};
