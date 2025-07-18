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
        Schema::create('paragraphs', function (Blueprint $table) {
            $table->bigIncrements('id'); // bigint(20) UNSIGNED PRIMARY KEY, Auto Increment
            $table->unsignedBigInteger('subheading_id'); // bigint(20) UNSIGNED, Foreign Key ke subheadings
            $table->text('content'); // text, Tidak Null
            $table->integer('order_number')->default(1); // int(11), Default 1
            $table->timestamps(); // created_at, updated_at

            // Foreign Key Constraint
            $table->foreign('subheading_id')->references('id')->on('subheadings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paragraphs');
    }
};
