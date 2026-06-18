<?php

declare(strict_types=1);

namespace Modules\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Modules\UserManagement\Http\Requests\UserManagementActionRequest;
use Modules\UserManagement\Support\OnlineUserMonitor;

final class AdminUserForceLogoutController extends Controller
{
    public function __invoke(UserManagementActionRequest $request, Campus $campus, User $user, OnlineUserMonitor $onlineUserMonitor): RedirectResponse
    {
        $deleted = $onlineUserMonitor->forceLogout(
            $user,
            $request->user()->is($user) ? $request->session()->getId() : null,
        );

        return back()->with('status', "{$deleted} session(s) ended.");
    }
}
