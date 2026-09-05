<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KategoriPengeluaran extends Model
{
    use SoftDeletes;

    protected $table = 'kategori_pengeluaran';

    protected $guarded = ['id'];
    public function pengeluaran()
    {
        return $this->hasMany(Pengeluaran::class, 'kategori_pengeluaran_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
