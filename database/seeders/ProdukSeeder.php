<?php

namespace Database\Seeders;

use App\Models\Produk;
use Illuminate\Database\Seeder;

class ProdukSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'nama' => 'Es Teh Original',
                'harga' => 4000,
                'active' => true,
            ],
            [
                'nama' => 'Milktea',
                'harga' => 5000,
                'active' => true,
            ],
            [
                'nama' => 'Lemon Tea',
                'harga' => 5000,
                'active' => true,
            ],
            [
                'nama' => 'Lychee Tea',
                'harga' => 5000,
                'active' => true,
            ],
            [
                'nama' => 'Choco Tea',
                'harga' => 6000,
                'active' => true,
            ],
            [
                'nama' => 'Greentea',
                'harga' => 6000,
                'active' => true,
            ],
            [
                'nama' => 'Mango Tea',
                'harga' => 6000,
                'active' => true,
            ],
            [
                'nama' => 'Mango Yakult Tea',
                'harga' => 8000,
                'active' => true,
            ],
            [
                'nama' => 'Jeruk Peras',
                'harga' => 5000,
                'active' => true,
            ],
            [
                'nama' => 'Jeruk Yakult',
                'harga' => 8000,
                'active' => true,
            ],
            // [
            //     'nama' => 'Iced Yakult Tea',
            //     'harga' => 9000,
            //     'active' => true,
            // ],
            // [
            //     'nama' => 'Es Tawar',
            //     'harga' => 2000,
            //     'active' => true,
            // ],
        ];

        foreach ($data as $produk) {
            Produk::create($produk);
        }
    }
}
