<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of Supplier.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $query = Supplier::query()
            ->select('id', 'nama', 'alamat', 'is_active', 'created_at', 'updated_at');

        // Search berdasarkan nama
        if ($search) {
            $query->where('nama', 'LIKE', "%{$search}%");
        }

        $data = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data supplier berhasil diambil',
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
     * Store a newly created Supplier.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:150|unique:supplier,nama',
            'alamat' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $supplier = Supplier::create([
            'nama' => $request->nama,
            'alamat' => $request->alamat,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil dibuat',
            'data' => $supplier,
        ], 201);
    }

    /**
     * Display the specified Supplier.
     */
    public function show(string $id)
    {
        $supplier = Supplier::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $supplier,
        ]);
    }

    /**
     * Update the specified Supplier.
     */
    public function update(Request $request, string $id)
    {
        $supplier = Supplier::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:150|unique:supplier,nama,' . $id,
            'alamat' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $supplier->update([
            'nama' => $request->nama,
            'alamat' => $request->alamat,
            'is_active' => $request->boolean('is_active', $supplier->is_active),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil diperbarui',
            'data' => $supplier->fresh(),
        ]);
    }

    /**
     * Remove the specified Supplier (Soft Delete).
     */
    public function destroy(string $id)
    {
        $supplier = Supplier::findOrFail($id);

        // Contoh pengecekan relasi (aktifkan kalau sudah ada relasi)
        // if ($supplier->pembelian()->exists()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Tidak dapat menghapus supplier karena masih digunakan.'
        //     ], 422);
        // }

        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil dihapus',
        ]);
    }
}
