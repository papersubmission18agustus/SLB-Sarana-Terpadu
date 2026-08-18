<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $fillable = ['student_id', 'quiz_id', 'answers', 'correct_answers', 'total_questions', 'score', 'duration_seconds', 'completed_at'];
    protected function casts(): array { return ['answers' => 'array', 'score' => 'decimal:2', 'completed_at' => 'datetime']; }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function quiz(): BelongsTo { return $this->belongsTo(Quiz::class); }
    public function points(): HasMany { return $this->hasMany(Point::class); }
}
