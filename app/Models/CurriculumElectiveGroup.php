<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CurriculumElectiveGroup extends Model
{
    protected $fillable = [
        'curriculum_id', 'code', 'name', 'minimum_subjects', 'maximum_subjects',
        'minimum_units', 'maximum_units',
    ];

    protected $attributes = ['minimum_subjects' => 0, 'minimum_units' => 0];

    protected function casts(): array
    {
        return ['minimum_units' => 'decimal:2', 'maximum_units' => 'decimal:2'];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CurriculumItem::class, 'elective_group_id');
    }
}
