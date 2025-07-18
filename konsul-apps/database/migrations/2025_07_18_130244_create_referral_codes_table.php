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
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->bigIncrements('id'); // bigint(20) UNSIGNED PRIMARY KEY, Auto Increment
            $table->string('code')->unique(); // varchar(255), Unik
            $table->decimal('discount_percentage', 5, 2); // decimal(5,2), Tidak Null
            $table->timestamp('valid_from')->nullable(); // timestamp, Bisa Null
            $table->timestamp('valid_until')->nullable(); // timestamp, Bisa Null
            $table->integer('max_uses')->nullable(); // int(11), Bisa Null
            $table->integer('current_uses')->default(0); // int(11), Default 0
            $table->unsignedBigInteger('created_by')->nullable(); // bigint(20) UNSIGNED, Foreign Key ke users
            $table->timestamps(); // created_at, updated_at

            // Foreign Key Constraint
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null'); // Jika user dihapus, set null
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_codes');
    }
};
