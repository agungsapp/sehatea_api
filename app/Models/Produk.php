<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produk extends Model
{
    use SoftDeletes;

    protected $table = 'produk';

    protected $guarded = ['id'];
    // public function komposisi()
    // {
    //     return $this->hasMany(Komposisi::class, 'produk_id');
    // }

    public function komposisiProduk()
    {
        return $this->hasMany(KomposisiProduk::class, 'produk_id');
    }
}
