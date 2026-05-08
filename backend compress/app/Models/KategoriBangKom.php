<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriBangKom extends Model
{
    use HasFactory;

    protected $table = 'kategori_bang_kom';

    protected $fillable = ['nama'];

    public function koleksi()
    {
        return $this->hasMany(Koleksi::class); // Tambahkan parameter foreign key
    }
    // public function koleksi()
    // {
    //     return $this->hasMany(Koleksi::class, 'kategori_bang_kom_id'); // Tambahkan parameter foreign key
    // }
}