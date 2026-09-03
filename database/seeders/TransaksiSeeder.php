<?php

namespace Database\Seeders;

use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TransaksiSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $tanggal = Carbon::today()->subDays($i);
            $jumlah = fake()->numberBetween(2, 8);
            for ($j = 0; $j < $jumlah; $j++) {
                Transaksi::factory()
                    ->withDetails()
                    ->create([
                        'created_at' => $tanggal->copy()->setTime(
                            fake()->numberBetween(8, 21),
                            fake()->numberBetween(0, 59),
                            fake()->numberBetween(0, 59)
                        ),
                    ]);
            }
        }
    }
}
