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
            // Tambahkan kolom session_type (enum online/offline)
            $table->enum('session_type', ['online', 'offline'])->default('online')->after('booked_time');
            // Tambahkan kolom offline_address
            $table->text('offline_address')->nullable()->after('session_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_service', function (Blueprint $table) {
            $table->dropColumn('offline_address');
            $table->dropColumn('session_type');
        });
    }
};
