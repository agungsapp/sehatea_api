<?php

namespace Database\Seeders;

use App\Models\KonversiBahan;
use Illuminate\Database\Seeder;

class KonversiBahanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $konversi = [
            ['bahan_id' => 8, 'satuan_id' => 2, 'nilai_konversi' => 1],
            ['bahan_id' => 8, 'satuan_id' => 10, 'nilai_konversi' => 50],
            ['bahan_id' => 8, 'satuan_id' => 9, 'nilai_konversi' => 1000],
            ['bahan_id' => 10, 'satuan_id' => 9, 'nilai_konversi' => 1000],
            ['bahan_id' => 11, 'satuan_id' => 3, 'nilai_konversi' => 1000],
            ['bahan_id' => 12, 'satuan_id' => 3, 'nilai_konversi' => 1000],
            ['bahan_id' => 13, 'satuan_id' => 3, 'nilai_konversi' => 1000],
            ['bahan_id' => 14, 'satuan_id' => 3, 'nilai_konversi' => 1000],
        ];

        foreach ($konversi as $item) {
            KonversiBahan::create($item);
        }
    }
}
