<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = ['quiz_id', 'question_text', 'image_path', 'sort_order'];
    public function quiz(): BelongsTo { return $this->belongsTo(Quiz::class); }
    public function answers(): HasMany { return $this->hasMany(QuizAnswer::class); }
}
