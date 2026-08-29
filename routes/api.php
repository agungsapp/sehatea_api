<?php

use App\Http\Controllers\AuthController;
use App\Http\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/passkey', [AuthController::class, 'updatePasskey']);

    // Contoh penggunaan middleware role:
    // Route::middleware('role:admin')->get('/admin/dashboard', function () {
    //     return ApiResponse::success('Akses admin saja.', ['dashboard' => true]);
    // });

    // Route::middleware('role:operator')->get('/operator/dashboard', function () {
    //     return ApiResponse::success('Akses operator saja.', ['dashboard' => true]);
    // });
    Route::middleware('role:admin')->get('/admin/dashboard', function () {
        return ApiResponse::success('Akses admin saja.', ['dashboard' => true]);
    });
});
