<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AiInteraction extends Model
{
    protected $fillable = ['student_id', 'material_id', 'performance_score', 'level', 'prompt', 'response'];
    protected function casts(): array { return ['performance_score' => 'decimal:2']; }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function material(): BelongsTo { return $this->belongsTo(Material::class); }
}
