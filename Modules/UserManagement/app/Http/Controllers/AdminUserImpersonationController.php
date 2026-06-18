<?php

declare(strict_types=1);

namespace Modules\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Modules\UserManagement\Http\Requests\ImpersonateUserRequest;

final class AdminUserImpersonationController extends Controller
{
    public function __invoke(ImpersonateUserRequest $request, Campus $campus, User $user): RedirectResponse
    {
        $request->user()->impersonate($user);

        return redirect()->route('impersonate.take-redirect');
    }
}
