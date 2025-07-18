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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // bigint(20) UNSIGNED PRIMARY KEY, Auto Increment
            $table->string('name'); // varchar(255), Tidak Null
            $table->string('email')->unique(); // varchar(255), Unik, Tidak Null
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password'); // varchar(255), Tidak Null
            $table->enum('role', ['admin', 'author', 'reader'])->default('reader'); // enum, Default 'reader'
            $table->string('google_id')->unique()->nullable(); // varchar(255), Unik, Bisa Null
            $table->rememberToken();
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
