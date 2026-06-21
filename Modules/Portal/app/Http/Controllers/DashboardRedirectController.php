<?php

declare(strict_types=1);

namespace Modules\Portal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\User;
use App\Support\CampusMembershipProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request, CampusMembershipProvisioner $campusMembershipProvisioner): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $campus = $campusMembershipProvisioner->provision($user);

        if (! $campus instanceof Campus) {
            return to_route('campus.assignment.pending');
        }

        return to_route('campus.dashboard', ['campus' => $campus->ensureSlug()]);
    }
}
