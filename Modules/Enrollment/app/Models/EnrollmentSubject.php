<?php

declare(strict_types=1);

namespace Modules\Enrollment\Models;

use Illuminate\Database\Eloquent\Model;

final class EnrollmentSubject extends Model
{
    protected $fillable = ['enrollment_id', 'curriculum_item_id', 'class_offering_id', 'status', 'final_result'];

    protected $attributes = ['status' => 'enrolled'];
}
