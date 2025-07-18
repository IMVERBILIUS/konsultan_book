<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\ConsultationServiceController; // Pastikan ini diimpor jika digunakan
// Hapus use Laravel\Passport\Passport;
// Hapus use Laravel\Passport\Http\Controllers\AccessTokenController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- Rute Autentikasi Publik (TIDAK MEMBUTUHKAN MIDDLEWARE 'auth:api') ---
// Route::post('oauth/token', ...) DIHAPUS, akan diganti oleh login() AuthController
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']); // Ini akan mengeluarkan token Sanctum

// --- Rute Artikel Publik ---
Route::get('articles/public', [ArticleController::class, 'indexPublic']);
Route::get('articles/public/slug/{slug}', [ArticleController::class, 'showPublicBySlug']);


// --- Rute Terlindungi (Membutuhkan 'auth:sanctum' middleware) ---
// Middleware akan berubah dari 'auth:api' menjadi 'auth:sanctum'
Route::middleware('auth:sanctum')->group(function () { // PERHATIKAN: 'auth:api' GANTI MENJADI 'auth:sanctum'
    // Autentikasi yang dilindungi
    Route::post('/logout', [AuthController::class, 'logout']); // Logout di Sanctum akan menghapus token saat ini
    Route::get('/me', [AuthController::class, 'user']); // Mengambil detail user yang login

    // Manajemen Artikel (Admin/Author only)
    Route::apiResource('admin/articles', ArticleController::class);

    // Rute khusus untuk statistik dashboard admin
    Route::get('admin/dashboard-stats', [ArticleController::class, 'dashboardStats']);

    // Manajemen Layanan Konsultasi (Admin only)
    Route::apiResource('admin/consultation-services', ConsultationServiceController::class);
});
