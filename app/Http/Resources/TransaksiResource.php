<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransaksiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode,
            'grand_total' => $this->grand_total,
            'metode_pembayaran' => $this->whenLoaded('metodePembayaran', fn() => $this->metodePembayaran ? [
                'id' => $this->metodePembayaran->id,
                'nama' => $this->metodePembayaran->nama,
            ] : null),
            'metode_pembelian' => $this->whenLoaded('metodePembelian', fn() => $this->metodePembelian ? [
                'id' => $this->metodePembelian->id,
                'nama' => $this->metodePembelian->nama,
            ] : null),
            'user' => $this->whenLoaded('user', fn() => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : null),
            'detail' => $this->whenLoaded('detailTransaksi', fn() => $this->detailTransaksi->map(fn($detail) => [
                'id' => $detail->id,
                'produk_id' => $detail->produk_id,
                'produk' => $detail->produk ? [
                    'id' => $detail->produk->id,
                    'nama' => $detail->produk->nama,
                ] : null,
                'harga' => $detail->harga,
                'qty' => $detail->qty,
                'subtotal' => $detail->subtotal,
            ])->values()),
            'created_at' => $this->created_at,
        ];
    }
}
