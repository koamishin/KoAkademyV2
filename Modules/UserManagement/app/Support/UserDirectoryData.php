<?php

declare(strict_types=1);

namespace Modules\UserManagement\Support;

use App\Enums\RoleEnums;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class UserDirectoryData
{
    public function __construct(
        private readonly PortalUserManagementAuthorizer $authorizer,
        private readonly OnlineUserMonitor $onlineUserMonitor,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(Request $request, Campus $campus): array
    {
        $actor = $request->user();
        $visibleQuery = $this->authorizer->scopeVisibleUsers($actor, $campus);
        $visibleUserIds = (clone $visibleQuery)->pluck('users.id')->map(fn ($id): int => (int) $id)->all();
        $onlineUserIds = $this->onlineUserMonitor->onlineUserIds();
        $filters = $this->filters($request, $campus);

        $query = $this->applyFilters(
            $this->authorizer->scopeVisibleUsers($actor, $campus)
                ->with(['campusMemberships.campus:id,name,code,slug', 'person:id,user_id,first_name,last_name,status'])
                ->latest('users.created_at'),
            $filters,
            $onlineUserIds,
        );

        /** @var LengthAwarePaginator<int, User> $users */
        $users = $query
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => $this->serializeUser($user, $campus, $actor));

        return [
            'users' => $users,
            'filters' => $filters,
            'campuses' => $this->campusOptions($actor, $campus),
            'roles' => collect(RoleEnums::cases())
                ->map(fn (RoleEnums $role): array => ['value' => $role->value, 'label' => $role->label()])
                ->values()
                ->all(),
            'onlineUsers' => $this->onlineUserMonitor->onlineUsers($visibleUserIds),
            'can' => [
                'create' => $this->authorizer->canMutateAny($actor, $campus),
                'manage' => $this->authorizer->canMutateAny($actor, $campus),
                'viewGlobal' => $this->authorizer->isGlobalManager($actor),
                'manageableRoles' => $this->authorizer->manageableRoleValues($actor, $campus),
                'manageableCampusIds' => $this->authorizer->manageableCampusIds($actor, $campus),
            ],
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function filters(Request $request, Campus $campus): array
    {
        $defaultCampus = $this->authorizer->isGlobalManager($request->user()) ? null : $campus->slug;

        return [
            'search' => $request->string('search')->toString() ?: null,
            'role' => $request->string('role')->toString() ?: null,
            'campus' => $request->string('campus')->toString() ?: $defaultCampus,
            'verified' => $request->string('verified')->toString() ?: null,
            'online' => $request->string('online')->toString() ?: null,
            'mfa' => $request->string('mfa')->toString() ?: null,
            'age' => $request->string('age')->toString() ?: null,
        ];
    }

    /**
     * @param  Builder<User>  $query
     * @param  array<string, string|null>  $filters
     * @param  list<int>  $onlineUserIds
     * @return Builder<User>
     */
    private function applyFilters(Builder $query, array $filters, array $onlineUserIds): Builder
    {
        return $query
            ->when($filters['search'], function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'], fn (Builder $query, string $role): Builder => $query->whereHas(
                'campusMemberships',
                fn (Builder $query): Builder => $query->where('role', $role),
            ))
            ->when($filters['campus'], fn (Builder $query, string $campusSlug): Builder => $query->whereHas(
                'campusMemberships.campus',
                fn (Builder $query): Builder => $query->where('slug', $campusSlug),
            ))
            ->when($filters['verified'] === 'verified', fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'))
            ->when($filters['verified'] === 'unverified', fn (Builder $query): Builder => $query->whereNull('email_verified_at'))
            ->when($filters['online'] === 'online', fn (Builder $query): Builder => $query->whereIn('id', $onlineUserIds ?: [0]))
            ->when($filters['online'] === 'offline', fn (Builder $query): Builder => $query->whereNotIn('id', $onlineUserIds ?: [0]))
            ->when($filters['mfa'] === 'enabled', function (Builder $query): void {
                $query->where(fn (Builder $query): Builder => $query
                    ->whereNotNull('two_factor_secret')
                    ->orWhereNotNull('app_authentication_secret')
                    ->orWhere('has_email_authentication', true));
            })
            ->when($filters['mfa'] === 'disabled', function (Builder $query): void {
                $query->whereNull('two_factor_secret')
                    ->whereNull('app_authentication_secret')
                    ->where(fn (Builder $query): Builder => $query->whereNull('has_email_authentication')->orWhere('has_email_authentication', false));
            })
            ->when($filters['age'] === '7', fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7)))
            ->when($filters['age'] === '30', fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30)))
            ->when($filters['age'] === 'older', fn (Builder $query): Builder => $query->where('created_at', '<', now()->subDays(30)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function campusOptions(User $actor, Campus $campus): array
    {
        $query = $this->authorizer->isGlobalManager($actor)
            ? Campus::query()
            : Campus::query()->whereKey($campus->getKey());

        return $query->orderBy('name')
            ->get(['id', 'name', 'code', 'slug'])
            ->map(fn (Campus $campus): array => [
                'id' => $campus->id,
                'name' => $campus->name,
                'code' => $campus->code,
                'slug' => $campus->slug,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUser(User $user, Campus $campus, User $actor): array
    {
        $sessions = $this->onlineUserMonitor->sessionsForUserIds([(int) $user->id])->take(5);
        $activeSessions = $sessions->where('online', true)->count();
        $latestSession = $sessions->first();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'personName' => $user->person?->full_name,
            'verified' => $user->email_verified_at !== null,
            'emailVerifiedAt' => $user->email_verified_at?->toISOString(),
            'createdAt' => $user->created_at?->toISOString(),
            'mfaEnabled' => filled($user->two_factor_secret) || filled($user->app_authentication_secret) || (bool) $user->has_email_authentication,
            'online' => $activeSessions > 0,
            'activeSessions' => $activeSessions,
            'lastSeenAt' => $latestSession?->last_seen_at,
            'ipAddress' => $latestSession?->ip_address,
            'userAgent' => $latestSession?->user_agent_summary,
            'memberships' => $user->campusMemberships
                ->map(fn ($membership): array => [
                    'id' => $membership->id,
                    'campusId' => $membership->campus_id,
                    'campusName' => $membership->campus?->name,
                    'campusSlug' => $membership->campus?->slug,
                    'role' => $membership->role->value,
                    'roleLabel' => $membership->role->label(),
                    'active' => $membership->active,
                    'isDefault' => $membership->is_default,
                ])
                ->values()
                ->all(),
            'sessions' => $sessions
                ->map(fn (object $session): array => [
                    'id' => $session->id,
                    'ipAddress' => $session->ip_address,
                    'userAgent' => $session->user_agent_summary,
                    'lastSeenAt' => $session->last_seen_at,
                    'online' => $session->online,
                ])
                ->values()
                ->all(),
            'can' => [
                'manage' => $this->authorizer->canManage($actor, $campus, $user),
                'impersonate' => $this->authorizer->canImpersonate($actor, $campus, $user),
            ],
        ];
    }
}
