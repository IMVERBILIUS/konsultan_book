<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Panggil seeder AdminUserSeeder
        $this->call([
            AdminUserSeeder::class,
            // Anda bisa menambahkan seeder lain di sini jika ada
        ]);

        // Ini adalah contoh jika Anda ingin membuat user dummy dalam jumlah banyak (opsional)
        // \App\Models\User::factory(10)->create();
    }
}
