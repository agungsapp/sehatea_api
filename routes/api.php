<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\V1\BahanController;
use App\Http\Controllers\V1\KategoriPengeluaranController;
use App\Http\Controllers\V1\KomposisiProdukController;
use App\Http\Controllers\V1\PembelianController;
use App\Http\Controllers\V1\PengeluaranController;
use App\Http\Controllers\V1\ProdukController;
use App\Http\Controllers\V1\ReferensiController;
use App\Http\Controllers\V1\SupplierController;
use App\Http\Controllers\V1\TransaksiController;
use App\Http\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'Running OK!',
        'message' => 'Welcome to the API',
        'version' => '1.0.0',
    ]);
});

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
        Route::apiResource('pengeluaran', PengeluaranController::class);
        Route::get('pembelian/satuan', [PembelianController::class, 'satuanByBahan']);
        Route::apiResource('pembelian', PembelianController::class);

        Route::get('produk/{produk}/komposisi', [KomposisiProdukController::class, 'index']);
        Route::post('produk/{produk}/komposisi', [KomposisiProdukController::class, 'store']);
        Route::get('produk/{produk}/komposisi/{komposisi}', [KomposisiProdukController::class, 'show']);
        Route::put('produk/{produk}/komposisi/{komposisi}', [KomposisiProdukController::class, 'update']);
        Route::delete('produk/{produk}/komposisi/{komposisi}', [KomposisiProdukController::class, 'destroy']);
        Route::patch('produk/{produk}/komposisi/{komposisi}/activate', [KomposisiProdukController::class, 'activate']);
    });
    // bersama
    Route::middleware('role:admin')->get('/admin/dashboard', function () {
        return ApiResponse::success('Akses admin saja.', ['dashboard' => true]);
    });

    Route::apiResource('produk', ProdukController::class);
    Route::apiResource('bahan', BahanController::class);
    Route::get('bahan/{bahan}/komposisi', [BahanController::class, 'komposisi']);
    Route::put('bahan/{bahan}/komposisi', [BahanController::class, 'updateKomposisi']);

    Route::apiResource('kategori-pengeluaran', KategoriPengeluaranController::class);
    Route::apiResource('supplier', SupplierController::class);

    Route::get('ref/metode-pembelian', [ReferensiController::class, 'getMetodePembelian']);
    Route::get('ref/metode-pembayaran', [ReferensiController::class, 'getMetodePembayaran']);
    Route::get('ref/kategori-pengeluaran', [ReferensiController::class, 'getKategoriPengeluaran']);
    Route::get('ref/kategori-pembelian', [ReferensiController::class, 'getKategoriPembelian']);
    Route::get('ref/satuan', [ReferensiController::class, 'getSatuan']);
});
