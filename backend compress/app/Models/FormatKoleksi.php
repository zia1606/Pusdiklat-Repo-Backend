<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormatKoleksi extends Model
{
    use HasFactory;

    protected $table = 'format_koleksi';

    protected $fillable = [
        'nama',
    ];

    // Relasi ke tabel koleksi
    public function koleksi()
    {
        return $this->hasMany(Koleksi::class, 'format_koleksi_id');
    }
}