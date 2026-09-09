<?php

namespace App\Http\Controllers\V1;

use App\Enums\JenisBahan;
use App\Http\Controllers\Controller;
use App\Http\Resources\KomposisiBahanResource;
use App\Models\Bahan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BahanController extends Controller
{
    public function index(Request $request)
    {
        $query = Bahan::with('satuan');

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        if ($request->has('monitor_stok')) {
            $query->where('monitor_stok', $request->boolean('monitor_stok'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min($request->integer('per_page', 15), 100);

        $bahans = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data bahan berhasil diambil.',
            'data' => $bahans->items(),
            'meta' => [
                'current_page' => $bahans->currentPage(),
                'last_page' => $bahans->lastPage(),
                'per_page' => $bahans->perPage(),
                'total' => $bahans->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150', 'unique:bahan,nama'],
            'jenis' => ['required', Rule::enum(JenisBahan::class)],
            'satuan_id' => ['required', 'exists:satuan,id'],
            'netto' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'monitor_stok' => ['sometimes', 'boolean'],
            'stok_saat_ini' => ['sometimes', 'numeric', 'min:0'],
            'stok_minimum' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $bahan = Bahan::create([
            'nama' => $validated['nama'],
            'jenis' => $validated['jenis'],
            'satuan_id' => $validated['satuan_id'],
            'netto' => $validated['netto'],
            'monitor_stok' => $validated['monitor_stok'] ?? false,
            'stok_saat_ini' => $validated['stok_saat_ini'] ?? 0,
            'stok_minimum' => $validated['stok_minimum'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bahan berhasil dibuat.',
            'data' => $bahan,
        ], 201);
    }

    public function show(Bahan $bahan)
    {
        $bahan->load(['satuan', 'komposisi.detail.bahan', 'komposisi.satuan']);

        //        return  response()->json($bahan->komposisi->satuan->kode);

        return response()->json([
            'success' => true,
            'message' => 'Data bahan berhasil diambil.',
            'data' => [
                'id' => $bahan->id,
                'nama' => $bahan->nama,
                'jenis' => $bahan->jenis,
                'stok_minimum' => $bahan->stok_minimum,
                'created_at' => $bahan->created_at,
                'updated_at' => $bahan->updated_at,
                'satuan_id' => $bahan->satuan_id,
                'satuan' => $bahan->satuan ? [
                    'id' => $bahan->satuan->id,
                    'kode' => $bahan->satuan->kode,
                    'nama' => $bahan->satuan->nama,
                ] : null,
                'monitor_stok' => $bahan->monitor_stok,
                'netto' => $bahan->netto,
                'stok' => (float) $bahan->stok_saat_ini,
                'stok_minimum' => (float) $bahan->stok_minimum,
                'is_active' => $bahan->is_active,
                'komposisi' => $bahan->komposisi ? [
                    'hasil' => [
                        'jumlah' => (float) $bahan->komposisi->hasil_jumlah,
                        'satuan' => $bahan->komposisi->satuan?->kode,
                        'satuan_id' => $bahan->komposisi->satuan_id,
                    ],
                    'detail' => $bahan->komposisi->detail->map(fn ($detail) => [
                        'bahan_id' => $detail->bahan_id,
                        'nama' => $detail->bahan->nama,
                        'jumlah' => (float) $detail->jumlah,
                        'satuan' => $detail->bahan->satuan->kode,
                    ])->values(),
                ] : null,
            ],
        ]);
    }

    public function update(Request $request, Bahan $bahan)
    {
        $validated = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:150', Rule::unique('bahan', 'nama')->ignore($bahan->id)],
            'jenis' => ['sometimes', Rule::enum(JenisBahan::class)],
            'satuan_id' => ['sometimes', 'required', 'exists:satuan,id'],
            'netto' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'monitor_stok' => ['sometimes', 'boolean'],
            'stok_saat_ini' => ['sometimes', 'numeric'],
            'stok_minimum' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $bahan->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bahan berhasil diperbarui.',
            'data' => $bahan->fresh('komposisi.detail.bahan'),
        ]);
    }

    public function destroy(Bahan $bahan)
    {
        $bahan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bahan berhasil dihapus.',
        ]);
    }

    public function komposisi(Bahan $bahan)
    {
        if ($bahan->jenis !== JenisBahan::OLAHAN) {
            return response()->json([
                'success' => false,
                'message' => 'Bahan ini bukan bahan olahan.',
            ], 422);
        }

        $bahan->loadMissing('komposisi.detail.bahan');

        return response()->json([
            'success' => true,
            'message' => 'Komposisi bahan berhasil diambil.',
            'data' => $bahan->komposisi ? new KomposisiBahanResource($bahan->komposisi) : null,
        ]);
    }

    public function updateKomposisi(Request $request, Bahan $bahan)
    {
        if ($bahan->jenis !== JenisBahan::OLAHAN) {
            return response()->json([
                'success' => false,
                'message' => 'Komposisi hanya dapat dibuat untuk bahan olahan.',
            ], 422);
        }

        $validated = $request->validate([
            'hasil_jumlah' => ['required', 'numeric', 'gt:0'],
            'satuan_id' => ['required', 'integer', 'exists:satuan,id'],
            'detail_komposisi' => ['required', 'array', 'min:1'],
            'detail_komposisi.*.bahan_id' => ['required', 'integer', 'exists:bahan,id'],
            'detail_komposisi.*.jumlah' => ['required', 'numeric', 'gt:0'],
        ]);

        $bahanIds = collect($validated['detail_komposisi'])->pluck('bahan_id');

        if ($bahanIds->contains($bahan->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Bahan tidak dapat menggunakan dirinya sendiri sebagai komposisi.',
            ], 422);
        }

        $komposisi = $bahan->komposisi()->updateOrCreate(
            ['bahan_id' => $bahan->id],
            [
                'hasil_jumlah' => $validated['hasil_jumlah'],
                'satuan_id' => $validated['satuan_id'],
                'is_active' => true,
            ]
        );

        $komposisi->detail()->delete();

        foreach ($validated['detail_komposisi'] as $detail) {
            $komposisi->detail()->create([
                'bahan_id' => $detail['bahan_id'],
                'jumlah' => $detail['jumlah'],
            ]);
        }

        $komposisi->load('detail.bahan');

        return response()->json([
            'success' => true,
            'message' => 'Komposisi bahan berhasil diperbarui.',
            'data' => new KomposisiBahanResource($komposisi),
        ]);
    }
}
