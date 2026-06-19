<?php

declare(strict_types=1);

namespace Modules\Enrollment\Models;

use App\Models\CurriculumItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Classroom\Models\ClassOffering;

final class EnrollmentSubject extends Model
{
    protected $fillable = ['enrollment_id', 'curriculum_item_id', 'class_offering_id', 'status', 'final_result'];

    protected $attributes = ['status' => 'enrolled'];

    public function curriculumItem(): BelongsTo
    {
        return $this->belongsTo(CurriculumItem::class);
    }

    public function classOffering(): BelongsTo
    {
        return $this->belongsTo(ClassOffering::class);
    }
}
