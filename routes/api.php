<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FavoriteController;

/* ---------- Auth ---------- */
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/* ---------- CATEGORÍAS (PÚBLICO) ---------- */
Route::get('/categories', [CategoryController::class, 'index']);

/* ---------- Protegidas ---------- */
Route::middleware('auth:sanctum')->group(function () {

    /* ---------- SESIÓN ---------- */
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    /* ---------- DASHBOARDS ---------- */

    /* Usuario normal */
    Route::middleware('role:user,partner,admin')->get(
        '/user/dashboard',
        [UserController::class, 'dashboard']
    );

    /* Socio */
    Route::middleware('role:partner,admin')->get(
        '/partner/dashboard',
        [PartnerController::class, 'dashboard']
    );

    /* Admin */
    Route::middleware('role:admin')->get(
        '/admin/dashboard',
        [AdminController::class, 'dashboard']
    );

    /* ---------- CATEGORÍAS (ADMIN) ---------- */
    Route::middleware('role:admin')->group(function () {
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    });

    // LUGARES

    // Públicos (solo aprobados)
    Route::get('/places', [PlaceController::class, 'index']);
    Route::get('/places/{slug}', [PlaceController::class, 'show']);

    // Crear / editar (partner / admin)
    Route::middleware('role:partner,admin')->group(function () {
        Route::post('/places', [PlaceController::class, 'store']);
        Route::put('/places/{id}', [PlaceController::class, 'update']);
    });

    // Eliminar (solo admin)
    Route::delete('/places/{id}', [PlaceController::class, 'destroy'])
        ->middleware('role:admin');

    // MODERACIÓN DE LUGARES (ADMIN)
    Route::middleware('role:admin')->group(function () {
        // Lugares pendientes
        Route::get('/admin/places/pending', [PlaceController::class, 'pending']);

        // Moderación
        Route::patch('/places/{id}/approve', [PlaceController::class, 'approve']);
        Route::patch('/places/{id}/reject', [PlaceController::class, 'reject']);
        Route::patch('/places/{id}/needs-fix', [PlaceController::class, 'needsFix']);
    });

    // Favoritos
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{placeId}', [FavoriteController::class, 'destroy']);
});