<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Program extends Model
{
    protected $fillable = ['campus_id', 'education_level_id', 'name', 'code', 'award_type', 'status', 'settings'];

    protected $attributes = ['status' => 'active'];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function curricula(): HasMany
    {
        return $this->hasMany(Curriculum::class);
    }
}
