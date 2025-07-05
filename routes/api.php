<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\KoleksiController;
use App\Http\Controllers\Api\KategoriBangKomController;
use App\Http\Controllers\Api\JenisDokumenController;
use App\Http\Controllers\Api\RiwayatBacaController;
use App\Http\Controllers\Api\FavoritController;
use App\Http\Controllers\Api\SimpanKoleksiController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\FormatKoleksiController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConnectionTestController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserManagementController;
// use App\Http\Controllers\BestCollectionController;
use App\Http\Controllers\Api\YoutubeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

// Tambahkan route ini ke file routes/api.php

// Recommendation System Routes
// Route::prefix('recommendations')->group(function () {
//     // Get similar collections based on koleksi_id
//     Route::post('/similar', [RecommendationController::class, 'getSimilarCollections']);
    
//     // Get recommendation system statistics
//     Route::get('/stats', [RecommendationController::class, 'getRecommendationStats']);
// });

// // Alternative route untuk akses langsung
// Route::post('/koleksi/{id}/similar', function($id, Request $request) {
//     $request->merge(['koleksi_id' => $id]);
//     return app(RecommendationController::class)->getSimilarCollections($request);
// });

// Route untuk testing (opsional - bisa dihapus di production)
// Route::get('/recommendations/test', function() {
//     return response()->json([
//         'message' => 'Recommendation system is ready',
//         'endpoints' => [
//             'POST /api/recommendations/similar' => 'Get similar collections',
//             'GET /api/recommendations/stats' => 'Get system statistics',
//             'POST /api/koleksi/{id}/similar' => 'Alternative endpoint'
//         ]
//     ]);
// });


// ========================================
// OPTION 1: GET dengan Query Parameters
// ========================================
// Route::prefix('recommendations')->group(function () {
//     // GET method untuk rekomendasi
//     Route::get('/similar', [RecommendationController::class, 'getSimilarCollectionsGet']);
    
//     // Stats tetap GET (sudah benar)
//     Route::get('/stats', [RecommendationController::class, 'getRecommendationStats']);
// });

// Alternative GET routes
// Route::get('/koleksi/{id}/similar', [RecommendationController::class, 'getSimilarByKoleksiId']);

// ========================================
// OPTION 2: RESTful Style
// ========================================


// ========================================
// OPTION 3: Hybrid Approach (Kedua Method)
// ========================================
// Route::prefix('recommendations')->group(function () {
//     // GET untuk request sederhana
//     Route::get('/similar/{koleksi_id}', [RecommendationController::class, 'getSimilarSimple']);
    
//     // POST untuk request kompleks dengan filter
//     Route::post('/similar', [RecommendationController::class, 'getSimilarAdvanced']);
    
//     Route::get('/stats', [RecommendationController::class, 'getRecommendationStats']);
// });

Route::get('/php-config', function() {
    return response()->json([
        'effective_upload_max_filesize' => ini_get('upload_max_filesize'),
        'effective_post_max_size' => ini_get('post_max_size'),
        'loaded_ini_file' => php_ini_loaded_file(),
        'additional_ini' => php_ini_scanned_files()
    ]);
});

Route::get('/upload-limits', function() {
    return response()->json([
        'nginx_client_max_body_size' => env('NGINX_CLIENT_MAX_BODY_SIZE'),
        'php_upload_max_filesize' => ini_get('upload_max_filesize'),
        'php_post_max_size' => ini_get('post_max_size')
    ]);
});

Route::get('/koleksi/best-collections', [KoleksiController::class, 'getBestCollections']);

// Get all users
Route::get('/users', [UserController::class, 'index'])->middleware(['auth:sanctum', 'checkUserRole:admin']);

// Get all roles
Route::get('/roles', [RoleController::class, 'index'])->middleware(['auth:sanctum', 'checkUserRole:admin']);

// Update user role
Route::put('/users/{user}/role', [UserController::class, 'updateRole'])->middleware(['auth:sanctum', 'checkUserRole:admin']);

// Delete user
Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware(['auth:sanctum', 'checkUserRole:admin']);

// Bulk delete users
Route::post('/users/bulk-delete', [UserController::class, 'bulkDestroy'])->middleware(['auth:sanctum', 'checkUserRole:admin']);


// Public Route
Route::post('/koleksi/count-by-year', [ConnectionTestController::class, 'count']);
Route::get('/test-python-connection', [ConnectionTestController::class, 'testPythonConnection']);
/// Rekomendasi
Route::get('/koleksi/{id}/recommendations', [RecommendationController::class, 'getRecommendations']);
/// YouTube 
Route::prefix('youtube')->group(function () {
    // Testing endpoints
    Route::get('/test', [YoutubeController::class, 'testYoutubeEndpoint']);
    Route::get('/debug/{id}', [YoutubeController::class, 'debugCollection']);
    Route::get('/test-extraction', [YoutubeController::class, 'testUrlExtraction']);
    
    // Existing routes
    Route::get('/embed/{id}', [YoutubeController::class, 'getYoutubeEmbed']);
    
    // Track YouTube video view
    Route::post('/track-view/{id}', [YoutubeController::class, 'trackYoutubeView']);
    
    // Get YouTube thumbnail
    Route::get('/thumbnail/{id}', [YoutubeController::class, 'getYoutubeThumbnail']);
    
    // Validate YouTube URL
    Route::post('/validate-url', [YoutubeController::class, 'validateYoutubeUrl']);
});
/// Youtube alternative routes for direct access
Route::get('/koleksi/{id}/youtube', [YoutubeController::class, 'getYoutubeEmbed']);
Route::post('/koleksi/{id}/youtube/view', [YoutubeController::class, 'trackYoutubeView']);
Route::post('Load', [KoleksiController::class, 'load']); /// gk tau buat apa
Route::get('/koleksi/distribusi-kategori', [KoleksiController::class, 'getDistribusiKategori']);
Route::get('/koleksi/distribusi-jenis', [KoleksiController::class, 'getDistribusiJenis']);
Route::get('/koleksi/most-favorited', [KoleksiController::class, 'getMostFavoritedCollections']);

Route::get('/koleksi/filter', [KoleksiController::class, 'filter']);
Route::get('/koleksi/search', [KoleksiController::class, 'search']);
Route::get('/koleksi/year-range', [KoleksiController::class, 'getYearRange']);
Route::get('/koleksi/total-views', [KoleksiController::class, 'getTotalViews']);
Route::get('/users/count', [UserController::class, 'getUserCount']);
Route::apiResource('koleksi', KoleksiController::class);
Route::apiResource('kategori-bang-kom', KategoriBangKomController::class);
Route::apiResource('jenis-dokumen', JenisDokumenController::class);
Route::apiResource('format-koleksi', FormatKoleksiController::class);


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
// Email Verification Routes
// Route::get('/email/verify/{id}/{token}', [AuthController::class, 'verifyEmail'])
//     ->name('verification.verify');
// Route::post('/email/resend', [AuthController::class, 'resendVerification']);


Route::get('/user/check-auth', [UserController::class, 'checkAuthStatus'])->middleware('auth:user');
// Route::post('/user/login', [UserController::class, 'login']);
// Route::post('/user/register', [UserController::class, 'register']);
// Route::post('/user/logout', [UserController::class, 'logout'])->middleware('auth:user');
// Route::middleware(['auth:user', 'checkAuthType:user', 'checkTokenExpiry'])->group(function () {
// Route::middleware(['auth:sanctum', 'checkUserRole:user'])->group(function () {
Route::middleware(['auth:sanctum', 'checkUserRole:user,admin'])->group(function () {
    
    Route::get('/koleksi/{id}/pdf', [KoleksiController::class, 'showPdf']);

    // Route::get('/koleksi/{id}/pdf', [KoleksiController::class, 'showPdf']);
    Route::post('Load', [KoleksiController::class, 'load']);
    // Route::get('/koleksi/{id}/pdf', [KoleksiController::class, 'showPdfWithWatermark']);

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
// Route::middleware(['auth:admin', 'checkAuthType:admin', 'checkTokenExpiry'])->group(function () {
Route::get('/admin/check-auth', [AdminController::class, 'checkAuthStatus'])->middleware('auth:admin');
Route::middleware(['auth:sanctum', 'checkUserRole:admin'])->group(function () {
    // Route::get('/koleksi/{id}/pdf', [KoleksiController::class, 'showPdfWithWatermark']);
    Route::get('/koleksi/{id}/admin-pdf', [KoleksiController::class, 'showAdminPdf']);
    // Route::get('/koleksi/{id}/public-pdf', [KoleksiController::class, 'showPublicPdf']);
    // Route::get('/koleksi/best-collections', [KoleksiController::class, 'getBestCollections']);
    Route::post('/koleksi/{id}/mark-as-best', [KoleksiController::class, 'markAsBestCollection']);
    Route::post('/koleksi/{id}/unmark-as-best', [KoleksiController::class, 'unmarkAsBestCollection']);
    // Route::get('/best-collections/history', [BestCollectionController::class, 'history']);
    Route::get('koleksi/{id}/edit', [KoleksiController::class, 'edit']);
    // User Management Routes
    Route::prefix('user-management')->group(function () {
        Route::get('/', [UserManagementController::class, 'index']);
        Route::post('/', [UserManagementController::class, 'store']);
        Route::put('/{id}', [UserManagementController::class, 'update']);
        Route::delete('/{id}', [UserManagementController::class, 'destroy']);
        Route::delete('/', [UserManagementController::class, 'bulkDestroy']);
        Route::get('/roles', [UserManagementController::class, 'getRoles']);
    });
});

