<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use Illuminate\Http\Request;

class PembelianController extends Controller
{
    /**
     * Display a listing of Pembelian.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $kategoriId = $request->get('kategori_pengeluaran_id');
        $bahanId = $request->get('bahan_id');
        $isActive = $request->get('is_active');

        $query = Pembelian::query()
            ->with([
                'kategoriPengeluaran:id,nama',
                'bahan:id,nama',
                'user:id,name'
            ])
            ->select(
                'id',
                'kode',
                'kategori_pengeluaran_id',
                'bahan_id',
                'qty',
                'satuan',
                'total',
                'keterangan',
                'user_id',
                'is_active',
                'created_at',
                'updated_at'
            );

        // Search berdasarkan kode
        if ($search) {
            $query->where('kode', 'LIKE', "%{$search}%");
        }

        // Filter kategori
        if ($kategoriId) {
            $query->where('kategori_pengeluaran_id', $kategoriId);
        }

        // Filter bahan
        if ($bahanId) {
            $query->where('bahan_id', $bahanId);
        }

        // Filter status aktif
        if (!is_null($isActive)) {
            $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
        }

        $data = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data pembelian berhasil diambil',
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
     * Store a newly created Pembelian.
     */
    public function store(Request $request)
    {
        $request->validate([
            'kategori_pengeluaran_id' => 'required|exists:kategori_pengeluaran,id',
            'bahan_id' => 'required|exists:bahan,id',
            'qty' => 'required|integer|min:1',
            'satuan' => 'required|string|max:50',
            'total' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $pembelian = Pembelian::create([
            'kode' => $this->generateKode(),
            'kategori_pengeluaran_id' => $request->kategori_pengeluaran_id,
            'bahan_id' => $request->bahan_id,
            'qty' => $request->qty,
            'satuan' => $request->satuan,
            'total' => $request->total,
            'keterangan' => $request->keterangan,
            'user_id' => auth()->id(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pembelian berhasil dibuat',
            'data' => $pembelian->load([
                'kategoriPengeluaran:id,nama',
                'bahan:id,nama',
                'user:id,name'
            ]),
        ], 201);
    }

    /**
     * Display the specified Pembelian.
     */
    public function show(string $id)
    {
        $pembelian = Pembelian::with([
            'kategoriPengeluaran:id,nama',
            'bahan:id,nama',
            'user:id,name'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $pembelian,
        ]);
    }

    /**
     * Update the specified Pembelian.
     */
    public function update(Request $request, string $id)
    {
        $pembelian = Pembelian::findOrFail($id);

        $request->validate([
            'kategori_pengeluaran_id' => 'required|exists:kategori_pengeluaran,id',
            'bahan_id' => 'required|exists:bahan,id',
            'qty' => 'required|integer|min:1',
            'satuan' => 'required|string|max:50',
            'total' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $pembelian->update([
            'kategori_pengeluaran_id' => $request->kategori_pengeluaran_id,
            'bahan_id' => $request->bahan_id,
            'qty' => $request->qty,
            'satuan' => $request->satuan,
            'total' => $request->total,
            'keterangan' => $request->keterangan,
            'is_active' => $request->boolean('is_active', $pembelian->is_active),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pembelian berhasil diperbarui',
            'data' => $pembelian->fresh()->load([
                'kategoriPengeluaran:id,nama',
                'bahan:id,nama',
                'user:id,name'
            ]),
        ]);
    }

    /**
     * Remove the specified Pembelian (Soft Delete).
     */
    public function destroy(string $id)
    {
        $pembelian = Pembelian::findOrFail($id);

        $pembelian->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pembelian berhasil dihapus',
        ]);
    }

    /**
     * Generate kode otomatis: PB0001, PB0002, dst.
     */
    private function generateKode(): string
    {
        $last = Pembelian::withTrashed()
            ->where('kode', 'like', 'PB%')
            ->orderByDesc('id')
            ->first();

        $number = $last ? (int) substr($last->kode, 2) + 1 : 1;

        return 'PB' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
