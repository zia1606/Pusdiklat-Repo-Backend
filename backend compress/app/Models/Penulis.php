<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penulis extends Model
{
    use HasFactory;

    protected $table = 'penulis';
    protected $fillable = ['nama'];

    public function koleksi()
    {
        return $this->belongsToMany(Koleksi::class, 'koleksi_penulis', 'penulis_id', 'koleksi_id');
    }
}
