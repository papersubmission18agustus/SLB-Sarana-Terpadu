<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = ['material_id', 'title', 'description', 'passing_score'];
    public function material(): BelongsTo { return $this->belongsTo(Material::class); }
    public function questions(): HasMany { return $this->hasMany(QuizQuestion::class); }
    public function attempts(): HasMany { return $this->hasMany(QuizAttempt::class); }
}
