<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BestCollection extends Model
{
    use HasFactory;

    protected $table = 'best_collection';

    protected $fillable = [
        'koleksi_id',
    ];

    // Relasi ke tabel koleksi
    public function koleksi()
    {
        return $this->belongsTo(Koleksi::class, 'koleksi_id');
    }
}