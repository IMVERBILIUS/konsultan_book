<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\ConsultationServiceController;
use App\Http\Controllers\Api\SketchController;
use App\Http\Controllers\Api\ReferralCodeController;
use App\Http\Controllers\Api\ConsultationBookingController; // Pastikan ini diimpor
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

// Rute REFERRAL PUBLIK (untuk check code)
Route::get('referral-codes/check', [ReferralCodeController::class, 'check']);


// --- Rute Terlindungi (Membutuhkan 'auth:sanctum' middleware) ---
Route::middleware('auth:sanctum')->group(function () {
    // Autentikasi
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'user']);
    // --- PERBAIKAN: Ubah '.class' menjadi '::class' ---
    Route::get('/user', [AuthController::class, 'user']);
    // --- AKHIR PERBAIKAN ---

    // Manajemen Artikel (Admin/Author only)
    // --- PERBAIKAN: Ubah '.class' menjadi '::class' ---
    Route::apiResource('admin/articles', ArticleController::class);
    // --- AKHIR PERBAIKAN ---

    // Rute khusus untuk statistik dashboard admin
    // --- PERBAIKAN: Ubah '.class' menjadi '::class' ---
    Route::get('admin/dashboard-stats', [ArticleController::class, 'dashboardStats']);
    // --- AKHIR PERBAIKAN ---

    // Manajemen Layanan Konsultasi (Admin only)
    // --- PERBAIKAN: Ubah '.class' menjadi '::class' ---
    Route::apiResource('admin/consultation-services', ConsultationServiceController::class);
    // --- AKHIR PERBAIKAN ---

    // Manajemen Sketsa (Admin/Author only)
    // --- PERBAIKAN: Ubah '.class' menjadi '::class' ---
    Route::apiResource('admin/sketches', SketchController::class);
    // --- AKHIR PERBAIKAN ---

    // Manajemen Kode Referral (Admin only)
    // --- PERBAIKAN: Ubah '.class' menjadi '::class' ---
    Route::apiResource('admin/referral-codes', ReferralCodeController::class);
    // --- AKHIR PERBAIKAN ---

    // Manajemen Booking Konsultasi (Admin/User only, sesuai role)
    // --- PERBAIKAN: Ubah '.class' menjadi '::class' ---
    Route::apiResource('admin/consultation-bookings', ConsultationBookingController::class);
    Route::get('admin/consultation-bookings/{booking}/invoice', [ConsultationBookingController::class, 'showInvoice'])->name('consultation-bookings.show_invoice');
    // --- AKHIR PERBAIKAN ---
});
