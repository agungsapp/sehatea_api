<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KomposisiBahan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'komposisi_bahan';

    protected $fillable = [
        'bahan_id',
        'hasil_jumlah',
        'hasil_satuan',
        'is_active',
    ];

    protected $casts = [
        'hasil_jumlah' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function bahan()
    {
        return $this->belongsTo(Bahan::class);
    }

    public function detail()
    {
        return $this->hasMany(KomposisiBahanDetail::class);
    }
}
