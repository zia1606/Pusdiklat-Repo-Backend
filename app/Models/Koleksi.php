<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Koleksi extends Model
{
    use HasFactory;

    protected $table = 'koleksi';

    protected $fillable = [
        'judul',
        'penulis',
        'ringkasan',
        'kategori_bang_kom_id', // Sesuaikan dengan nama kolom di database
        'jenis_dokumen_id',
        'tahun_terbit',
        'penerbit',
        'keywords',
        'dokumen_pdf',
        'youtube_link',
        'views',
        'is_best_collection',
        'preprocessing'
    ];

    public function kategoriBangKom()
    {
        return $this->belongsTo(KategoriBangKom::class, 'kategori_bang_kom_id'); // Tambahkan parameter foreign key
    }

    public function jenisDokumen()
    {
        return $this->belongsTo(JenisDokumen::class, 'jenis_dokumen_id');
    }

        public function favorits()
    {
        return $this->hasMany(Favorit::class);
    }
}