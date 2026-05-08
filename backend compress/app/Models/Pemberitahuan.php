<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pemberitahuan extends Model
{
    protected $fillable = [
        'title',
        'description',
        'date',
        'icon_type',
        'color',
        'link',
        'is_active',
    ];
}
