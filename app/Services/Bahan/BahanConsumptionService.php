<?php

namespace App\Services\Bahan;

use App\Enums\JenisBahan;
use App\Models\Bahan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pengurangan / kembalikan stok bahan saat penjualan produk (transaksi).
 *
 * Stok pengurangan adalah OPTIONAL: transaksi tidak boleh gagal hanya karena
 * pengurangan stok gagal. Semua error yang terjadi di service ini hanya dilog
 * dan tidak dipropagate ke caller.
 */
class BahanConsumptionService
{
    public function __construct(private BahanStockService $bahanStockService) {}

    /**
     * Mengurangi stok bahan penyusun produk berdasarkan transaksi.
     * Untuk bahan olahan, dihitung dengan "pembalikan komposisi".
     */
    public function deductForTransaction(iterable $produkIds, iterable $jumlahs, ?User $user = null): void
    {
        $this->adjustForTransaction($produkIds, $jumlahs, $user, 'decrease');
    }

    /**
     * Kembalikan stok bahan penyusun produk (waste / hapus transaksi).
     * Mirip dengan deductForTransaction, hanya jenis dino 'increase'.
     */
    public function restoreForTransaction(iterable $produkIds, iterable $jumlahs, ?User $user = null): void
    {
        $this->adjustForTransaction($produkIds, $jumlahs, $user, 'increase');
    }

    /**
     * Adjust stok ketika transaksi diperbarui:
     * 1) Kembalikan stok transaksi lama.
     * 2) Kurangi stok transaksi baru.
     * Dua-dua OPTIONAL.
     */
    public function adjustForUpdate(
        iterable $oldProdukIds,
        iterable $oldJumlahs,
        iterable $newProdukIds,
        iterable $newJumlahs,
        ?User $user = null
    ): void {
        $this->restoreForTransaction($oldProdukIds, $oldJumlahs, $user);
        $this->deductForTransaction($newProdukIds, $newJumlahs, $user);
    }

    /**
     * Core logic: mengurangi (decrease) atau kembalikan (increase) stok.
     */
    protected function adjustForTransaction(
        iterable $produkIds,
        iterable $jumlahs,
        ?User $user,
        string $type
    ): void {
        try {
            $produks = Produk::whereIn('id', $produkIds)->get()->keyBy('id');

            $byBahan = collect();

            foreach ($produkIds as $index => $produkId) {
                $produk = $produks->get($produkId);
                $qtyProduk = (float) $jumlahs[$index];

                if (! $produk) {
                    continue;
                }

                $konsumsi = $this->produkConsumption($produk, $qtyProduk);

                foreach ($konsumsi as $bahanId => $qty) {
                    $byBahan[$bahanId] = ($byBahan[$bahanId] ?? 0) + $qty;
                }
            }

            if ($byBahan->isEmpty()) {
                return;
            }

            DB::transaction(function () use ($byBahan, $user, $type) {
                $bahanList = Bahan::whereIn('id', $byBahan->keys())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($byBahan as $bahanId => $qty) {
                    $bahan = $bahanList->get($bahanId);

                    if (! $bahan) {
                        continue;
                    }

                    $qty = (float) $qty;

                    if ($type === 'decrease') {
                        $this->bahanStockService->decrease(
                            bahan: $bahan,
                            qty: $qty,
                            keterangan: 'Penjualan produk (transaksi)',
                            user: $user
                        );
                    } else {
                        $this->bahanStockService->increase(
                            bahan: $bahan,
                            qty: $qty,
                            keterangan: 'Restok produk (transaksi)',
                            user: $user
                        );
                    }
                }
            });
        } catch (Throwable $e) {
            // Stok pengurangan OPTIONAL: transaksi tetap jalan.
            Log::error('Gagal menyesuaikan stok bahan saat transaksi', [
                'type' => $type,
                'produk_ids' => $produkIds,
                'jumlahs' => $jumlahs,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Komposisi stok yang terkurangi untuk 1 produk (qty produk).
     * Return: [bahan_id => qty].
     */
    protected function produkConsumption(Produk $produk, float $qtyProduk): Collection
    {
        $komposisi = $produk->komposisiProduk()
            ->where('is_active', true)
            ->with('detail.bahan')
            ->first();

        if (! $komposisi) {
            return collect();
        }

        $konsumsi = collect();

        foreach ($komposisi->detail as $detail) {
            $bahan = $detail->bahan;
            $jumlah = (float) $detail->jumlah;

            if (! $bahan) {
                continue;
            }

            if ($bahan->jenis === JenisBahan::OLAHAN) {
                // Olahan: mengurangi stok mentah bahan olahan sendiri
                // + komponen penyusun (pembalikan komposisi).
                $konsumsi[$bahan->id] = ($konsumsi[$bahan->id] ?? 0) + ($jumlah * $qtyProduk);

                $this->olahanLeaf(
                    bahanOlahan: $bahan,
                    jumlahOlahan: $jumlah,
                    qtyProduk: $qtyProduk,
                    konsumsi: $konsumsi
                );
            } else {
                $konsumsi[$bahan->id] = ($konsumsi[$bahan->id] ?? 0) + ($jumlah * $qtyProduk);
            }
        }

        return $konsumsi;
    }

    /**
     * Untuk bahan olahan, menambahkan qty komponen penyusun (pembalikan komposisi)
     * ke dalam $konsumsi.
     */
    protected function olahanLeaf(Bahan $bahanOlahan, float $jumlahOlahan, float $qtyProduk, Collection $konsumsi): void
    {
        $komposisi = $bahanOlahan->komposisi()
            ->where('is_active', true)
            ->with('detail.bahan')
            ->first();

        if (! $komposisi || (float) $komposisi->hasil_jumlah <= 0) {
            return;
        }

        $hasilJumlah = (float) $komposisi->hasil_jumlah;

        foreach ($komposisi->detail as $detailLeaf) {
            $bahanLeaf = $detailLeaf->bahan;

            if (! $bahanLeaf) {
                continue;
            }

            // Proposional: (jumlah_komponen / hasil) * jumlah_olahan * qty_produk
            $qtyLeaf = ((float) $detailLeaf->jumlah / $hasilJumlah) * $jumlahOlahan * $qtyProduk;
            $konsumsi[$bahanLeaf->id] = ($konsumsi[$bahanLeaf->id] ?? 0) + $qtyLeaf;
        }
    }
}
