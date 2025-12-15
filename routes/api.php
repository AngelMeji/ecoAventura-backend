<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\CategoryController;

/* =============================================
   RUTAS PÚBLICAS (sin autenticación)
   ============================================= */

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Categorías (público)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

// Lugares (público - solo aprobados)
Route::get('/places', [PlaceController::class, 'index']);
Route::get('/places/{slug}', [PlaceController::class, 'show']);

/* =============================================
   RUTAS PROTEGIDAS (requieren autenticación)
   ============================================= */

Route::middleware('auth:sanctum')->group(function () {

    // Usuario autenticado
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    /* ---------- Usuario normal ---------- */
    Route::middleware('role:user,partner,admin')->group(function () {
        Route::get('/user/dashboard', [UserController::class, 'dashboard']);
    });

    /* ---------- Socio (Partner) ---------- */
    Route::middleware('role:partner,admin')->group(function () {
        Route::get('/partner/dashboard', [PartnerController::class, 'dashboard']);
        
        // Mis lugares (CRUD para socios)
        Route::get('/partner/places', [PlaceController::class, 'myPlaces']);
        Route::post('/partner/places', [PlaceController::class, 'store']);
        Route::put('/partner/places/{place}', [PlaceController::class, 'update']);
        Route::delete('/partner/places/{place}', [PlaceController::class, 'destroy']);
    });

    /* ---------- Admin ---------- */
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
        
        // Gestión de lugares (admin)
        Route::get('/admin/places', [PlaceController::class, 'adminIndex']);
        Route::patch('/admin/places/{place}/status', [PlaceController::class, 'updateStatus']);
        Route::put('/admin/places/{place}', [PlaceController::class, 'update']);
        Route::delete('/admin/places/{place}', [PlaceController::class, 'destroy']);
    });

});