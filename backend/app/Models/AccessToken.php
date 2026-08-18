<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    protected $fillable = ['student_id', 'token', 'expires_at', 'is_active', 'created_by'];
    protected $hidden = ['token'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
