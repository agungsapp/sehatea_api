<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Bahan;
use App\Models\Pembelian;
use App\Services\Bahan\BahanStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class PembelianController extends Controller
{
    protected BahanStockService $bahanStockService;

    public function __construct(BahanStockService $bahanStockService)
    {
        $this->bahanStockService = $bahanStockService;
    }

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
                'user:id,name',
                'satuan:id,kode,nama',
            ])
            ->select(
                'id',
                'kode',
                'kategori_pengeluaran_id',
                'bahan_id',
                'qty',
                'satuan_id',
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
        if (! is_null($isActive)) {
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
        $validated = $request->validate([
            'kategori_pengeluaran_id' => ['required', 'exists:kategori_pengeluaran,id'],
            'bahan_id' => ['required', 'exists:bahan,id'],
            'qty' => ['required', 'numeric', 'min:0.0001'],
            'satuan_id' => ['required', 'exists:satuan,id'],
            'total' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $pembelian = DB::transaction(function () use ($validated) {
                $bahan = Bahan::findOrFail($validated['bahan_id']);

                $qtyDasar = $this->hitungQtyDasar(
                    bahan: $bahan,
                    qty: (float) $validated['qty'],
                    satuanId: (int) $validated['satuan_id']
                );

                $hargaSatuan = $qtyDasar > 0
                    ? round($validated['total'] / $qtyDasar, 2)
                    : 0;

                $pembelian = Pembelian::create([
                    'kode' => $this->generateKode(),
                    'kategori_pengeluaran_id' => $validated['kategori_pengeluaran_id'],
                    'bahan_id' => $validated['bahan_id'],
                    'qty' => $validated['qty'],
                    'satuan_id' => $validated['satuan_id'],
                    'total' => $validated['total'],
                    'keterangan' => $validated['keterangan'] ?? null,
                    'user_id' => auth()->id(),
                    'is_active' => $validated['is_active'] ?? true,
                ]);

                $this->bahanStockService->increase(
                    bahan: $bahan,
                    qty: $qtyDasar,
                    keterangan: "Pembelian {$pembelian->kode}".($validated['keterangan'] ? " - {$validated['keterangan']}" : ''),
                    user: auth()->user(),
                    hargaSatuan: $hargaSatuan
                );

                return $pembelian;
            });

            return response()->json([
                'success' => true,
                'message' => 'Pembelian berhasil dibuat.',
                'data' => $pembelian->load([
                    'kategoriPengeluaran:id,nama',
                    'bahan:id,nama,satuan_id,harga_satuan',
                    'satuan:id,nama',
                    'user:id,name',
                ]),
            ], 201);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan pembelian.',
            ], 500);
        }
    }

    /**
     * Display the specified Pembelian.
     */
    public function show(string $id)
    {
        $pembelian = Pembelian::with([
            'kategoriPengeluaran:id,nama',
            'bahan:id,nama',
            'satuan:id,kode,nama',
            'user:id,name',
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
        $validated = $request->validate([
            'kategori_pengeluaran_id' => ['required', 'exists:kategori_pengeluaran,id'],
            'bahan_id' => ['required', 'exists:bahan,id'],
            'qty' => ['required', 'numeric', 'min:0.0001'],
            'satuan_id' => ['required', 'exists:satuan,id'],
            'total' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $pembelian = DB::transaction(function () use ($validated, $id) {
                $pembelian = Pembelian::findOrFail($id);

                // === 1. Kembalikan stok lama (tanpa ubah harga) ===
                $bahanLama = Bahan::findOrFail($pembelian->bahan_id);
                $qtyDasarLama = $this->hitungQtyDasar(
                    bahan: $bahanLama,
                    qty: (float) $pembelian->qty,
                    satuanId: (int) $pembelian->satuan_id
                );

                $this->bahanStockService->decrease(
                    bahan: $bahanLama,
                    qty: $qtyDasarLama,
                    keterangan: "Revisi Pembelian {$pembelian->kode} (stok dikembalikan)",
                    user: auth()->user()
                    // tidak kirim hargaSatuan
                );

                // === 2. Update data pembelian ===
                $pembelian->update([
                    'kategori_pengeluaran_id' => $validated['kategori_pengeluaran_id'],
                    'bahan_id' => $validated['bahan_id'],
                    'qty' => $validated['qty'],
                    'satuan_id' => $validated['satuan_id'],
                    'total' => $validated['total'],
                    'keterangan' => $validated['keterangan'] ?? null,
                    'is_active' => $validated['is_active'] ?? $pembelian->is_active,
                ]);

                // === 3. Tambahkan stok baru + update harga ===
                $bahanBaru = Bahan::findOrFail($validated['bahan_id']);
                $qtyDasarBaru = $this->hitungQtyDasar(
                    bahan: $bahanBaru,
                    qty: (float) $validated['qty'],
                    satuanId: (int) $validated['satuan_id']
                );

                $hargaSatuanBaru = $qtyDasarBaru > 0
                    ? round($validated['total'] / $qtyDasarBaru, 2)
                    : 0;

                $this->bahanStockService->increase(
                    bahan: $bahanBaru,
                    qty: $qtyDasarBaru,
                    keterangan: "Revisi Pembelian {$pembelian->kode}".($validated['keterangan'] ? " - {$validated['keterangan']}" : ''),
                    user: auth()->user(),
                    hargaSatuan: $hargaSatuanBaru   // ← harga diupdate di sini
                );

                return $pembelian;
            });

            return response()->json([
                'success' => true,
                'message' => 'Pembelian berhasil diperbarui',
                'data' => $pembelian->fresh()->load([
                    'kategoriPengeluaran:id,nama',
                    'bahan:id,nama',
                    'satuan:id,kode,nama',
                    'user:id,name',
                ]),
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui pembelian.',
            ], 500);
        }
    }

    /**
     * Remove the specified Pembelian (Soft Delete).
     */
    public function destroy(string $id)
    {
        try {
            DB::transaction(function () use ($id) {
                $pembelian = Pembelian::findOrFail($id);

                $bahan = Bahan::findOrFail($pembelian->bahan_id);
                $qtyDasar = $this->hitungQtyDasar(
                    bahan: $bahan,
                    qty: (float) $pembelian->qty,
                    satuanId: (int) $pembelian->satuan_id
                );

                // Kembalikan stok
                $this->bahanStockService->decrease(
                    bahan: $bahan,
                    qty: $qtyDasar,
                    keterangan: "Hapus Pembelian {$pembelian->kode}",
                    user: auth()->user()
                );

                $pembelian->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Pembelian berhasil dihapus',
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus pembelian.',
            ], 500);
        }
    }

    private function hitungQtyDasar(Bahan $bahan, float $qty, int $satuanId): float
    {
        if ($satuanId === $bahan->satuan_id) {
            return $qty;
        }

        $konversi = $bahan->konversi()
            ->where('satuan_id', $satuanId)
            ->where('is_active', true)
            ->first();

        if (! $konversi) {
            throw new InvalidArgumentException(
                "Satuan yang dipilih tidak memiliki konversi untuk bahan {$bahan->nama}."
            );
        }

        return $qty * (float) $konversi->nilai_konversi;
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

        return 'PB'.str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function satuanByBahan(Request $request)
    {
        $validated = $request->validate([
            'bahan_id' => ['required', 'integer', 'exists:bahan,id'],
        ]);

        $bahan = Bahan::with([
            'konversi' => function ($query) {
                $query->where('is_active', true)->with('satuan:id,kode,nama');
            },
            'satuan:id,kode,nama',
        ])->select(['id', 'nama', 'satuan_id'])->findOrFail($validated['bahan_id']);

        $data = $bahan->konversi->isNotEmpty()
            ? $bahan->konversi->map(fn ($konversi) => [
                'id' => $konversi->satuan_id,
                'kode' => $konversi->satuan->kode,
                'nama' => $konversi->satuan->nama,
                'nilai_konversi' => (float) $konversi->nilai_konversi,
            ])->values()
            : collect([
                [
                    'id' => $bahan->satuan_id,
                    'nama' => $bahan->satuan->nama,
                    'nilai_konversi' => 1,
                ],
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Satuan pembelian berhasil diambil.',
            'data' => $data,
        ]);
    }
}
