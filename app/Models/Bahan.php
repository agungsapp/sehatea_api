<?php

namespace App\Models;

use App\Enums\JenisBahan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bahan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bahan';

    protected $fillable = [
        'nama',
        'jenis',
        'satuan_id',
        'harga_satuan',
        'netto',
        'monitor_stok',
        'stok_saat_ini',
        'stok_minimum',
        'is_active',
    ];

    protected $casts = [
        'jenis' => JenisBahan::class,
        'monitor_stok' => 'boolean',
        'harga_satuan' => 'decimal:2',
        'stok_saat_ini' => 'decimal:4',
        'stok_minimum' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function komposisi()
    {
        return $this->hasOne(KomposisiBahan::class);
    }

    public function konversi()
    {
        return $this->hasMany(KonversiBahan::class);
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }
}
