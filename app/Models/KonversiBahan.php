<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KonversiBahan extends Model
{
    protected $table = 'konversi_bahan';

    protected $fillable = [
        'bahan_id',
        'satuan_id',
        'nilai_konversi',
    ];

    public function bahan()
    {
        return $this->belongsTo(Bahan::class);
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }
}
