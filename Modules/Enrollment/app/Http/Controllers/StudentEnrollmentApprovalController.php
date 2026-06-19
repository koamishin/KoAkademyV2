<?php

declare(strict_types=1);

namespace Modules\Enrollment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Enrollment\Actions\ApproveEnrollment;
use Modules\Enrollment\Models\Enrollment;
use Modules\Enrollment\Support\AdminStudentAuthorizer;

final class StudentEnrollmentApprovalController extends Controller
{
    public function store(
        Request $request,
        Campus $campus,
        Person $student,
        Enrollment $enrollment,
        AdminStudentAuthorizer $authorizer,
        ApproveEnrollment $approveEnrollment,
    ): RedirectResponse {
        $authorizer->abortUnlessCanManage($request->user(), $campus);
        $authorizer->abortUnlessStudentBelongsToCampus($student, $campus);
        abort_unless($enrollment->student_id === $student->id && (int) $enrollment->campus_id === (int) $campus->id, 404);

        $approveEnrollment->execute($enrollment, $request->user());

        return back()->with('status', 'Enrollment reviewed.');
    }
}
