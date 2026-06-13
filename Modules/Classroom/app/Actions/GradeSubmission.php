<?php

declare(strict_types=1);

namespace Modules\Classroom\Actions;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Modules\Classroom\Models\Submission;

final class GradeSubmission
{
    public function execute(Submission $submission, User $teacher, float $score, ?string $feedback = null): Submission
    {
        $maximum = $submission->assignment->points;
        if ($score < 0 || ($maximum !== null && $score > (float) $maximum)) {
            throw ValidationException::withMessages(['score' => 'The score must be within the assignment point range.']);
        }
        $submission->update(['score' => $score, 'feedback' => $feedback, 'graded_by' => $teacher->id, 'status' => 'returned', 'returned_at' => now()]);

        return $submission->refresh();
    }
}
