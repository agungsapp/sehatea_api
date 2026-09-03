<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodePembelian extends Model
{
    protected $table = 'metode_pembelian';

    protected $fillable = [
        'nama',
        'is_active',
    ];
}
