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
        Schema::create('consultation_bookings', function (Blueprint $table) {
            $table->bigIncrements('id'); // bigint(20) UNSIGNED PRIMARY KEY, Auto Increment
            $table->unsignedBigInteger('user_id'); // bigint(20) UNSIGNED, Foreign Key ke users
            $table->unsignedBigInteger('service_id'); // bigint(20) UNSIGNED, Foreign Key ke consultation_services
            $table->unsignedBigInteger('referral_code_id')->nullable(); // bigint(20) UNSIGNED, Foreign Key ke referral_codes, Bisa Null
            $table->unsignedBigInteger('invoice_id')->nullable(); // bigint(20) UNSIGNED, Foreign Key ke invoices, Bisa Null
            $table->date('booked_date'); // date, Tidak Null
            $table->time('booked_time'); // time, Tidak Null
            $table->enum('contact_preference', ['chat_only', 'chat_and_call']); // enum, Tidak Null
            $table->enum('session_type', ['online', 'offline']); // enum, Tidak Null
            $table->text('offline_address')->nullable(); // text, Bisa Null
            $table->decimal('discount_amount', 10, 2)->nullable(); // decimal(10,2), Bisa Null
            $table->decimal('final_price', 10, 2); // decimal(10,2), Tidak Null
            $table->enum('payment_type', ['dp', 'full_payment']); // enum, Tidak Null
            $table->enum('session_status', ['menunggu pembayaran', 'terdaftar', 'ongoing', 'selesai', 'dibatalkan'])->default('menunggu pembayaran'); // enum, Default 'menunggu pembayaran'
            $table->timestamps(); // created_at, updated_at

            // Foreign Key Constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('consultation_services')->onDelete('cascade');
            $table->foreign('referral_code_id')->references('id')->on('referral_codes')->onDelete('set null'); // set null jika kode referral dihapus
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null'); // set null jika invoice dihapus
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_bookings');
    }
};
