<?php

declare(strict_types=1);

namespace Modules\Enrollment\Models;

use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\Person;
use App\Models\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Enrollment\Enums\EnrollmentClassification;
use Modules\Enrollment\Enums\EnrollmentStatus;

final class Enrollment extends Model
{
    protected $fillable = ['student_id', 'campus_id', 'enrollment_period_id', 'curriculum_id', 'section_id', 'student_number', 'classification', 'status', 'approved_by', 'approved_at', 'notes'];

    protected $attributes = ['status' => 'draft'];

    protected static function booted(): void
    {
        self::creating(function (self $enrollment): void {
            $enrollment->campus_id ??= EnrollmentPeriod::query()
                ->whereKey($enrollment->enrollment_period_id)
                ->value('campus_id');
        });
    }

    protected function casts(): array
    {
        return ['classification' => EnrollmentClassification::class, 'status' => EnrollmentStatus::class, 'approved_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'student_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(EnrollmentPeriod::class, 'enrollment_period_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(EnrollmentSubject::class);
    }

    public function assessment(): HasOne
    {
        return $this->hasOne(EnrollmentAssessment::class);
    }
}
