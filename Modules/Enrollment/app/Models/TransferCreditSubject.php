<?php

declare(strict_types=1);

namespace Modules\Enrollment\Models;

use App\Models\CurriculumItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TransferCreditSubject extends Model
{
    public const STATUSES = ['pending', 'credited', 'rejected'];

    protected $fillable = [
        'transfer_credit_evaluation_id',
        'curriculum_item_id',
        'previous_subject_code',
        'previous_subject_name',
        'previous_units',
        'previous_grade',
        'school_year',
        'term',
        'status',
        'credited_units',
        'remarks',
        'metadata',
    ];

    protected $attributes = ['status' => 'pending'];

    protected function casts(): array
    {
        return [
            'previous_units' => 'decimal:2',
            'credited_units' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(TransferCreditEvaluation::class, 'transfer_credit_evaluation_id');
    }

    public function curriculumItem(): BelongsTo
    {
        return $this->belongsTo(CurriculumItem::class);
    }
}
