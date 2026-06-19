<?php

declare(strict_types=1);

namespace Modules\Enrollment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Enrollment\Actions\CreateEnrollment;
use Modules\Enrollment\Enums\EnrollmentClassification;
use Modules\Enrollment\Enums\EnrollmentStatus;
use Modules\Enrollment\Http\Requests\StoreStudentEnrollmentRequest;
use Modules\Enrollment\Models\Enrollment;
use Modules\Enrollment\Models\EnrollmentPeriod;
use Modules\Enrollment\Support\AdminStudentAuthorizer;

final class StudentEnrollmentController extends Controller
{
    public function store(
        StoreStudentEnrollmentRequest $request,
        Campus $campus,
        Person $student,
        AdminStudentAuthorizer $authorizer,
        CreateEnrollment $createEnrollment,
    ): RedirectResponse {
        $authorizer->abortUnlessCanManage($request->user(), $campus);
        $authorizer->abortUnlessStudentBelongsToCampus($student, $campus);
        $validated = $request->validated();

        $enrollment = $createEnrollment->execute(
            $student,
            EnrollmentPeriod::query()->whereBelongsTo($campus)->findOrFail($validated['enrollment_period_id']),
            Curriculum::query()->whereHas('program', fn ($query) => $query->whereBelongsTo($campus))->findOrFail($validated['curriculum_id']),
            EnrollmentClassification::from($validated['classification']),
            $validated['section_id'] ?? null,
            $validated['selected_elective_item_ids'] ?? [],
            $validated['year_level'] ?? null,
        );

        if (filled($validated['notes'] ?? null)) {
            $enrollment->update(['notes' => $validated['notes']]);
        }

        return back()->with('status', 'Enrollment created for review.');
    }

    public function cancel(Request $request, Campus $campus, Person $student, Enrollment $enrollment, AdminStudentAuthorizer $authorizer): RedirectResponse
    {
        $authorizer->abortUnlessCanManage($request->user(), $campus);
        $authorizer->abortUnlessStudentBelongsToCampus($student, $campus);
        abort_unless($enrollment->student_id === $student->id && (int) $enrollment->campus_id === (int) $campus->id, 404);

        DB::transaction(function () use ($request, $enrollment): void {
            $from = $enrollment->status;
            $enrollment->update(['status' => EnrollmentStatus::Cancelled]);
            DB::table('enrollment_status_histories')->insert([
                'enrollment_id' => $enrollment->id,
                'from_status' => $from->value,
                'to_status' => EnrollmentStatus::Cancelled->value,
                'changed_by' => $request->user()?->id,
                'notes' => $request->string('notes')->toString() ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('status', 'Enrollment cancelled.');
    }
}
