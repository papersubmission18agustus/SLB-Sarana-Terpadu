<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = ['category_id', 'created_by', 'title', 'description', 'content', 'pdf_url', 'ppt_url', 'video_url', 'image_path', 'audio_path', 'is_published'];
    protected function casts(): array { return ['is_published' => 'boolean']; }
    public function category(): BelongsTo { return $this->belongsTo(MaterialCategory::class, 'category_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function quizzes(): HasMany { return $this->hasMany(Quiz::class); }
    public function aiInteractions(): HasMany { return $this->hasMany(AiInteraction::class); }
}
