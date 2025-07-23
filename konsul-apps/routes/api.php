<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\ConsultationServiceController;
use App\Http\Controllers\Api\SketchController;
use App\Http\Controllers\Api\ReferralCodeController; // --- PERBAIKAN: Impor ReferralCodeController ---
use Laravel\Passport\Passport;
use Laravel\Passport\Http\Controllers\AccessTokenController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rute Passport untuk Token Issuance
Route::post('oauth/token', [AccessTokenController::class, 'issueToken'])
    ->middleware(['throttle', 'api', 'cors'])
    ->name('passport.token');

// Rute Autentikasi Publik
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Rute Artikel Publik
Route::get('articles/public', [ArticleController::class, 'indexPublic']);
Route::get('articles/public/slug/{slug}', [ArticleController::class, 'showPublicBySlug']);

// Rute Sketsa Publik
Route::get('sketches/public', [SketchController::class, 'indexPublic']);
Route::get('sketches/public/slug/{slug}', [SketchController::class, 'showPublicBySlug']);


// --- Rute Terlindungi (Membutuhkan 'auth:sanctum' middleware) ---
Route::middleware('auth:sanctum')->group(function () {
    // Autentikasi yang dilindungi
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'user']);
    Route::get('/user', [AuthController::class, 'user']);

    // Manajemen Artikel (Admin/Author only)
    Route::apiResource('admin/articles', ArticleController::class);

    // Rute khusus untuk statistik dashboard admin
    Route::get('admin/dashboard-stats', [ArticleController::class, 'dashboardStats']);

    // Manajemen Layanan Konsultasi (Admin only)
    Route::apiResource('admin/consultation-services', ConsultationServiceController::class);

    // Manajemen Sketsa (Admin/Author only)
    Route::apiResource('admin/sketches', SketchController::class);

    // --- PERBAIKAN: Manajemen Kode Referral (Admin only) ---
    Route::apiResource('admin/referral-codes', ReferralCodeController::class);
    // Jika Anda ingin otorisasi role, tambahkan ->middleware('role:admin');
    // --- AKHIR PERBAIKAN ---
});
