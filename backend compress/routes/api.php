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
use App\Http\Controllers\Api\BukuTamuController;
use App\Http\Controllers\Api\PemberitahuanController;
use App\Http\Controllers\Api\TataTertibController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\FaqKategoriController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\PenulisController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\Api\SubKategoriController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

//Public Routes
Route::get('/koleksi/year-range', [KoleksiController::class, 'getYearRange']);
Route::get('/koleksi/total-views', [KoleksiController::class, 'getTotalViews']);
Route::get('/koleksi/distribusi-kategori', [KoleksiController::class, 'getDistribusiKategori']);
Route::get('/koleksi/distribusi-jenis', [KoleksiController::class, 'getDistribusiJenis']);
Route::get('/koleksi/most-favorited', [KoleksiController::class, 'getMostFavoritedCollections']);

Route::get('/koleksi/best-collections', [KoleksiController::class, 'getBestCollections']);  // GET Best Collection
Route::get('/koleksi/filter', [KoleksiController::class, 'filter']);
Route::get('/koleksi', [KoleksiController::class, 'index']);                                // Tampilkan list semua koleksi
Route::get('/koleksi/{koleksi}', [KoleksiController::class, 'show']);                       // Detail koleksi

Route::post('/recommendations', [RecommendationController::class, 'getRecommendations']);   // Rekomendasi

Route::post('/koleksi/count-by-year', [ConnectionTestController::class, 'count']);
Route::get('/test-python-connection', [ConnectionTestController::class, 'testPythonConnection']);

/// YouTube 
Route::prefix('youtube')->group(function () {
    Route::get('/embed/{id}', [YoutubeController::class, 'getYoutubeEmbed']);           // Existing routes
    Route::post('/track-view/{id}', [YoutubeController::class, 'trackYoutubeView']);    // Track YouTube video view   
    Route::get('/thumbnail/{id}', [YoutubeController::class, 'getYoutubeThumbnail']);   // Get YouTube thumbnail
});

// Public Metadata Routes
Route::get('/kategori-bang-kom', [KategoriBangKomController::class, 'index']);
Route::get('/kategori-bang-kom/{id}', [KategoriBangKomController::class, 'show']);
Route::get('/jenis-dokumen', [JenisDokumenController::class, 'index']);
Route::get('/jenis-dokumen/{id}', [JenisDokumenController::class, 'show']);
Route::get('/format-koleksi', [FormatKoleksiController::class, 'index']);
Route::get('/format-koleksi/{id}', [FormatKoleksiController::class, 'show']);

Route::get('/users/count', [UserController::class, 'getUserCount']);

// New Public Routes
Route::get('/pemberitahuan', [PemberitahuanController::class, 'index']);
Route::get('/tata-tertib', [TataTertibController::class, 'index']);
Route::get('/settings', [SettingController::class, 'index']);
Route::get('/faq-kategori', [FaqKategoriController::class, 'index']);
Route::get('/faqs', [FaqController::class, 'index']);
Route::get('/kategori', [KategoriController::class, 'index']);
Route::get('/sub-kategori', [SubKategoriController::class, 'index']);
Route::get('/penulis', [PenulisController::class, 'index']);
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
Route::middleware(['auth:sanctum', 'checkUserStatus', 'checkUserRole:user,admin'])->group(function () {
    Route::get('/profile', [UserController::class, 'showProfile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
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

    // Buku Tamu (User)
    Route::post('/buku-tamu', [BukuTamuController::class, 'store']);
});


// Admin route
Route::get('/admin/check-auth', [AdminController::class, 'checkAuthStatus'])->middleware('auth:admin');
Route::get('/admin/dashboard-stats', [AdminController::class, 'getDashboardStats'])->middleware(['auth:sanctum', 'checkUserRole:admin']);
Route::middleware(['auth:sanctum', 'checkUserStatus', 'checkUserRole:admin'])->group(function () {
    Route::post('/users/{user}/restore', [UserController::class, 'restore']);
    Route::get('/users/trashed', [UserController::class, 'trashed']);
    Route::delete('/koleksi/{id}', [KoleksiController::class, 'destroy']);
    Route::post('/koleksi', [KoleksiController::class, 'store']);
    Route::put('/koleksi/{id}', [KoleksiController::class, 'update']);
    Route::get('/koleksi/{id}/edit', [KoleksiController::class, 'edit']);
    // Route::get('/koleksi/{id}/admin-pdf', [KoleksiController::class, 'showAdminPdf']);
    // Route::get('/koleksi/{id}/public-pdf', [KoleksiController::class, 'showPublicPdf']);
    Route::post('/koleksi/{id}/mark-as-best', [KoleksiController::class, 'markAsBestCollection']);
    Route::post('/koleksi/{id}/unmark-as-best', [KoleksiController::class, 'unmarkAsBestCollection']);
    Route::post('/koleksi/{id}/mark-as-active', [KoleksiController::class, 'markAsActive']);
    Route::post('/koleksi/{id}/unmark-as-active', [KoleksiController::class, 'unmarkAsActive']);
    // Route::get('koleksi/{id}/edit', [KoleksiController::class, 'edit']);
    Route::get('/users', [UserController::class, 'index']); // GET all users
    Route::get('/roles', [RoleController::class, 'index']);    // Get all roles
    Route::put('/users/{user}/role', [UserController::class, 'updateRole']);   // Update user role
    Route::delete('/users/{user}', [UserController::class, 'destroy']);        // Hapus user
    Route::post('/users/bulk-delete', [UserController::class, 'bulkDestroy']); // Hapus banyak users

    // Metadata Management (Admin)
    Route::post('/kategori-bang-kom', [KategoriBangKomController::class, 'store']);
    Route::put('/kategori-bang-kom/{id}', [KategoriBangKomController::class, 'update']);
    Route::delete('/kategori-bang-kom/{id}', [KategoriBangKomController::class, 'destroy']);
    
    Route::post('/jenis-dokumen', [JenisDokumenController::class, 'store']);
    Route::put('/jenis-dokumen/{id}', [JenisDokumenController::class, 'update']);
    Route::delete('/jenis-dokumen/{id}', [JenisDokumenController::class, 'destroy']);

    // Buku Tamu (Admin)
    Route::get('/admin/buku-tamu', [BukuTamuController::class, 'index']);
    Route::put('/admin/buku-tamu/{id}', [BukuTamuController::class, 'update']);
    Route::delete('/admin/buku-tamu/{id}', [BukuTamuController::class, 'destroy']);

    // Pemberitahuan
    Route::post('/pemberitahuan', [PemberitahuanController::class, 'store']);
    Route::get('/pemberitahuan/{id}', [PemberitahuanController::class, 'show']);
    Route::put('/pemberitahuan/{id}', [PemberitahuanController::class, 'update']);
    Route::delete('/pemberitahuan/{id}', [PemberitahuanController::class, 'destroy']);
    
    // Tata Tertib
    Route::post('/tata-tertib', [TataTertibController::class, 'store']);
    Route::get('/tata-tertib/{id}', [TataTertibController::class, 'show']);
    Route::put('/tata-tertib/{id}', [TataTertibController::class, 'update']);
    Route::delete('/tata-tertib/{id}', [TataTertibController::class, 'destroy']);

    // Settings
    Route::put('/settings', [SettingController::class, 'update']);

    // FAQ Kategori
    Route::post('/faq-kategori', [FaqKategoriController::class, 'store']);
    Route::get('/faq-kategori/{id}', [FaqKategoriController::class, 'show']);
    Route::put('/faq-kategori/{id}', [FaqKategoriController::class, 'update']);
    Route::delete('/faq-kategori/{id}', [FaqKategoriController::class, 'destroy']);

    // FAQ
    Route::post('/faqs', [FaqController::class, 'store']);
    Route::get('/faqs/{id}', [FaqController::class, 'show']);
    Route::put('/faqs/{id}', [FaqController::class, 'update']);
    Route::delete('/faqs/{id}', [FaqController::class, 'destroy']);

    // Logs
    Route::get('/logs', [LogController::class, 'index']);
    Route::get('/logs/login', [LogController::class, 'getLoginLogs']);
    Route::get('/logs/buku-tamu', [LogController::class, 'getBukuTamuLogs']);
    Route::get('/logs/baca', [LogController::class, 'getBacaLogs']);
    Route::get('/logs/favorit', [LogController::class, 'getFavoritLogs']);

    // Penulis, Kategori, SubKategori Management
    Route::apiResource('penulis', PenulisController::class);
    Route::apiResource('kategori', KategoriController::class);
    Route::apiResource('sub-kategori', SubKategoriController::class);
});