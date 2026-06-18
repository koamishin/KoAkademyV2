<?php

declare(strict_types=1);

namespace Modules\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Modules\UserManagement\Http\Requests\UserManagementActionRequest;

final class AdminUserEmailVerificationController extends Controller
{
    public function __invoke(UserManagementActionRequest $request, Campus $campus, User $user): RedirectResponse
    {
        $user->forceFill(['email_verified_at' => now()])->save();

        return back()->with('status', 'Email marked as verified.');
    }

    public function destroy(UserManagementActionRequest $request, Campus $campus, User $user): RedirectResponse
    {
        $user->forceFill(['email_verified_at' => null])->save();

        return back()->with('status', 'Email marked as unverified.');
    }
}
