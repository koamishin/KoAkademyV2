<?php

declare(strict_types=1);

namespace Modules\Enrollment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Modules\Classroom\Actions\SyncEnrollmentRoster;
use Modules\Classroom\Models\ClassOffering;
use Modules\Enrollment\Enums\EnrollmentStatus;
use Modules\Enrollment\Http\Requests\UpdateEnrollmentSubjectRequest;
use Modules\Enrollment\Models\Enrollment;
use Modules\Enrollment\Models\EnrollmentSubject;
use Modules\Enrollment\Support\AdminStudentAuthorizer;

final class EnrollmentSubjectController extends Controller
{
    public function update(
        UpdateEnrollmentSubjectRequest $request,
        Campus $campus,
        Person $student,
        Enrollment $enrollment,
        EnrollmentSubject $enrollmentSubject,
        AdminStudentAuthorizer $authorizer,
        SyncEnrollmentRoster $syncEnrollmentRoster,
    ): RedirectResponse {
        $authorizer->abortUnlessCanManage($request->user(), $campus);
        $authorizer->abortUnlessStudentBelongsToCampus($student, $campus);
        abort_unless($enrollment->student_id === $student->id && (int) $enrollment->campus_id === (int) $campus->id, 404);
        abort_unless($enrollmentSubject->enrollment_id === $enrollment->id, 404);

        $validated = $request->validated();
        $classOfferingId = $validated['class_offering_id'] ?? null;

        if ($classOfferingId !== null) {
            $curriculumItem = $enrollmentSubject->curriculumItem()->firstOrFail();
            ClassOffering::query()
                ->whereKey($classOfferingId)
                ->where('campus_id', $campus->id)
                ->where('term_id', $enrollment->period->term_id)
                ->where('subject_id', $curriculumItem->subject_id)
                ->firstOrFail();
        }

        $enrollmentSubject->update([
            'class_offering_id' => $classOfferingId,
            'status' => $validated['status'],
            'final_result' => $validated['final_result'] ?? null,
        ]);

        if ($enrollment->status === EnrollmentStatus::Approved) {
            $syncEnrollmentRoster->execute($enrollment);
        }

        return back()->with('status', 'Enrolled subject updated.');
    }
}
