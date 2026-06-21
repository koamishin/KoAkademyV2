<?php

declare(strict_types=1);

namespace Modules\Enrollment\Models;

use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TransferCreditEvaluation extends Model
{
    public const STATUSES = ['draft', 'in_review', 'approved', 'rejected'];

    protected $fillable = [
        'campus_id',
        'student_id',
        'curriculum_id',
        'evaluator_id',
        'source_school_name',
        'source_school_address',
        'previous_program',
        'status',
        'evaluated_at',
        'notes',
        'metadata',
    ];

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return [
            'evaluated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'student_id');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(TransferCreditSubject::class);
    }
}
