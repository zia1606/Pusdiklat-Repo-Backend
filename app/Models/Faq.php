<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = [
        'faq_kategori_id',
        'question',
        'answer',
        'order_number',
        'is_active',
    ];

    public function kategori()
    {
        return $this->belongsTo(FaqKategori::class, 'faq_kategori_id');
    }
}
