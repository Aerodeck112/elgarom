<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean', 'last_hit_at' => 'datetime'];

    public static function normalize(string $path): string
    {
        $path = '/'.ltrim(rawurldecode($path), '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
