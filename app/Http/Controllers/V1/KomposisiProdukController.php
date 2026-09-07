<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\KomposisiProduk;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KomposisiProdukController extends Controller
{
    public function index(Produk $produk)
    {
        $komposisi = $produk->komposisiProduk()
            ->with('detail.bahan')
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Komposisi produk berhasil diambil.',
            'data' => $komposisi,
        ]);
    }

    public function store(Request $request, Produk $produk)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.bahan_id' => ['required', 'integer', 'exists:bahan,id'],
            'detail.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $komposisi = DB::transaction(function () use ($validated, $produk) {
            $isActive = $validated['is_active'] ?? true;

            if ($isActive) {
                $produk->komposisiProduk()->update([
                    'is_active' => false,
                ]);
            }

            $komposisi = $produk->komposisiProduk()->create([
                'nama' => $validated['nama'],
                'is_active' => $isActive,
            ]);

            foreach ($validated['detail'] as $detail) {
                $komposisi->detail()->create([
                    'bahan_id' => $detail['bahan_id'],
                    'jumlah' => $detail['jumlah'],
                ]);
            }

            return $komposisi;
        });

        $komposisi->load('detail.bahan');

        return response()->json([
            'success' => true,
            'message' => 'Komposisi produk berhasil dibuat.',
            'data' => $komposisi,
        ], 201);
    }

    public function show(Produk $produk, KomposisiProduk $komposisi)
    {
        abort_unless($komposisi->produk_id === $produk->id, 404);

        $komposisi->load('detail.bahan');

        return response()->json([
            'success' => true,
            'message' => 'Komposisi produk berhasil diambil.',
            'data' => $komposisi,
        ]);
    }

    public function update(Request $request, Produk $produk, KomposisiProduk $komposisi)
    {
        abort_unless($komposisi->produk_id === $produk->id, 404);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.bahan_id' => ['required', 'integer', 'exists:bahan,id'],
            'detail.*.jumlah' => ['required', 'numeric', 'gt:0'],
        ]);

        DB::transaction(function () use ($validated, $komposisi) {
            $komposisi->update([
                'nama' => $validated['nama'],
            ]);

            $komposisi->detail()->delete();

            foreach ($validated['detail'] as $detail) {
                $komposisi->detail()->create([
                    'bahan_id' => $detail['bahan_id'],
                    'jumlah' => $detail['jumlah'],
                ]);
            }
        });

        $komposisi->load('detail.bahan');

        return response()->json([
            'success' => true,
            'message' => 'Komposisi produk berhasil diperbarui.',
            'data' => $komposisi,
        ]);
    }

    public function destroy(Produk $produk, KomposisiProduk $komposisi)
    {
        abort_unless($komposisi->produk_id === $produk->id, 404);

        $komposisi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Komposisi produk berhasil dihapus.',
        ]);
    }

    public function activate(Produk $produk, KomposisiProduk $komposisi)
    {
        abort_unless($komposisi->produk_id === $produk->id, 404);

        DB::transaction(function () use ($produk, $komposisi) {
            $produk->komposisiProduk()->update([
                'is_active' => false,
            ]);

            $komposisi->update([
                'is_active' => true,
            ]);
        });

        $komposisi->load('detail.bahan');

        return response()->json([
            'success' => true,
            'message' => 'Komposisi produk berhasil diaktifkan.',
            'data' => $komposisi,
        ]);
    }
}
