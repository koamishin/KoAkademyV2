<?php

declare(strict_types=1);

namespace Modules\Admissions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\CurrentCampus;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Enums\ApplicationStatus;
use Modules\Admissions\Models\Application;
use Modules\Portal\Enums\PortalRole;
use Modules\Portal\Support\PortalRoleResolver;

final class AdminApplicationQueueController extends Controller
{
    public function __invoke(CurrentCampus $currentCampus, PortalRoleResolver $portalRoleResolver): Response
    {
        $campus = $currentCampus->get();

        abort_unless($portalRoleResolver->resolve(request()->user(), $campus) === PortalRole::Admin, 403);

        return Inertia::render('admissions/AdminQueue', [
            'applications' => Application::query()
                ->whereBelongsTo($campus)
                ->whereIn('status', [ApplicationStatus::Submitted, ApplicationStatus::UnderReview, ApplicationStatus::NeedsInformation])
                ->with(['person:id,first_name,middle_name,last_name,suffix', 'period:id,name', 'program:id,name'])
                ->latest('submitted_at')
                ->paginate(15)
                ->through(fn (Application $application): array => [
                    'id' => $application->id,
                    'number' => $application->application_number,
                    'studentName' => $application->person?->full_name,
                    'period' => $application->period?->name,
                    'program' => $application->program?->name,
                    'status' => $application->status->value,
                    'submittedAt' => $application->submitted_at?->toFormattedDateString(),
                ]),
            'summary' => [
                'submitted' => Application::query()->whereBelongsTo($campus)->where('status', ApplicationStatus::Submitted)->count(),
                'underReview' => Application::query()->whereBelongsTo($campus)->where('status', ApplicationStatus::UnderReview)->count(),
                'needsInformation' => Application::query()->whereBelongsTo($campus)->where('status', ApplicationStatus::NeedsInformation)->count(),
            ],
        ]);
    }
}
