<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ReviewController; // Ensure this is imported
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ChatbotController;

/* ---------- AUTH (Public) ---------- */
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/* ---------- PASSWORD RESET (Public) ---------- */
Route::post('/password/email', [PasswordResetController::class, 'sendResetLink']);
Route::post('/password/reset', [PasswordResetController::class, 'reset']);

/* ---------- CATEGORÍAS (Public) ---------- */
Route::get('/categories', [CategoryController::class, 'index']);
// Place List (Public)
Route::get('/places', [PlaceController::class, 'index']);
Route::get('/places/{id}', [PlaceController::class, 'show']);
Route::post('/places/{id}/chat', [ChatbotController::class, 'chat']);

/* ---------- PROTECTED ROUTES ---------- */
Route::middleware(['auth:sanctum'])->group(function () {

    // AUTH & PROFILE
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/me/password', [ProfileController::class, 'updatePassword']);

    // PLACES (Resource)
    Route::apiResource('places', PlaceController::class)->except(['index', 'show']); // Index/Show are public

    // Approval Workflow
    Route::patch('/places/{id}/set-pending', [PlaceController::class, 'setPending']);
    Route::patch('/places/{id}/approve', [PlaceController::class, 'approve']);
    Route::patch('/places/{id}/reject', [PlaceController::class, 'reject']);
    Route::patch('/places/{id}/needs-fix', [PlaceController::class, 'needsFix']); // Added back as it was useful

    // REVIEWS
    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::post('/places/{placeId}/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

    // ADMIN DASHBOARD & MANAGEMENT (Solo Admin)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/dashboard', [AdminController::class, 'stats']);
        Route::get('/admin/places/pending', [AdminController::class, 'pendingPlaces']);
        Route::get('/admin/places', [AdminController::class, 'allPlaces']);

        // ADMIN USER MANAGEMENT
        Route::get('/admin/users', [AdminController::class, 'indexUsers']);
        Route::post('/admin/users', [AdminController::class, 'createUser']);
        Route::put('/admin/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/admin/users/{id}', [AdminController::class, 'destroyUser']);

        // ADMIN REVIEW MANAGEMENT (Moderación de comentarios)
        Route::get('/admin/reviews', [AdminController::class, 'indexReviews']);
        Route::patch('/admin/reviews/{id}/toggle-hide', [AdminController::class, 'toggleHideReview']);

        // CATEGORIES (Admin management) - Movidas aquí para mayor seguridad
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    });

    // User Profile
    Route::match(['put', 'post'], '/me/profile', [ProfileController::class, 'update']);

    // PARTNER & USER DASHBOARDS
    Route::get('/partner/dashboard', [PartnerController::class, 'dashboard']);
    Route::get('/user/dashboard', [UserController::class, 'dashboard']);

    // FAVORITES
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{placeId}', [FavoriteController::class, 'destroy']);
});