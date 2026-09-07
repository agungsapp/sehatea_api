<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KomposisiProduk extends Model
{
    use SoftDeletes;

    protected $table = 'komposisi_produk';

    protected $guarded = ['id'];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function detail()
    {
        return $this->hasMany(DetailKomposisiProduk::class, 'komposisi_produk_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
