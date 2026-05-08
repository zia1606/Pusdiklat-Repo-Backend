<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimpanKoleksi extends Model
{
    use HasFactory;

    protected $table = 'simpan_koleksi';
    protected $fillable = ['user_id', 'koleksi_id'];

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke koleksi
    public function koleksi()
    {
        return $this->belongsTo(Koleksi::class);
    }
}
