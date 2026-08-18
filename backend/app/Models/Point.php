<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    protected $fillable = ['student_id', 'quiz_attempt_id', 'points', 'activity_type', 'description'];
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function quizAttempt(): BelongsTo { return $this->belongsTo(QuizAttempt::class); }
}
