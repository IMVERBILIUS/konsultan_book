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
        Schema::create('subheadings', function (Blueprint $table) {
            $table->bigIncrements('id'); // bigint(20) UNSIGNED PRIMARY KEY, Auto Increment
            $table->unsignedBigInteger('article_id'); // bigint(20) UNSIGNED, Foreign Key ke articles
            $table->string('title'); // varchar(255), Tidak Null
            $table->integer('order_number')->default(1); // int(11), Default 1
            $table->timestamps(); // created_at, updated_at

            // Foreign Key Constraint
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subheadings');
    }
};
