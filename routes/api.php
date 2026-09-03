<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\V1\BahanController;
use App\Http\Controllers\V1\ProdukController;
use App\Http\Controllers\V1\TransaksiController;
use App\Http\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/user', [AuthController::class, 'user']);
            Route::post('/passkey', [AuthController::class, 'updatePasskey']);
        });
        // Contoh penggunaan middleware role:
        // Route::middleware('role:admin')->get('/admin/dashboard', function () {
        //     return ApiResponse::success('Akses admin saja.', ['dashboard' => true]);
        // });

        // Route::middleware('role:operator')->get('/operator/dashboard', function () {
        //     return ApiResponse::success('Akses operator saja.', ['dashboard' => true]);
        // });

        // admin

        // operator
        Route::apiResource('transaksi', TransaksiController::class);
    });
    // bersama
    Route::middleware('role:admin')->get('/admin/dashboard', function () {
        return ApiResponse::success('Akses admin saja.', ['dashboard' => true]);
    });

    Route::apiResource('produk', ProdukController::class);
    Route::apiResource('bahan', BahanController::class);
    Route::get('bahan/{bahan}/komposisi', [BahanController::class, 'komposisi']);
    Route::put('bahan/{bahan}/komposisi', [BahanController::class, 'updateKomposisi']);

    Route::get('ref/metode-pembelian', [\App\Http\Controllers\V1\ReferensiController::class, 'getMetodePembelian']);
    Route::get('ref/metode-pembayaran', [\App\Http\Controllers\V1\ReferensiController::class, 'getMetodePembayaran']);
});
