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
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id(); // bigint(20) UNSIGNED PRIMARY KEY, Auto Increment
            $table->morphs('tokenable'); // tokenable_type varchar(255), tokenable_id bigint(20) UNSIGNED
            $table->string('name'); // varchar(255)
            $table->string('token', 64)->unique(); // varchar(64), Unik
            $table->text('abilities')->nullable(); // text, Bisa Null
            $table->timestamp('last_used_at')->nullable(); // timestamp, Bisa Null
            $table->timestamp('expires_at')->nullable(); // timestamp, Bisa Null
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
