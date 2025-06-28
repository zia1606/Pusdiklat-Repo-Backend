<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatBestCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'koleksi_id',
        'action',
        'metadata'
    ];

    public function koleksi()
    {
        return $this->belongsTo(Koleksi::class);
    }
}
