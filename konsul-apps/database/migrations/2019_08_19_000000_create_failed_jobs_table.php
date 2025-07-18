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
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id(); // bigint(20) UNSIGNED PRIMARY KEY, Auto Increment
            $table->string('uuid')->unique(); // varchar(255), Unik
            $table->text('connection'); // text
            $table->text('queue'); // text
            $table->longText('payload'); // longtext
            $table->longText('exception'); // longtext
            $table->timestamp('failed_at')->useCurrent(); // timestamp, Default current_timestamp()
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
};
