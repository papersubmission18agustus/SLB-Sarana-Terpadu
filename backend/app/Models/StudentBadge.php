<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class StudentBadge extends Model
{
    protected $fillable = ['student_id', 'badge_id', 'awarded_at'];
    protected function casts(): array { return ['awarded_at' => 'datetime']; }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function badge(): BelongsTo { return $this->belongsTo(Badge::class); }
}
