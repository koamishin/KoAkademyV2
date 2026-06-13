<?php

declare(strict_types=1);

namespace Modules\Classroom\Actions;

use App\Models\Person;
use Illuminate\Validation\ValidationException;
use Modules\Classroom\Models\Assignment;
use Modules\Classroom\Models\Submission;

final class SubmitAssignment
{
    public function execute(Assignment $assignment, Person $student, ?string $body): Submission
    {
        $isMember = $assignment->classOffering()->whereHas('members', fn ($q) => $q->where('person_id', $student->id)->where('status', 'active'))->exists();
        if (! $isMember) {
            throw ValidationException::withMessages(['assignment' => 'You are not enrolled in this class.']);
        }

        return Submission::query()->updateOrCreate(['assignment_id' => $assignment->id, 'student_id' => $student->id], ['body' => $body, 'status' => 'submitted', 'submitted_at' => now(), 'returned_at' => null]);
    }
}
