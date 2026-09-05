<?php

use App\Http\Controllers\AuthController;
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
        Route::apiResource('transaksi', \App\Http\Controllers\V1\TransaksiController::class);
        Route::apiResource('pengeluaran', \App\Http\Controllers\V1\PengeluaranController::class);
        Route::apiResource('pembelian', \App\Http\Controllers\V1\PembelianController::class);
    });
    // bersama
    Route::middleware('role:admin')->get('/admin/dashboard', function () {
        return ApiResponse::success('Akses admin saja.', ['dashboard' => true]);
    });

    Route::apiResource('produk', \App\Http\Controllers\V1\ProdukController::class);
    Route::apiResource('bahan', \App\Http\Controllers\V1\BahanController::class);
    Route::get('bahan/{bahan}/komposisi', [\App\Http\Controllers\V1\BahanController::class, 'komposisi']);
    Route::put('bahan/{bahan}/komposisi', [\App\Http\Controllers\V1\BahanController::class, 'updateKomposisi']);

    Route::apiResource('kategori-pengeluaran', \App\Http\Controllers\V1\KategoriPengeluaranController::class);
    Route::apiResource('supplier', \App\Http\Controllers\V1\SupplierController::class);

    Route::get('ref/metode-pembelian', [\App\Http\Controllers\V1\ReferensiController::class, 'getMetodePembelian']);
    Route::get('ref/metode-pembayaran', [\App\Http\Controllers\V1\ReferensiController::class, 'getMetodePembayaran']);
    Route::get('ref/kategori-pengeluaran', [\App\Http\Controllers\V1\ReferensiController::class, 'getKategoriPengeluaran']);
    Route::get('ref/kategori-pembelian', [\App\Http\Controllers\V1\ReferensiController::class, 'getKategoriPembelian']);
});
