<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class LearningProgress extends Model
{
    protected $fillable = ['student_id', 'category_id', 'completed_materials', 'total_materials', 'progress_percentage', 'average_score', 'last_activity_at'];
    protected function casts(): array { return ['progress_percentage' => 'decimal:2', 'average_score' => 'decimal:2', 'last_activity_at' => 'datetime']; }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function category(): BelongsTo { return $this->belongsTo(MaterialCategory::class, 'category_id'); }
}
