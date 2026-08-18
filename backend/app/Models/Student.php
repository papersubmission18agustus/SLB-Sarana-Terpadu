<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use HasApiTokens;
    protected $fillable = ['nama', 'tanggal_lahir', 'tempat_lahir', 'nama_orang_tua_wali', 'pendamping_email', 'pendamping_phone', 'current_level_id'];

    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date'];
    }

    public function accessTokens(): HasMany { return $this->hasMany(AccessToken::class); }
    public function currentLevel(): BelongsTo { return $this->belongsTo(Level::class, 'current_level_id'); }
    public function quizAttempts(): HasMany { return $this->hasMany(QuizAttempt::class); }
    public function points(): HasMany { return $this->hasMany(Point::class); }
    public function learningProgress(): HasMany { return $this->hasMany(LearningProgress::class); }
    public function aiInteractions(): HasMany { return $this->hasMany(AiInteraction::class); }
    public function badges(): BelongsToMany { return $this->belongsToMany(Badge::class)->withPivot('awarded_at')->withTimestamps(); }
}
