<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetailKomposisiProduk extends Model
{
    use SoftDeletes;

    protected $table = 'detail_komposisi_produk';

    protected $guarded = ['id'];

    public function komposisiProduk()
    {
        return $this->belongsTo(KomposisiProduk::class, 'komposisi_produk_id');
    }

    public function bahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function scopeActive($query)
    {
        return $query->whereHas('komposisiProduk', function ($q) {
            $q->where('is_active', true);
        });
    }
}
