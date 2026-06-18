<?php

declare(strict_types=1);

namespace Modules\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\UserManagement\Http\Requests\StoreUserRequest;
use Modules\UserManagement\Http\Requests\UpdateUserRequest;
use Modules\UserManagement\Support\PortalUserManagementAuthorizer;
use Modules\UserManagement\Support\UserAnalyticsData;
use Modules\UserManagement\Support\UserDirectoryData;

final class AdminUserController extends Controller
{
    public function index(
        Request $request,
        Campus $campus,
        PortalUserManagementAuthorizer $authorizer,
        UserDirectoryData $directoryData,
        UserAnalyticsData $analyticsData,
    ): Response {
        abort_unless($request->user() instanceof User && $authorizer->canView($request->user(), $campus), 403);

        return Inertia::render('user-management/AdminUsers', [
            ...$directoryData->for($request, $campus),
            'analytics' => $analyticsData->for($request->user(), $campus),
            'recentActivity' => $analyticsData->recentActivity($request->user(), $campus),
        ]);
    }

    public function store(StoreUserRequest $request, Campus $campus): RedirectResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'email_verified_at' => ($validated['email_verified'] ?? false) ? now() : null,
                'password' => Hash::make(Str::password(32)),
            ]);

            $this->syncMemberships($user, $validated['memberships']);

            return $user;
        });

        Password::sendResetLink(['email' => $user->email]);

        return back()->with('status', 'User account created and password setup email sent.');
    }

    public function update(UpdateUserRequest $request, Campus $campus, User $user, PortalUserManagementAuthorizer $authorizer): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $campus, $user, $authorizer, $validated): void {
            $user->forceFill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'email_verified_at' => ($validated['email_verified'] ?? false) ? ($user->email_verified_at ?? now()) : null,
            ])->save();

            $this->syncMemberships($user, $validated['memberships'], $authorizer->manageableCampusIds($request->user(), $campus));
        });

        return back()->with('status', 'User account updated.');
    }

    /**
     * @param  list<array<string, mixed>>  $memberships
     * @param  list<int>|null  $syncCampusIds
     */
    private function syncMemberships(User $user, array $memberships, ?array $syncCampusIds = null): void
    {
        $defaultCampusId = collect($memberships)->firstWhere('is_default', true)['campus_id'] ?? $memberships[0]['campus_id'];
        $selectedCampusIds = collect($memberships)->pluck('campus_id')->map(fn ($id): int => (int) $id)->all();

        if ($syncCampusIds !== null) {
            $user->campusMemberships()
                ->whereIn('campus_id', $syncCampusIds)
                ->whereNotIn('campus_id', $selectedCampusIds)
                ->delete();
        }

        foreach ($memberships as $membership) {
            $campusId = (int) $membership['campus_id'];

            $user->campusMemberships()->updateOrCreate(
                ['campus_id' => $campusId],
                [
                    'role' => $membership['role'],
                    'active' => (bool) ($membership['active'] ?? true),
                    'is_default' => $campusId === (int) $defaultCampusId,
                ],
            );
        }
    }
}
