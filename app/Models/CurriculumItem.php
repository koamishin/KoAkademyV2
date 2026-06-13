<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CurriculumItem extends Model
{
    protected $fillable = [
        'curriculum_id', 'subject_id', 'year_level', 'term_sequence', 'elective_group',
        'credit_units', 'contact_hours', 'lab_hours', 'competency_hours', 'is_required',
    ];

    protected $attributes = ['is_required' => true];

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
