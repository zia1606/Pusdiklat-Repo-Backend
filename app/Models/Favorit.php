<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorit extends Model
{
    use HasFactory;

    protected $table = 'favorit';

    protected $fillable = [
        'user_id',
        'koleksi_id',
    ];

    // Relasi ke tabel users
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke tabel koleksi
    public function koleksi()
    {
        return $this->belongsTo(Koleksi::class, 'koleksi_id');
    }
}