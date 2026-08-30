<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    /**
     * Display a listing of Produk.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $query = Produk::query()
            ->select('id', 'nama', 'harga', 'active', 'created_at', 'updated_at');

        // Search berdasarkan nama produk
        if ($search) {
            $query->where('nama', 'LIKE', "%{$search}%");
        }

        $produks = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data produk berhasil diambil',
            'data' => $produks->items(),
            'meta' => [
                'current_page' => $produks->currentPage(),
                'last_page' => $produks->lastPage(),
                'per_page' => $produks->perPage(),
                'total' => $produks->total(),
            ],
        ]);
    }

    /**
     * Store a newly created Produk.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255|unique:produk,nama',
            'harga' => 'required|integer|min:0',
            'active' => 'nullable|boolean',
        ]);

        $produk = Produk::create([
            'nama' => $request->nama,
            'harga' => $request->harga,
            'active' => $request->boolean('active', true), // default true kalau tidak dikirim
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dibuat',
            'data' => $produk,
        ], 201);
    }

    /**
     * Display the specified Produk.
     */
    public function show(string $id)
    {
        $produk = Produk::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $produk,
        ]);
    }

    /**
     * Update the specified Produk.
     */
    public function update(Request $request, string $id)
    {
        $produk = Produk::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:255|unique:produk,nama,'.$id,
            'harga' => 'required|integer|min:0',
            'active' => 'nullable|boolean',
        ]);

        $produk->update([
            'nama' => $request->nama,
            'harga' => $request->harga,
            'active' => $request->boolean('active', $produk->active),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui',
            'data' => $produk->fresh(),
        ]);
    }

    /**
     * Remove the specified Produk.
     */
    public function destroy(string $id)
    {
        $produk = Produk::findOrFail($id);

        // Contoh pengecekan relasi (aktifkan kalau sudah ada relasi)
        // if ($produk->orders()->exists()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Tidak dapat menghapus produk karena masih digunakan.'
        //     ], 422);
        // }

        $produk->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus',
        ]);
    }
}
