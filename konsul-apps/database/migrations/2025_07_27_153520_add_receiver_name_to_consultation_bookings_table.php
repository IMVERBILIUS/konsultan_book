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
        Schema::table('consultation_bookings', function (Blueprint $table) {
            // Tambahkan kolom receiver_name setelah user_id
            $table->string('receiver_name')->nullable()->after('user_id');
            // Anda juga bisa menambahkan receiver_email, receiver_phone jika perlu
            // $table->string('receiver_email')->nullable()->after('receiver_name');
            // $table->string('receiver_phone')->nullable()->after('receiver_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_bookings', function (Blueprint $table) {
            $table->dropColumn('receiver_name');
            // $table->dropColumn('receiver_email');
            // $table->dropColumn('receiver_phone');
        });
    }
};
