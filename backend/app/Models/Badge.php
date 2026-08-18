<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'icon_path', 'required_points'];
    public function students(): BelongsToMany { return $this->belongsToMany(Student::class)->withPivot('awarded_at')->withTimestamps(); }
}
