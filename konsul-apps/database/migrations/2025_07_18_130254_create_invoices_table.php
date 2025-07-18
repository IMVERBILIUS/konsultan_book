
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
        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id'); // bigint(20) UNSIGNED PRIMARY KEY, Auto Increment
            $table->unsignedBigInteger('user_id'); // bigint(20) UNSIGNED, Foreign Key ke users
            $table->string('invoice_no')->unique(); // varchar(255), Unik, Tidak Null
            $table->timestamp('invoice_date'); // timestamp, Tidak Null (akan diisi di aplikasi)

            // --- PERBAIKAN DI SINI: Jadikan due_date nullable atau berikan default valid ---
            // Karena due_date dihitung dari invoice_date + 1 hari, itu akan diisi oleh aplikasi.
            // Maka, saat pembuatan baris, kolom ini bisa null terlebih dahulu jika belum dihitung.
            $table->timestamp('due_date')->nullable(); // timestamp, Bisa Null, karena diisi setelah invoice_date
            // Atau jika memang TIDAK BOLEH NULL:
            // $table->timestamp('due_date')->default('2000-01-01 00:00:00'); // Berikan default timestamp yang valid
            // Tapi nullable() lebih masuk akal jika dihitung belakangan.
            // --- AKHIR PERBAIKAN ---

            $table->decimal('total_amount', 10, 2); // decimal(10,2), Tidak Null
            $table->enum('payment_type', ['dp', 'full_payment']); // enum, Tidak Null
            $table->enum('payment_status', ['paid', 'unpaid', 'pending', 'dibatalkan'])->default('unpaid'); // enum, Default 'unpaid'
            $table->enum('session_type', ['online', 'offline']); // enum, Tidak Null
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
        Schema::dropIfExists('invoices');
    }
};
