<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class QuizAnswer extends Model
{
    protected $fillable = ['quiz_question_id', 'answer_text', 'image_path', 'is_correct', 'sort_order'];
    protected function casts(): array { return ['is_correct' => 'boolean']; }
    public function question(): BelongsTo { return $this->belongsTo(QuizQuestion::class, 'quiz_question_id'); }
}
