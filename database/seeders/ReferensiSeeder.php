<?php

namespace Database\Seeders;

use App\Models\MetodePembayaran;
use App\Models\MetodePembelian;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReferensiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pembelians = [
            ['nama' => 'Toko', 'is_active' => true],
            ['nama' => 'Pesanan', 'is_active' => true],
            ['nama' => 'Gojek', 'is_active' => true],
            ['nama' => 'Grab', 'is_active' => true],
            ['nama' => 'Shopee', 'is_active' => true],
        ];

        $pembayarans = [
            ['nama' => 'Qris', 'is_active' => true],
            ['nama' => 'Tunai', 'is_active' => true],
            ['nama' => 'Transfer', 'is_active' => true],
        ];
        // Insert data into metode_pembelian table
        foreach ($pembelians as $pembelian) {
            MetodePembelian::create($pembelian);
        }
        // Insert data into metode_pembayaran table
        foreach ($pembayarans as $pembayaran) {
            MetodePembayaran::create($pembayaran);
        }
    }
}
