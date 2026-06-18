<?php

declare(strict_types=1);

namespace Modules\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Modules\UserManagement\Http\Requests\UserManagementActionRequest;

final class AdminUserPasswordResetController extends Controller
{
    public function __invoke(UserManagementActionRequest $request, Campus $campus, User $user): RedirectResponse
    {
        Password::sendResetLink(['email' => $user->email]);

        return back()->with('status', 'Password reset email sent.');
    }
}
