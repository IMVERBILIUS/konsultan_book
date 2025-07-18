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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->bigIncrements('id'); // bigint(20) UNSIGNED PRIMARY KEY, Auto Increment
            $table->unsignedBigInteger('user_id')->unique(); // bigint(20) UNSIGNED, Foreign Key ke users, UNIQUE
            $table->string('profile_photo')->nullable(); // varchar(255), Bisa Null
            $table->string('name')->nullable(); // varchar(255), Bisa Null
            $table->string('email')->nullable(); // varchar(255), Bisa Null
            $table->date('birthdate')->nullable(); // date, Bisa Null
            $table->enum('gender', ['male', 'female'])->nullable(); // enum, Bisa Null
            $table->string('phone_number')->nullable(); // varchar(255), Bisa Null
            $table->string('social_media')->nullable(); // varchar(255), Bisa Null
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
        Schema::dropIfExists('user_profiles');
    }
};
