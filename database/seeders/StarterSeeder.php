<?php

namespace Database\Seeders;

use App\Models\KategoriPengeluaran;
use App\Models\Satuan;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class StarterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $satuans = [
            ['nama' => 'gram', 'kode' => 'gr', 'is_active' => true],
            ['nama' => 'pcs', 'kode' => 'pcs', 'is_active' => true],
            ['nama' => 'kilogram', 'kode' => 'kg', 'is_active' => true],
            ['nama' => 'liter', 'kode' => 'ltr', 'is_active' => true],
            ['nama' => 'mililiter', 'kode' => 'ml', 'is_active' => true],
            ['nama' => 'sachet', 'kode' => 'sachet', 'is_active' => true],
            ['nama' => 'botol', 'kode' => 'botol', 'is_active' => true],
            ['nama' => 'kaleng', 'kode' => 'kaleng', 'is_active' => true],
            ['nama' => 'dus', 'kode' => 'dus', 'is_active' => true],
            ['nama' => 'slop', 'kode' => 'slop', 'is_active' => true],
        ];

        $kategoriPengeluaran = [
            ['nama' => 'Bahan Baku', 'is_active' => true],
            ['nama' => 'Gaji Karyawan', 'is_operasional' => true, 'is_active' => true],
            ['nama' => 'Listrik', 'is_operasional' => true, 'is_active' => true],
            ['nama' => 'Air', 'is_operasional' => true, 'is_active' => true],
            ['nama' => 'Internet', 'is_operasional' => true, 'is_active' => true],
            ['nama' => 'Transportasi', 'is_operasional' => true, 'is_active' => true],
            ['nama' => 'Lainnya', 'is_active' => true],
        ];

        $supplier = [
            ['nama' => 'PB'],
            ['nama' => 'Ales Jaya Grosir'],
            ['nama' => 'Haura'],
            ['nama' => 'Taqwa Mulia'],
        ];

        foreach ($satuans as $satuan) {
            Satuan::create($satuan);
        }

        foreach ($kategoriPengeluaran as $kategori) {
            KategoriPengeluaran::create($kategori);
        }

        foreach ($supplier as $supp) {
            Supplier::create($supp);
        }
    }
}
