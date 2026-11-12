<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function toolProjects()
    {
        return $this->belongsToMany(ToolProject::class);
    }
}
