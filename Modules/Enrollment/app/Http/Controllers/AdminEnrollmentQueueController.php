<?php

declare(strict_types=1);

namespace Modules\Enrollment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\CurrentCampus;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Enrollment\Enums\EnrollmentStatus;
use Modules\Enrollment\Models\Enrollment;
use Modules\Portal\Enums\PortalRole;
use Modules\Portal\Support\PortalRoleResolver;

final class AdminEnrollmentQueueController extends Controller
{
    public function __invoke(CurrentCampus $currentCampus, PortalRoleResolver $portalRoleResolver): Response
    {
        $campus = $currentCampus->get();

        abort_unless($portalRoleResolver->resolve(request()->user(), $campus) === PortalRole::Admin, 403);

        return Inertia::render('enrollment/AdminQueue', [
            'enrollments' => Enrollment::query()
                ->whereBelongsTo($campus)
                ->whereIn('status', [EnrollmentStatus::Draft, EnrollmentStatus::Pending, EnrollmentStatus::Waitlisted])
                ->with(['student:id,first_name,middle_name,last_name,suffix', 'period:id,name', 'curriculum:id,name,code'])
                ->latest()
                ->paginate(15)
                ->through(fn (Enrollment $enrollment): array => [
                    'id' => $enrollment->id,
                    'studentName' => $enrollment->student?->full_name,
                    'studentNumber' => $enrollment->student_number,
                    'period' => $enrollment->period?->name,
                    'curriculum' => $enrollment->curriculum?->name,
                    'status' => $enrollment->status->value,
                    'classification' => $enrollment->classification?->value,
                ]),
            'summary' => [
                'draft' => Enrollment::query()->whereBelongsTo($campus)->where('status', EnrollmentStatus::Draft)->count(),
                'pending' => Enrollment::query()->whereBelongsTo($campus)->where('status', EnrollmentStatus::Pending)->count(),
                'waitlisted' => Enrollment::query()->whereBelongsTo($campus)->where('status', EnrollmentStatus::Waitlisted)->count(),
            ],
        ]);
    }
}
