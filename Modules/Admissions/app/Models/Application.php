<?php

declare(strict_types=1);

namespace Modules\Admissions\Models;

use App\Models\Person;
use App\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Admissions\Enums\ApplicationStatus;

final class Application extends Model
{
    protected $fillable = [
        'person_id', 'admission_period_id', 'program_id', 'application_number', 'status',
        'answers', 'submitted_at', 'decided_by', 'decided_at', 'decision_notes',
    ];

    protected $attributes = ['status' => 'draft'];

    protected static function booted(): void
    {
        self::creating(function (self $application): void {
            $application->public_id ??= (string) Str::uuid();
            $application->application_number ??= 'APP-'.now()->format('Y').'-'.Str::upper(Str::random(8));
        });
    }

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'answers' => 'array',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AdmissionPeriod::class, 'admission_period_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class);
    }
}
