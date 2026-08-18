<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class MaterialCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description'];
    public function materials(): HasMany { return $this->hasMany(Material::class, 'category_id'); }
    public function learningProgress(): HasMany { return $this->hasMany(LearningProgress::class, 'category_id'); }
}
