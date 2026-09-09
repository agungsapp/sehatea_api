<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransaksiResource;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Services\Bahan\BahanConsumptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransaksiController extends Controller
{
    public function __construct(private BahanConsumptionService $bahanConsumptionService) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $perPage = $validated['per_page'] ?? 20;
        $query = Transaksi::with([
            'metodePembayaran:id,nama',
            'metodePembelian:id,nama',
            'detailTransaksi:id,transaksi_id,produk_id,harga,qty,subtotal,user_id',
            'detailTransaksi.produk:id,nama',
            'user:id,name',
        ]);
        if (! empty($validated['date'])) {
            $query->whereDate('created_at', $validated['date']);
        } elseif (! empty($validated['start_date']) || ! empty($validated['end_date'])) {
            $startDate = $validated['start_date'] ?? $validated['end_date'];
            $endDate = $validated['end_date'] ?? $validated['start_date'];
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        } else {
            $query->whereDate('created_at', Carbon::today());
        }
        $transaksi = $query->latest('id')->paginate($perPage);

        return TransaksiResource::collection($transaksi)->additional([
            'success' => true,
            'message' => 'Data transaksi berhasil diambil.',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'produk_id' => ['required', 'array', 'min:1'],
            'produk_id.*' => ['required', 'integer', 'exists:produk,id'],
            'jumlah' => ['required', 'array', 'min:1'],
            'jumlah.*' => ['required', 'integer', 'min:1'],
            'metode_pembayaran_id' => ['required', 'integer', 'exists:metode_pembayaran,id'],
            'metode_pembelian_id' => ['required', 'integer', 'exists:metode_pembelian,id'],
        ]);
        if (count($validated['produk_id']) !== count($validated['jumlah'])) {
            return response()->json([
                'success' => false,
                'message' => 'Jumlah produk dan jumlah qty tidak sesuai.',
            ], 422);
        }
        $transaksi = DB::transaction(function () use ($validated) {
            $produkIds = $validated['produk_id'];
            $produks = Produk::whereIn('id', $produkIds)->get()->keyBy('id');
            $grandTotal = 0;
            foreach ($produkIds as $index => $produkId) {
                $produk = $produks->get($produkId);
                $qty = $validated['jumlah'][$index];
                $grandTotal += $produk->harga * $qty;
            }
            $transaksi = Transaksi::create([
                'kode' => 'TRX-'.Str::upper(Str::random(8)),
                'grand_total' => $grandTotal,
                'metode_pembayaran_id' => $validated['metode_pembayaran_id'],
                'metode_pembelian_id' => $validated['metode_pembelian_id'],
                'user_id' => auth()->id(),
            ]);
            foreach ($produkIds as $index => $produkId) {
                $produk = $produks->get($produkId);
                $qty = $validated['jumlah'][$index];
                $transaksi->detailTransaksi()->create([
                    'produk_id' => $produkId,
                    'harga' => $produk->harga,
                    'qty' => $qty,
                    'subtotal' => $produk->harga * $qty,
                    'user_id' => auth()->id(),
                ]);
            }

            return $transaksi;
        });

        // Pengurangan stok bahan: OPTIONAL. Tidak boleh membuat transaksi gagal.
        $this->bahanConsumptionService->deductForTransaction(
            produkIds: $validated['produk_id'],
            jumlahs: $validated['jumlah'],
            user: auth()->user()
        );

        $transaksi->load([
            'metodePembayaran:id,nama',
            'metodePembelian:id,nama',
            'detailTransaksi:id,transaksi_id,produk_id,harga,qty,subtotal,user_id',
            'detailTransaksi.produk:id,nama',
            'user:id,name',
        ]);

        return (new TransaksiResource($transaksi))->additional([
            'success' => true,
            'message' => 'Transaksi berhasil dibuat.',
        ])->response()->setStatusCode(201);
    }

    public function show(Transaksi $transaksi)
    {
        $transaksi->load([
            'metodePembayaran:id,nama',
            'metodePembelian:id,nama',
            'detailTransaksi:id,transaksi_id,produk_id,harga,qty,subtotal,user_id',
            'detailTransaksi.produk:id,nama',
            'user:id,name',
        ]);

        return (new TransaksiResource($transaksi))->additional([
            'success' => true,
            'message' => 'Data transaksi berhasil diambil.',
        ]);
    }

    public function update(Request $request, Transaksi $transaksi)
    {
        $validated = $request->validate([
            'produk_id' => ['required', 'array', 'min:1'],
            'produk_id.*' => ['required', 'integer', 'exists:produk,id'],
            'jumlah' => ['required', 'array', 'min:1'],
            'jumlah.*' => ['required', 'integer', 'min:1'],
            'metode_pembayaran_id' => ['required', 'integer', 'exists:metode_pembayaran,id'],
            'metode_pembelian_id' => ['required', 'integer', 'exists:metode_pembelian,id'],
        ]);
        if (count($validated['produk_id']) !== count($validated['jumlah'])) {
            return response()->json([
                'success' => false,
                'message' => 'Jumlah produk dan jumlah qty tidak sesuai.',
            ], 422);
        }
        $oldDetail = $transaksi->detailTransaksi()->get();
        $oldProdukIds = collect($oldDetail->map(fn ($d) => $d->produk_id));
        $oldJumlahs = collect($oldDetail->map(fn ($d) => (int) $d->qty));

        DB::transaction(function () use ($validated, $transaksi) {
            $produkIds = $validated['produk_id'];
            $produks = Produk::whereIn('id', $produkIds)->get()->keyBy('id');
            $grandTotal = 0;
            $transaksi->update([
                'metode_pembayaran_id' => $validated['metode_pembayaran_id'],
                'metode_pembelian_id' => $validated['metode_pembelian_id'],
            ]);
            $transaksi->detailTransaksi()->forceDelete();
            foreach ($produkIds as $index => $produkId) {
                $produk = $produks->get($produkId);
                $qty = $validated['jumlah'][$index];
                $subtotal = $produk->harga * $qty;
                $grandTotal += $subtotal;
                $transaksi->detailTransaksi()->create([
                    'produk_id' => $produkId,
                    'harga' => $produk->harga,
                    'qty' => $qty,
                    'subtotal' => $subtotal,
                    'user_id' => auth()->id(),
                ]);
            }
            $transaksi->update([
                'grand_total' => $grandTotal,
            ]);
        });

        // Pengurangan / kembalikan stok: OPTIONAL.
        $this->bahanConsumptionService->adjustForUpdate(
            oldProdukIds: $oldProdukIds,
            oldJumlahs: $oldJumlahs,
            newProdukIds: $validated['produk_id'],
            newJumlahs: $validated['jumlah'],
            user: auth()->user()
        );

        $transaksi->load([
            'metodePembayaran:id,nama',
            'metodePembelian:id,nama',
            'detailTransaksi:id,transaksi_id,produk_id,harga,qty,subtotal,user_id',
            'detailTransaksi.produk:id,nama',
            'user:id,name',
        ]);

        return (new TransaksiResource($transaksi))->additional([
            'success' => true,
            'message' => 'Transaksi berhasil diperbarui.',
        ]);
    }

    public function destroy(Transaksi $transaksi)
    {
        $oldDetail = $transaksi->detailTransaksi()->get();
        $oldProdukIds = collect($oldDetail->map(fn ($d) => $d->produk_id));
        $oldJumlahs = collect($oldDetail->map(fn ($d) => (int) $d->qty));

        DB::transaction(function () use ($transaksi) {
            $transaksi->detailTransaksi()->forceDelete();
            $transaksi->delete();
        });

        // Kembalikan stok: OPTIONAL.
        $this->bahanConsumptionService->restoreForTransaction(
            produkIds: $oldProdukIds,
            jumlahs: $oldJumlahs,
            user: auth()->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dihapus.',
        ]);
    }
}
