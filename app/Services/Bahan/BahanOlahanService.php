<?php

namespace App\Services\Bahan;

use App\Enums\JenisBahan;
use App\Models\Bahan;
use App\Models\KomposisiBahan;
use App\Models\KomposisiBahanDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class BahanOlahanService
{
    /**
     * Rekalkulasi stok potensi dan HPP semua bahan olahan yang menggunakan
     * $bahan sebagai komponen penyusun. Dipanggil setelah stok bahan dasar
     * berubah (mis. pembelian).
     */
    public function syncAfterStockChange(Bahan $bahan): void
    {
        if ($bahan->jenis === JenisBahan::OLAHAN) {
            return;
        }

        try {
            DB::transaction(function () use ($bahan) {
                $komposisiIds = KomposisiBahanDetail::query()
                    ->where('bahan_id', $bahan->id)
                    ->whereNull('deleted_at')
                    ->distinct()
                    ->pluck('komposisi_bahan_id');

                if ($komposisiIds->isEmpty()) {
                    return;
                }

                $komposisiList = KomposisiBahan::query()
                    ->with('detail.bahan')
                    ->whereIn('id', $komposisiIds)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->get();

                foreach ($komposisiList as $komposisi) {
                    $this->syncKomposisi($komposisi);
                }
            });
        } catch (Throwable $e) {
            Log::error('Gagal sinkronisasi bahan olahan', [
                'bahan_id' => $bahan->id,
                'bahan_nama' => $bahan->nama,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                'Gagal memperbarui stok bahan olahan.',
                previous: $e
            );
        }
    }

    protected function syncKomposisi(KomposisiBahan $komposisi): void
    {
        $hasilJumlah = (float) $komposisi->hasil_jumlah;

        if ($hasilJumlah <= 0) {
            return;
        }

        $bahanOlahan = Bahan::whereKey($komposisi->bahan_id)
            ->lockForUpdate()
            ->first();

        if (! $bahanOlahan || $bahanOlahan->jenis !== JenisBahan::OLAHAN) {
            return;
        }

        $details = $komposisi->detail
            ->filter(fn ($detail) => $detail->bahan && (float) $detail->jumlah > 0);

        if ($details->isEmpty()) {
            return;
        }

        $potensi = null;
        $hpp = 0;

        foreach ($details as $detail) {
            $bahanKomponen = $detail->bahan;
            $jumlahKomponen = (float) $detail->jumlah;
            $stokKomponen = (float) $bahanKomponen->stok_saat_ini;
            $hargaKomponen = (float) $bahanKomponen->harga_satuan;

            // Potensi per komponen. Jika stok komponen tidak mencukupi,
            // potensi tidak boleh negatif.
            $potensiKomponen = max((float) 0, ($stokKomponen / $jumlahKomponen) * $hasilJumlah);

            if ($potensi === null || $potensiKomponen < $potensi) {
                $potensi = $potensiKomponen;
            }

            $hpp += ($jumlahKomponen / $hasilJumlah) * $hargaKomponen;
        }

        $bahanOlahan->harga_satuan = $hpp;
        $bahanOlahan->stok_saat_ini = (float) $potensi;
        $bahanOlahan->save();
    }
}
