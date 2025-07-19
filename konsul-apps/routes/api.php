// konsul-apps/routes/api.php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\ConsultationServiceController;
use App\Http\Controllers\Api\SketchController; // --- PERUBAHAN: Impor SketchController ---
use Laravel\Passport\Passport;
use Laravel\Passport\Http\Controllers\AccessTokenController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('oauth/token', [AccessTokenController::class, 'issueToken'])
    ->middleware(['throttle', 'api', 'cors'])
    ->name('passport.token');

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Rute Artikel Publik
Route::get('articles/public', [ArticleController::class, 'indexPublic']);
Route::get('articles/public/slug/{slug}', [ArticleController::class, 'showPublicBySlug']);

// --- PERUBAHAN: Rute Sketsa Publik ---
Route::get('sketches/public', [SketchController::class, 'indexPublic']);
Route::get('sketches/public/slug/{slug}', [SketchController::class, 'showPublicBySlug']);
// --- AKHIR PERUBAHAN ---


// --- Rute Terlindungi (Membutuhkan 'auth:api' middleware) ---
Route::middleware('auth:api')->group(function () {
    // Autentikasi
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'user']);
    Route::get('/user', [AuthController::class, 'user']); // Jika Anda punya ini, hapus jika /me sudah cukup

    // Manajemen Artikel (Admin/Author only)
    Route::apiResource('admin/articles', ArticleController::class);

    // Rute khusus untuk statistik dashboard admin (sudah ada)
    Route::get('admin/dashboard-stats', [ArticleController::class, 'dashboardStats']);

    // Manajemen Layanan Konsultasi (Admin only)
    Route::apiResource('admin/consultation-services', ConsultationServiceController::class);

    // --- PERUBAHAN: Manajemen Sketsa (Admin/Author only) ---
    Route::apiResource('admin/sketches', SketchController::class);
    // Jika Anda ingin otorisasi role, tambahkan ->middleware('role:admin|author');
    // --- AKHIR PERUBAHAN ---
});
