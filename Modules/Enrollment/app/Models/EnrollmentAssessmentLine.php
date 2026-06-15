<?php

declare(strict_types=1);

namespace Modules\Enrollment\Models;

use App\Models\CurriculumItem;
use App\Models\CurriculumMiscellaneousFee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EnrollmentAssessmentLine extends Model
{
    protected $fillable = [
        'enrollment_assessment_id', 'curriculum_item_id', 'curriculum_miscellaneous_fee_id',
        'type', 'code', 'description', 'quantity', 'unit_amount', 'amount', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_amount' => 'decimal:2',
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(EnrollmentAssessment::class, 'enrollment_assessment_id');
    }

    public function curriculumItem(): BelongsTo
    {
        return $this->belongsTo(CurriculumItem::class);
    }

    public function miscellaneousFee(): BelongsTo
    {
        return $this->belongsTo(CurriculumMiscellaneousFee::class, 'curriculum_miscellaneous_fee_id');
    }
}
