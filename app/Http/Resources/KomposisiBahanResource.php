<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KomposisiBahanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'hasil' => [
                'jumlah' => (float) $this->hasil_jumlah,
                'satuan' => $this->satuan->kode,
            ],
            'detail' => $this->whenLoaded('detail', fn() => $this->detail->map(fn($detail) => [
                'bahan_id' => $detail->bahan_id,
                'nama' => $detail->bahan->nama,
                'jumlah' => (float) $detail->jumlah,
                'satuan' => $detail->bahan->satuan->kode,
            ])->values()),
        ];
    }
}
