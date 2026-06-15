<?php

declare(strict_types=1);

namespace Modules\Enrollment\Actions;

use App\Models\Curriculum;
use App\Models\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Enrollment\Enums\EnrollmentClassification;
use Modules\Enrollment\Enums\EnrollmentStatus;
use Modules\Enrollment\Models\Enrollment;
use Modules\Enrollment\Models\EnrollmentPeriod;

final class CreateEnrollment
{
    public function execute(Person $student, EnrollmentPeriod $period, Curriculum $curriculum, EnrollmentClassification $classification, ?int $sectionId = null): Enrollment
    {
        if (Enrollment::query()->where('student_id', $student->id)->where('enrollment_period_id', $period->id)->exists()) {
            throw ValidationException::withMessages(['student' => 'This student already has an enrollment for the selected period.']);
        }

        return DB::transaction(function () use ($student, $period, $curriculum, $classification, $sectionId): Enrollment {
            $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'campus_id' => $period->campus_id, 'enrollment_period_id' => $period->id, 'curriculum_id' => $curriculum->id, 'section_id' => $sectionId, 'classification' => $classification, 'status' => EnrollmentStatus::Pending]);
            $enrollment->subjects()->createMany($curriculum->items()->get()->map(fn ($item) => ['curriculum_item_id' => $item->id])->all());

            return $enrollment->load('subjects');
        });
    }
}
