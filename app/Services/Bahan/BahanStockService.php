<?php

namespace App\Services\Bahan;

use App\Models\Bahan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class BahanStockService
{
    public function increase(
        Bahan $bahan,
        float $qty,
        ?string $keterangan = null,
        ?User $user = null,
        ?float $hargaSatuan = null
    ): Bahan {
        return $this->adjust($bahan, $qty, 'increase', $keterangan, $user, $hargaSatuan);
    }

    public function decrease(
        Bahan $bahan,
        float $qty,
        ?string $keterangan = null,
        ?User $user = null
    ): Bahan {
        return $this->adjust($bahan, $qty, 'decrease', $keterangan, $user);
    }

    protected function adjust(
        Bahan $bahan,
        float $qty,
        string $type,
        ?string $keterangan = null,
        ?User $user = null,
        ?float $hargaSatuan = null
    ): Bahan {
        if ($qty <= 0) {
            throw new InvalidArgumentException('Jumlah stok harus lebih dari 0.');
        }

        $user = $user ?? Auth::user();

        if (! $user) {
            throw new RuntimeException('User tidak ditemukan. Pastikan sudah login.');
        }

        try {
            return DB::transaction(function () use ($bahan, $qty, $type, $keterangan, $user, $hargaSatuan) {
                $bahan = Bahan::where('id', $bahan->id)->lockForUpdate()->firstOrFail();

                $stokSebelum = (float) $bahan->stok_saat_ini;

                if ($type === 'increase') {
                    $stokSesudah = $stokSebelum + $qty;

                    if (! is_null($hargaSatuan) && $hargaSatuan >= 0) {
                        $bahan->harga_satuan = $hargaSatuan;
                    }
                } else {
                    if ($stokSebelum < $qty) {
                        throw new RuntimeException(
                            "Stok \"{$bahan->nama}\" tidak mencukupi. ".
                                "Stok saat ini: {$stokSebelum}, diminta: {$qty}."
                        );
                    }
                    $stokSesudah = $stokSebelum - $qty;
                }

                $bahan->stok_saat_ini = $stokSesudah;
                $bahan->save();

                $this->logStockChange(
                    bahan: $bahan,
                    type: $type,
                    qty: $qty,
                    stokSebelum: $stokSebelum,
                    stokSesudah: $stokSesudah,
                    keterangan: $keterangan,
                    user: $user
                );

                return $bahan->fresh();
            });
        } catch (InvalidArgumentException|RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Gagal menyesuaikan stok bahan', [
                'bahan_id' => $bahan->id,
                'bahan_nama' => $bahan->nama,
                'type' => $type,
                'qty' => $qty,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new RuntimeException(
                'Terjadi kesalahan saat menyesuaikan stok. Silakan coba lagi.',
                previous: $e
            );
        }
    }

    protected function logStockChange(
        Bahan $bahan,
        string $type,
        float $qty,
        float $stokSebelum,
        float $stokSesudah,
        ?string $keterangan,
        User $user
    ): void {
        $action = $type === 'increase' ? 'TAMBAH' : 'KURANGI';

        Log::info("Stok Bahan {$action}", [
            'bahan_id' => $bahan->id,
            'bahan_nama' => $bahan->nama,
            'type' => $type,
            'qty' => $qty,
            'stok_sebelum' => $stokSebelum,
            'stok_sesudah' => $stokSesudah,
            'keterangan' => $keterangan,
            'user_id' => $user->id,
            'user_name' => $user->name ?? $user->email,
            'ip' => request()->ip(),
        ]);
    }
}
