<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pengeluaran extends Model
{
    use SoftDeletes;

    protected $table = 'pengeluaran';

    protected $guarded = ['id'];

    public function kategoriPengeluaran()
    {
        return $this->belongsTo(KategoriPengeluaran::class, 'kategori_pengeluaran_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
