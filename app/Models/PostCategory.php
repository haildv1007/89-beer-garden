<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostCategory extends Model
{
    protected $fillable = ['name', 'slug', 'sort_order'];

    public static function labels(): array
    {
        return static::query()->orderBy('sort_order')->orderBy('id')->pluck('name', 'slug')->all();
    }
}
