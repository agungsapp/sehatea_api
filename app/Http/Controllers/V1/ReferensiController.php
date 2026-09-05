<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\MetodePembayaran;
use App\Models\MetodePembelian;
use Illuminate\Http\Request;

class ReferensiController extends Controller
{
    public function getMetodePembelian()
    {
        return response()->json([
            'success' => true,
            'message' => 'Metode Pembelian berhasil diambil',
            'data' => MetodePembelian::select(['id', 'nama'])->where('is_active', true)->get(),
        ]);
    }
    public function getMetodePembayaran()
    {
        return response()->json([
            'success' => true,
            'message' => 'Metode Pembayaran berhasil diambil',
            'data' => MetodePembayaran::select(['id', 'nama'])->where('is_active', true)->get(),
        ]);
    }

    public function getKategoriPengeluaran()
    {
        return response()->json([
            'success' => true,
            'message' => 'Kategori Pengeluaran berhasil diambil',
            'data' => \App\Models\KategoriPengeluaran::select(['id', 'nama'])->where('is_operasional', true)->where('is_active', true)->get(),
        ]);
    }

    public function getKategoriPembelian()
    {
        return response()->json([
            'success' => true,
            'message' => 'Kategori Pembelian berhasil diambil',
            'data' => \App\Models\KategoriPengeluaran::select(['id', 'nama'])->where('is_operasional', false)->where('is_active', true)->get(),
        ]);
    }
}
