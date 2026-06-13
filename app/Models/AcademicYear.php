<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AcademicYear extends Model
{
    protected $fillable = ['institution_id', 'name', 'starts_on', 'ends_on', 'status', 'is_current'];

    protected $attributes = ['status' => 'draft', 'is_current' => false];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'is_current' => 'boolean'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }
}
