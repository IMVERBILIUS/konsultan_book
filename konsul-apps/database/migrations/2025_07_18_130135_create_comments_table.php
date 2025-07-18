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
        Schema::create('comments', function (Blueprint $table) {
            $table->bigIncrements('id'); // bigint(20) UNSIGNED PRIMARY KEY, Auto Increment
            $table->text('content'); // text, Tidak Null
            $table->unsignedBigInteger('article_id'); // bigint(20) UNSIGNED, Foreign Key ke articles
            $table->unsignedBigInteger('user_id'); // bigint(20) UNSIGNED, Foreign Key ke users
            $table->timestamps(); // created_at, updated_at

            // Foreign Key Constraints
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
