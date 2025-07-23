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
        Schema::table('consultation_services', function (Blueprint $table) {
            // Tambahkan kolom thumbnail setelah product_description
            $table->string('thumbnail')->nullable()->after('product_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_services', function (Blueprint $table) {
            // Hapus kolom thumbnail jika rollback
            $table->dropColumn('thumbnail');
        });
    }
};
