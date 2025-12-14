<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\AdminController;

/* ---------- Auth ---------- */
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/* ---------- Protegidas ---------- */
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

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

});