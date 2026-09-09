<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\KomposisiProduk;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KomposisiProdukController extends Controller
{
    /**
     * Semua produk (card-based), masing-masing dengan list komposisi (semua versi).
     */
    public function getAllProdukKomposisi()
    {
        $produk = Produk::with('komposisiProduk.detail.bahan')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Semua komposisi produk berhasil diambil.',
            'data' => $produk,
        ]);
    }

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

            // Jika komposisi baru mau diaktifkan, nonaktifkan yang lain dulu
            if ($isActive) {
                $produk->komposisiProduk()
                    ->whereNull('deleted_at')
                    ->update(['is_active' => false]);
            }

            $komposisi = $produk->komposisiProduk()->create([
                'nama' => $validated['nama'],
                'is_active' => $isActive,
            ]);

            // Langsung createMany (validasi sudah min:1)
            $komposisi->detail()->createMany($validated['detail']);

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

            // Hapus detail lama
            $komposisi->detail()->forceDelete();

            // Karena validasi sudah min:1, langsung create saja
            $komposisi->detail()->createMany($validated['detail']);
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

        DB::transaction(function () use ($komposisi) {
            $komposisi->detail()->delete();

            $isActive = (bool) $komposisi->is_active;

            $komposisi->delete();

            // Jika komposisi aktif dihapus, aktifkan resep yang paling baru.
            if ($isActive) {
                $replacement = KomposisiProduk::withTrashed()
                    ->where('produk_id', $komposisi->produk_id)
                    ->whereKeyNot($komposisi->id)
                    ->whereNull('deleted_at')
                    ->latest('id')
                    ->first();

                if ($replacement) {
                    $replacement->update(['is_active' => true]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Komposisi produk berhasil dihapus.',
        ]);
    }

    /**
     * Aktifkan komposisi. Jika komposisi numpum sudah aktif, aksi dino nonaktifkan
     * (tambahkan support toggle on/off tetap satu saja aktif per produk).
     */
    public function activate(Produk $produk, KomposisiProduk $komposisi)
    {
        abort_unless($komposisi->produk_id === $produk->id, 404);

        DB::transaction(function () use ($produk, $komposisi) {
            if ((bool) $komposisi->is_active) {
                $komposisi->update(['is_active' => false]);
            } else {
                $produk->komposisiProduk()
                    ->whereKeyNot($komposisi->id)
                    ->whereNull('deleted_at')
                    ->update(['is_active' => false]);

                $komposisi->update(['is_active' => true]);
            }
        });

        $komposisi->load('detail.bahan');

        return response()->json([
            'success' => true,
            'message' => 'Status komposisi produk berhasil diperbarui.',
            'data' => $komposisi,
        ]);
    }
}
