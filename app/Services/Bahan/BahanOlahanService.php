<?php

namespace App\Services\Bahan;

use App\Models\Bahan;
use App\Models\KomposisiBahan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class BahanOlahanService
{
    public function syncAfterStockChange(Bahan $bahan): void
    {
        if ($bahan->jenis !== 'dasar') {
     Log::info('service bahan olahan tidak dijalankan');
            return;
        }
     Log::info('service bahan olahan dijalankan');

        try {
            DB::transaction(function () use ($bahan) {
                $komposisiList = KomposisiBahan::with('details.bahan')
                    ->where('bahan_id', $bahan->id)
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

        if (! $bahanOlahan || $bahanOlahan->jenis !== 'olahan') {
            return;
        }

        $details = $komposisi->details
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

            $potensiKomponen = ($stokKomponen / $jumlahKomponen) * $hasilJumlah;

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
