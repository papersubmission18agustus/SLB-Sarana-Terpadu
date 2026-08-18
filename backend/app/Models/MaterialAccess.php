<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialAccess extends Model
{
    protected $fillable = ['student_id', 'material_id', 'accessed_at'];

    protected function casts(): array
    {
        return ['accessed_at' => 'datetime'];
    }

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function material(): BelongsTo { return $this->belongsTo(Material::class); }
}
