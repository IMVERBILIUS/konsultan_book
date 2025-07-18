<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User; // Impor model User
use Illuminate\Support\Facades\Hash; // Untuk hashing password
use Illuminate\Support\Str; // Untuk slug jika diperlukan (tidak untuk user)

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cek apakah user admin sudah ada untuk menghindari duplikasi
        $adminUser = User::where('email', 'admin@example.com')->first();

        if (is_null($adminUser)) {
            User::create([
                'name' => 'Admin Utama',
                'email' => 'sasa1234@gmail.com',
                'password' => Hash::make('sasa123'), // Password di-hash
                'role' => 'admin', // Set peran menjadi 'admin'
                'created_at' => now(),
                'updated_at' => now(),
                // 'email_verified_at' => now(), // Opsional: jika Anda ingin langsung verifikasi email
            ]);
            $this->command->info('Akun Admin telah berhasil dibuat: sasa1234@gmail.com / password123');
        } else {
            $this->command->info('Akun Admin (sasa1234@gmail.com) sudah ada.');
        }

        // Anda bisa menambahkan user lain di sini jika perlu
        // User::create([
        //     'name' => 'Penulis Artikel',
        //     'email' => 'author@example.com',
        //     'password' => Hash::make('password123'),
        //     'role' => 'author',
        // ]);
        // $this->command->info('Akun Penulis telah berhasil dibuat: author@example.com / password123');
    }
}
