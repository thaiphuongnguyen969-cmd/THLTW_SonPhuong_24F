<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'link_type',
        'url',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
