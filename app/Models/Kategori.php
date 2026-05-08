<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    use HasFactory;

    protected $table = 'kategori';
    protected $fillable = ['nama'];

    public function subKategori()
    {
        return $this->hasMany(SubKategori::class, 'kategori_id');
    }

    public function koleksi()
    {
        return $this->hasMany(Koleksi::class, 'kategori_id');
    }
}
