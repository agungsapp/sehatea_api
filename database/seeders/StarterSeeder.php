<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StarterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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

        foreach ($kategoriPengeluaran as $kategori) {
            \App\Models\KategoriPengeluaran::create($kategori);
        }

        foreach ($supplier as $supp) {
            \App\Models\Supplier::create($supp);
        }
    }
}
