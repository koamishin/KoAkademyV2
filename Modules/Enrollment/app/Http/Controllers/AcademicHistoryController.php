<?php

declare(strict_types=1);

namespace Modules\Enrollment\Http\Controllers;

use App\Enums\RoleEnums;
use App\Http\Controllers\Controller;
use App\Support\CurrentCampus;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Enrollment\Models\Enrollment;

final class AcademicHistoryController extends Controller
{
    public function __invoke(CurrentCampus $currentCampus): Response
    {
        $user = request()->user();
        $person = $user->person;
        $membership = $user->campusMemberships()
            ->where('campus_id', $currentCampus->id())
            ->where('active', true)
            ->first();

        abort_unless($membership?->role === RoleEnums::STUDENT, 403);

        return Inertia::render('enrollment/History', [
            'enrollments' => Enrollment::query()
                ->where('campus_id', $currentCampus->id())
                ->where('student_id', $person?->id)
                ->with([
                    'period:id,name',
                    'curriculum:id,program_id,name,code',
                    'curriculum.program:id,name,code',
                    'subjects:id,enrollment_id,curriculum_item_id,status,final_result',
                    'subjects.curriculumItem:id,subject_id,credit_units',
                    'subjects.curriculumItem.subject:id,code,name',
                ])
                ->latest()
                ->get(),
        ]);
    }
}
