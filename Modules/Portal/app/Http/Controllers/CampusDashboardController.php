<?php

declare(strict_types=1);

namespace Modules\Portal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Portal\Enums\PortalRole;
use Modules\Portal\Support\AdminDashboardData;
use Modules\Portal\Support\FacultyDashboardData;
use Modules\Portal\Support\PortalRoleResolver;
use Modules\Portal\Support\StudentDashboardData;

final class CampusDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        Campus $campus,
        PortalRoleResolver $portalRoleResolver,
        StudentDashboardData $studentDashboardData,
        FacultyDashboardData $facultyDashboardData,
        AdminDashboardData $adminDashboardData,
    ): Response {
        $role = $portalRoleResolver->resolve($request->user(), $campus);

        return match ($role) {
            PortalRole::Admin => Inertia::render('portal/AdminDashboard', $adminDashboardData->for($request->user(), $campus)),
            PortalRole::Faculty => Inertia::render('portal/FacultyDashboard', $facultyDashboardData->for($request->user(), $campus)),
            default => Inertia::render('portal/StudentDashboard', $studentDashboardData->for($request->user(), $campus)),
        };
    }
}
