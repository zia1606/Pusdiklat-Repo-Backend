<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqKategori extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'order_number',
    ];

    public function faqs()
    {
        return $this->hasMany(Faq::class);
    }
}
