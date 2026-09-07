<?php

namespace Database\Seeders;

use App\Models\Bahan;
use Illuminate\Database\Seeder;

class BahanSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama' => 'Teh Tongji Super', 'jenis' => 'dasar', 'satuan_id' => 1, 'monitor_stok' => false, 'stok_saat_ini' => 0, 'stok_minimum' => 0, 'is_active' => true],
            ['nama' => 'Dandang Abu', 'jenis' => 'dasar', 'satuan_id' => 1, 'monitor_stok' => false, 'stok_saat_ini' => 0, 'stok_minimum' => 0, 'is_active' => true],
            ['nama' => 'Air', 'jenis' => 'dasar', 'satuan_id' => 5, 'monitor_stok' => false, 'stok_saat_ini' => 0, 'stok_minimum' => 0, 'is_active' => true],
            ['nama' => 'Gula', 'jenis' => 'dasar', 'satuan_id' => 1, 'monitor_stok' => false, 'stok_saat_ini' => 0, 'stok_minimum' => 0, 'is_active' => true],
            ['nama' => 'Es Kristal', 'jenis' => 'dasar', 'satuan_id' => 1, 'monitor_stok' => false, 'stok_saat_ini' => 0, 'stok_minimum' => 0, 'is_active' => true],
            ['nama' => 'Es Batu', 'jenis' => 'dasar', 'satuan_id' => 1, 'monitor_stok' => false, 'stok_saat_ini' => 0, 'stok_minimum' => 0, 'is_active' => true],
            ['nama' => 'Gelas Cup 17oz Polos', 'jenis' => 'dasar', 'satuan_id' => 2, 'monitor_stok' => false, 'stok_saat_ini' => 0, 'stok_minimum' => 0, 'is_active' => true],
            ['nama' => 'Gelas Cup 22oz', 'jenis' => 'dasar', 'satuan_id' => 2, 'monitor_stok' => false, 'stok_saat_ini' => 0, 'stok_minimum' => 0, 'is_active' => true],
            ['nama' => 'Gelas Cup 14oz', 'jenis' => 'dasar', 'satuan_id' => 2, 'monitor_stok' => false, 'stok_saat_ini' => 0, 'stok_minimum' => 0, 'is_active' => true],
            ['nama' => 'Javaland Choco Royal', 'jenis' => 'dasar', 'satuan_id' => 1, 'monitor_stok' => true, 'stok_saat_ini' => 0, 'stok_minimum' => 500, 'is_active' => true],
            ['nama' => 'Javaland Mangga', 'jenis' => 'dasar', 'satuan_id' => 1, 'monitor_stok' => true, 'stok_saat_ini' => 0, 'stok_minimum' => 500, 'is_active' => true],
            ['nama' => 'Javaland GreenTea', 'jenis' => 'dasar', 'satuan_id' => 1, 'monitor_stok' => true, 'stok_saat_ini' => 0, 'stok_minimum' => 500, 'is_active' => true],
            ['nama' => 'Maxfood Lemon Tea', 'jenis' => 'dasar', 'satuan_id' => 1, 'monitor_stok' => true, 'stok_saat_ini' => 0, 'stok_minimum' => 500, 'is_active' => true],
            ['nama' => 'Maxfood Lychee Tea', 'jenis' => 'dasar', 'satuan_id' => 1, 'monitor_stok' => true, 'stok_saat_ini' => 0, 'stok_minimum' => 500, 'is_active' => true],
            ['nama' => 'Yakult', 'jenis' => 'dasar', 'satuan_id' => 2, 'monitor_stok' => false, 'stok_saat_ini' => 0, 'stok_minimum' => 0, 'is_active' => true],
            ['nama' => 'Air Teh', 'jenis' => 'olahan', 'satuan_id' => 5, 'monitor_stok' => false, 'stok_saat_ini' => 0, 'stok_minimum' => 0, 'is_active' => true],
            ['nama' => 'Gula Cair', 'jenis' => 'olahan', 'satuan_id' => 5, 'monitor_stok' => false, 'stok_saat_ini' => 0, 'stok_minimum' => 0, 'is_active' => true],
        ];

        foreach ($data as $item) {
            Bahan::create($item);
        }

        $tehTongji = Bahan::where('nama', 'Teh Tongji Super')->firstOrFail();
        $dandang = Bahan::where('nama', 'Dandang Abu')->firstOrFail();
        $air = Bahan::where('nama', 'Air')->firstOrFail();
        $gula = Bahan::where('nama', 'Gula')->firstOrFail();
        $airTeh = Bahan::where('nama', 'Air Teh')->firstOrFail();
        $gulaCair = Bahan::where('nama', 'Gula Cair')->firstOrFail();

        $airTeh->komposisi()->create([
            'hasil_jumlah' => 8000,
            'hasil_satuan' => 'ml',
            'is_active' => true,
        ])->detail()->createMany([
            ['bahan_id' => $tehTongji->id, 'jumlah' => 80],
            ['bahan_id' => $dandang->id, 'jumlah' => 40],
            ['bahan_id' => $air->id, 'jumlah' => 8000],
        ]);

        $gulaCair->komposisi()->create([
            'hasil_jumlah' => 1000,
            'hasil_satuan' => 'ml',
            'is_active' => true,
        ])->detail()->createMany([
            ['bahan_id' => $gula->id, 'jumlah' => 500],
            ['bahan_id' => $air->id, 'jumlah' => 500],
        ]);
    }
}
