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
        Schema::create('sketches', function (Blueprint $table) {
            $table->bigIncrements('id'); // bigint(20) UNSIGNED PRIMARY KEY, Auto Increment
            $table->unsignedBigInteger('user_id'); // bigint(20) UNSIGNED, Foreign Key ke users
            $table->string('title'); // varchar(255), Tidak Null
            $table->string('slug')->unique(); // varchar(255), UNIQUE, Tidak Null
            $table->string('author')->nullable(); // varchar(255), Bisa Null
            $table->string('thumbnail')->nullable(); // varchar(255), Bisa Null
            $table->enum('status', ['Draft', 'Published'])->default('Draft'); // enum, Default 'Draft'
            $table->unsignedBigInteger('views')->default(0); // bigint(20) UNSIGNED, Default 0
            $table->text('content'); // text, Tidak Null
            $table->timestamps(); // created_at, updated_at

            // Foreign Key Constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sketches');
    }
};
