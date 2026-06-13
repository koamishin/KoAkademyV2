<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Term extends Model
{
    protected $fillable = ['academic_year_id', 'name', 'code', 'sequence', 'starts_on', 'ends_on', 'status'];

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date'];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
