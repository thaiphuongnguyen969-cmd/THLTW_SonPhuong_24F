<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'image',
        'link_url',
        'button_text',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
