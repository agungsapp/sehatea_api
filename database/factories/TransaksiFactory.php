<?php

namespace Database\Factories;

use App\Models\MetodePembayaran;
use App\Models\MetodePembelian;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransaksiFactory extends Factory
{
    protected $model = Transaksi::class;
    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-30 days', 'now');
        return [
            'kode' => 'TRX-' . Str::upper(Str::random(8)),
            'grand_total' => 0,
            'metode_pembayaran_id' => MetodePembayaran::query()->inRandomOrder()->value('id'),
            'metode_pembelian_id' => MetodePembelian::query()->inRandomOrder()->value('id'),
            'user_id' => User::query()->inRandomOrder()->value('id'),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
    public function withDetails(int $min = 1, int $max = 4): static
    {
        return $this->afterCreating(function (Transaksi $transaksi) use ($min, $max) {
            $produk = Produk::query()
                ->inRandomOrder()
                ->limit(fake()->numberBetween($min, $max))
                ->get();
            $grandTotal = 0;
            foreach ($produk as $item) {
                $qty = fake()->numberBetween(1, 5);
                $subtotal = $item->harga * $qty;
                $transaksi->detailTransaksi()->create([
                    'produk_id' => $item->id,
                    'harga' => $item->harga,
                    'qty' => $qty,
                    'subtotal' => $subtotal,
                    'user_id' => $transaksi->user_id,
                ]);
                $grandTotal += $subtotal;
            }
            $transaksi->update([
                'grand_total' => $grandTotal,
            ]);
        });
    }
}
