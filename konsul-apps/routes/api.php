<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\ConsultationServiceController;
use App\Http\Controllers\Api\SketchController; // Pastikan ini diimpor
use Laravel\Passport\Passport;
use Laravel\Passport\Http\Controllers\AccessTokenController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
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

// --- PERBAIKAN: Rute Sketsa Publik ---
Route::get('sketches/public', [SketchController::class, 'indexPublic']);
Route::get('sketches/public/slug/{slug}', [SketchController::class, 'showPublicBySlug']);
// --- AKHIR PERBAIKAN ---


// Rute Terlindungi (Membutuhkan 'auth:sanctum' middleware)
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

    // --- PERBAIKAN: Manajemen Sketsa (Admin/Author only) ---
    Route::apiResource('admin/sketches', SketchController::class);
    // --- AKHIR PERBAIKAN ---
});
