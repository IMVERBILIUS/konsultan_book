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
        Schema::table('booking_service', function (Blueprint $table) {
            // --- PERBAIKAN: Jadikan booked_date dan booked_time nullable ---
            $table->date('booked_date')->nullable()->after('price_at_booking');
            $table->time('booked_time')->nullable()->after('booked_date');
            // --- AKHIR PERBAIKAN ---
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_service', function (Blueprint $table) {
            $table->dropColumn('booked_time');
            $table->dropColumn('booked_date');
        });
    }
};
