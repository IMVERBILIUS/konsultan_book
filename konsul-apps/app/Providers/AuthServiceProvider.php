<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
// Hapus use Laravel\Passport\Passport; jika ada
// Hapus use Carbon\Carbon; jika tidak digunakan lagi

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        //
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // PASTIKAN TIDAK ADA BARIS Passport::methods() DI SINI

        // Contoh jika ada Gate/Policy lain
        // Gate::define('view-admin-dashboard', function (User $user) {
        //     return $user->role === 'admin';
        // });
    }
}
