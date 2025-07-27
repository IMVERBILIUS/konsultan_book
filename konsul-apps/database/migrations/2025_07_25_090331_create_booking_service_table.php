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
        Schema::create('booking_service', function (Blueprint $table) {
            $table->bigIncrements('id'); // ID unik untuk setiap entri di pivot table
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('service_id');
            $table->decimal('price_at_booking', 10, 2)->comment('Price of the service at the time of booking');
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('consultation_bookings')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('consultation_services')->onDelete('cascade');

            // Optional: Composite primary key to ensure unique pairs
            $table->unique(['booking_id', 'service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_service');
    }
};
