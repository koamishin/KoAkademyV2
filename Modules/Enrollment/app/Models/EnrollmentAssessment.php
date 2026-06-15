<?php

declare(strict_types=1);

namespace Modules\Enrollment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class EnrollmentAssessment extends Model
{
    protected $fillable = [
        'enrollment_id', 'currency', 'tuition_total', 'laboratory_total',
        'miscellaneous_total', 'total', 'assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'tuition_total' => 'decimal:2',
            'laboratory_total' => 'decimal:2',
            'miscellaneous_total' => 'decimal:2',
            'total' => 'decimal:2',
            'assessed_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(EnrollmentAssessmentLine::class);
    }
}
