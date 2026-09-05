<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\KategoriPengeluaran;
use Illuminate\Http\Request;

class KategoriPengeluaranController extends Controller
{
    /**
     * Display a listing of KategoriPengeluaran.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $query = KategoriPengeluaran::query()
            ->select('id', 'nama', 'is_operasional', 'is_active', 'created_at', 'updated_at');

        // Search berdasarkan nama
        if ($search) {
            $query->where('nama', 'LIKE', "%{$search}%");
        }

        $data = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data kategori pengeluaran berhasil diambil',
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    /**
     * Store a newly created KategoriPengeluaran.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:100|unique:kategori_pengeluaran,nama',
            'is_operasional' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $kategori = KategoriPengeluaran::create([
            'nama' => $request->nama,
            'is_operasional' => $request->boolean('is_operasional', false),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori pengeluaran berhasil dibuat',
            'data' => $kategori,
        ], 201);
    }

    /**
     * Display the specified KategoriPengeluaran.
     */
    public function show(string $id)
    {
        $kategori = KategoriPengeluaran::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $kategori,
        ]);
    }

    /**
     * Update the specified KategoriPengeluaran.
     */
    public function update(Request $request, string $id)
    {
        $kategori = KategoriPengeluaran::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:100|unique:kategori_pengeluaran,nama,' . $id,
            'is_operasional' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $kategori->update([
            'nama' => $request->nama,
            'is_operasional' => $request->boolean('is_operasional', $kategori->is_operasional),
            'is_active' => $request->boolean('is_active', $kategori->is_active),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori pengeluaran berhasil diperbarui',
            'data' => $kategori->fresh(),
        ]);
    }

    /**
     * Remove the specified KategoriPengeluaran (Soft Delete).
     */
    public function destroy(string $id)
    {
        $kategori = KategoriPengeluaran::findOrFail($id);

        // Contoh pengecekan relasi (aktifkan kalau sudah ada relasi)
        // if ($kategori->pengeluaran()->exists()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Tidak dapat menghapus kategori karena masih digunakan.'
        //     ], 422);
        // }

        $kategori->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori pengeluaran berhasil dihapus',
        ]);
    }
}
