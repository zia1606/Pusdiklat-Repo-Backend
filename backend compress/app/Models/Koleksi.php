<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Koleksi extends Model
{
    use HasFactory;

    public function scopeActiveForUser($query)
    {
        if (!Auth::guard('sanctum')->user()) {
            return $query->where('is_active', 1);
        }
        return $query;
    }

    protected $table = 'koleksi';

    protected $fillable = [
        'no_induk',
        'judul',
        'penulis',
        'ringkasan',
        'kategori_id',
        'sub_kategori_id',
        'format_koleksi_id',
        'tahun_terbit',
        'penerbit',
        'edisi_cetak',
        'tempat_terbit',
        'isbn',
        'isbn_digital',
        'issn',
        'jml_hal_romawi',
        'jml_hal_angka',
        'ddc',
        'cutter',
        'call_number',
        'Klasifikasi',
        'kode',
        'thumbnail',
        'id_rak',
        'keywords',
        'dokumen_pdf',
        'youtube_link',
        'views',
        'is_best_collection',
        'preprocessing',
        'is_active'
    ];

    public function penulisList()
    {
        return $this->belongsToMany(Penulis::class, 'koleksi_penulis', 'koleksi_id', 'penulis_id');
    }

    public function kategoris()
    {
        return $this->belongsToMany(Kategori::class, 'koleksi_kategori', 'koleksi_id', 'kategori_id');
    }

    public function subKategoris()
    {
        return $this->belongsToMany(SubKategori::class, 'koleksi_sub_kategori', 'koleksi_id', 'sub_kategori_id');
    }

    public function favorits()
    {
        return $this->hasMany(Favorit::class, 'koleksi_id');
    }
}