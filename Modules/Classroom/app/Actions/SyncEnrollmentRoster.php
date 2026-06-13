<?php

declare(strict_types=1);

namespace Modules\Classroom\Actions;

use Modules\Classroom\Models\ClassMember;
use Modules\Enrollment\Models\Enrollment;

final class SyncEnrollmentRoster
{
    public function execute(Enrollment $enrollment): int
    {
        $count = 0;
        foreach ($enrollment->subjects()->whereNotNull('class_offering_id')->get() as $subject) {
            ClassMember::query()->updateOrCreate(['class_offering_id' => $subject->class_offering_id, 'person_id' => $enrollment->student_id], ['role' => 'student', 'status' => 'active']);
            $count++;
        }

        return $count;
    }
}
