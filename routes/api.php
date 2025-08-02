<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\KoleksiController;
use App\Http\Controllers\Api\KategoriBangKomController;
use App\Http\Controllers\Api\JenisDokumenController;
use App\Http\Controllers\Api\RiwayatBacaController;
use App\Http\Controllers\Api\FavoritController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\FormatKoleksiController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConnectionTestController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\YoutubeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

//Public Routes
Route::get('/koleksi/filter', [KoleksiController::class, 'filter']);
Route::get('/koleksi', [KoleksiController::class, 'index']);                                // Tampilkan list semua koleksi
Route::get('/koleksi/{koleksi}', [KoleksiController::class, 'show']);                       // Detail koleksi
Route::get('/koleksi/best-collections', [KoleksiController::class, 'getBestCollections']);  // GET Best Collection
Route::post('/recommendations', [RecommendationController::class, 'getRecommendations']);   // Rekomendasi


Route::get('/koleksi/year-range', [KoleksiController::class, 'getYearRange']);
Route::get('/koleksi/total-views', [KoleksiController::class, 'getTotalViews']);
Route::get('/koleksi/distribusi-kategori', [KoleksiController::class, 'getDistribusiKategori']);
Route::get('/koleksi/distribusi-jenis', [KoleksiController::class, 'getDistribusiJenis']);
Route::get('/koleksi/most-favorited', [KoleksiController::class, 'getMostFavoritedCollections']);

Route::post('/koleksi/count-by-year', [ConnectionTestController::class, 'count']);
Route::get('/test-python-connection', [ConnectionTestController::class, 'testPythonConnection']);

/// YouTube 
Route::prefix('youtube')->group(function () {
    Route::get('/embed/{id}', [YoutubeController::class, 'getYoutubeEmbed']);           // Existing routes
    Route::post('/track-view/{id}', [YoutubeController::class, 'trackYoutubeView']);    // Track YouTube video view   
    Route::get('/thumbnail/{id}', [YoutubeController::class, 'getYoutubeThumbnail']);   // Get YouTube thumbnail
});

// Route::apiResource('koleksi', KoleksiController::class);
Route::apiResource('kategori-bang-kom', KategoriBangKomController::class);
Route::apiResource('jenis-dokumen', JenisDokumenController::class);
Route::apiResource('format-koleksi', FormatKoleksiController::class);

Route::get('/users/count', [UserController::class, 'getUserCount']);

// All user authentication routes
Route::post('/register/user', [AuthController::class, 'registerUser']);
Route::post('/register/admin', [AuthController::class, 'registerAdmin']);
Route::post('/login', [AuthController::class, 'login']);
//reset Password user
// Route::get('/forgot-password', [UserController::class, 'forgot_password']);
Route::post('/forgot-password-act', [UserController::class, 'forgot_password_act']);
Route::post('/reset-password-act', [UserController::class, 'reset_password_act']);
Route::get('/validasi-forgot-password/{token}', [UserController::class, 'validasi_forgot_password']);
Route::post('/validasi-forgot-password-act', [UserController::class, 'validasi_forgot_password_act']);

// User routes
Route::middleware(['web', 'api'])->group(function () {
    Route::get('/auth/google/redirect', [UserController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback', [UserController::class, 'handleGoogleCallback'])->name('api.google.callback');
});

// Protected routes untuk semua authenticated users
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/check-auth', [AuthController::class, 'checkAuthStatus']);
});

Route::get('/user/check-auth', [UserController::class, 'checkAuthStatus'])->middleware('auth:user');
Route::middleware(['auth:sanctum', 'checkUserRole:user,admin'])->group(function () {
    Route::get('/koleksi/{id}/pdf', [KoleksiController::class, 'showPdf']);
    // Riwayat baca
    Route::delete('/riwayat-baca/clear', [RiwayatBacaController::class, 'clearAll']);
    Route::get('/riwayat-baca', [RiwayatBacaController::class, 'index']);
    Route::post('/riwayat-baca', [RiwayatBacaController::class, 'store']);
    Route::delete('/riwayat-baca/{id}', [RiwayatBacaController::class, 'destroy']);
    
    // Route untuk Favorit
    Route::get('/favorit', [FavoritController::class, 'index']);
    Route::post('/favorit', [FavoritController::class, 'store']);
    Route::delete('/favorit/{id}', [FavoritController::class, 'destroy']);
    Route::delete('/favorit/by-koleksi/{koleksi_id}', [FavoritController::class, 'removeByKoleksi']);
    Route::get('/favorit/check/{koleksi_id}', [FavoritController::class, 'checkFavorite']);
});


// Admin route
Route::get('/admin/check-auth', [AdminController::class, 'checkAuthStatus'])->middleware('auth:admin');
Route::middleware(['auth:sanctum', 'checkUserRole:admin'])->group(function () {
    Route::post('/koleksi', [KoleksiController::class, 'store']);
    Route::put('/koleksi/{id}', [KoleksiController::class, 'update']);
    Route::delete('/koleksi/{id}', [KoleksiController::class, 'destroy']);
    Route::get('/koleksi/{id}/edit', [KoleksiController::class, 'edit']);
    // Route::get('/koleksi/{id}/admin-pdf', [KoleksiController::class, 'showAdminPdf']);
    // Route::get('/koleksi/{id}/public-pdf', [KoleksiController::class, 'showPublicPdf']);
    Route::post('/koleksi/{id}/mark-as-best', [KoleksiController::class, 'markAsBestCollection']);
    Route::post('/koleksi/{id}/unmark-as-best', [KoleksiController::class, 'unmarkAsBestCollection']);
    // Route::get('koleksi/{id}/edit', [KoleksiController::class, 'edit']);
    Route::get('/users', [UserController::class, 'index']); // GET all users
    Route::get('/roles', [RoleController::class, 'index']);    // Get all roles
    Route::put('/users/{user}/role', [UserController::class, 'updateRole']);   // Update user role
    Route::delete('/users/{user}', [UserController::class, 'destroy']);        // Hapus user
    Route::post('/users/bulk-delete', [UserController::class, 'bulkDestroy']); // Hapus banyak users
});