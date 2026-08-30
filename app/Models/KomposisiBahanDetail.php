<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KomposisiBahanDetail extends Model
{
    use SoftDeletes;

    protected $table = 'komposisi_bahan_detail';

    protected $fillable = [
        'komposisi_bahan_id',
        'bahan_id',
        'jumlah',
    ];

    protected $casts = [
        'jumlah' => 'decimal:4',
    ];

    public function komposisiBahan()
    {
        return $this->belongsTo(KomposisiBahan::class);
    }

    public function bahan()
    {
        return $this->belongsTo(Bahan::class);
    }
}
