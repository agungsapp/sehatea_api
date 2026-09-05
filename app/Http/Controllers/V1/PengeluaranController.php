<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Pengeluaran;
use Illuminate\Http\Request;

class PengeluaranController extends Controller
{
    /**
     * Display a listing of Pengeluaran.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $kategoriId = $request->get('kategori_pengeluaran_id');
        $isActive = $request->get('is_active');

        $query = Pengeluaran::query()
            ->with(['kategoriPengeluaran:id,nama', 'user:id,name'])
            ->select(
                'id',
                'kode',
                'kategori_pengeluaran_id',
                'nama',
                'total',
                'keterangan',
                'user_id',
                'is_active',
                'created_at',
                'updated_at'
            );

        // Search berdasarkan kode atau nama
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('kode', 'LIKE', "%{$search}%")
                    ->orWhere('nama', 'LIKE', "%{$search}%");
            });
        }

        // Filter kategori
        if ($kategoriId) {
            $query->where('kategori_pengeluaran_id', $kategoriId);
        }

        // Filter status aktif
        if (!is_null($isActive)) {
            $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
        }

        $data = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data pengeluaran berhasil diambil',
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
     * Store a newly created Pengeluaran.
     */
    public function store(Request $request)
    {
        $request->validate([
            'kategori_pengeluaran_id' => 'required|exists:kategori_pengeluaran,id',
            'nama' => 'nullable|string|max:150',
            'total' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $pengeluaran = Pengeluaran::create([
            'kode' => $this->generateKode(),
            'kategori_pengeluaran_id' => $request->kategori_pengeluaran_id,
            'nama' => $request->nama,
            'total' => $request->total,
            'keterangan' => $request->keterangan,
            'user_id' => auth()->id(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil dibuat',
            'data' => $pengeluaran->load(['kategoriPengeluaran:id,nama', 'user:id,name']),
        ], 201);
    }

    /**
     * Display the specified Pengeluaran.
     */
    public function show(string $id)
    {
        $pengeluaran = Pengeluaran::with(['kategoriPengeluaran:id,nama', 'user:id,name'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $pengeluaran,
        ]);
    }

    /**
     * Update the specified Pengeluaran.
     */
    public function update(Request $request, string $id)
    {
        $pengeluaran = Pengeluaran::findOrFail($id);

        $request->validate([
            'kategori_pengeluaran_id' => 'required|exists:kategori_pengeluaran,id',
            'nama' => 'nullable|string|max:150',
            'total' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $pengeluaran->update([
            'kategori_pengeluaran_id' => $request->kategori_pengeluaran_id,
            'nama' => $request->nama,
            'total' => $request->total,
            'keterangan' => $request->keterangan,
            'is_active' => $request->boolean('is_active', $pengeluaran->is_active),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil diperbarui',
            'data' => $pengeluaran->fresh()->load(['kategoriPengeluaran:id,nama', 'user:id,name']),
        ]);
    }

    /**
     * Remove the specified Pengeluaran (Soft Delete).
     */
    public function destroy(string $id)
    {
        $pengeluaran = Pengeluaran::findOrFail($id);

        $pengeluaran->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil dihapus',
        ]);
    }

    /**
     * Generate kode otomatis: PN0001, PN0002, dst.
     */
    private function generateKode(): string
    {
        $last = Pengeluaran::withTrashed()
            ->where('kode', 'like', 'PN%')
            ->orderByDesc('id')
            ->first();

        $number = $last ? (int) substr($last->kode, 2) + 1 : 1;

        return 'PN' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
