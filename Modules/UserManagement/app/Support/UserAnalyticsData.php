<?php

declare(strict_types=1);

namespace Modules\UserManagement\Support;

use App\Enums\RoleEnums;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;

final class UserAnalyticsData
{
    public function __construct(
        private readonly PortalUserManagementAuthorizer $authorizer,
        private readonly OnlineUserMonitor $onlineUserMonitor,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(User $actor, Campus $campus): array
    {
        $baseQuery = $this->authorizer->scopeVisibleUsers($actor, $campus);
        $visibleUserIds = (clone $baseQuery)->pluck('users.id')->map(fn ($id): int => (int) $id)->all();
        $onlineUserIds = array_values(array_intersect($visibleUserIds, $this->onlineUserMonitor->onlineUserIds()));
        $recentUserIds = DB::table('sessions')
            ->where('last_activity', '>=', $this->onlineUserMonitor->recentThreshold())
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return [
            'cards' => [
                'totalUsers' => (clone $baseQuery)->count(),
                'students' => $this->countWithRole($actor, $campus, RoleEnums::STUDENT),
                'teachers' => $this->countWithRole($actor, $campus, RoleEnums::TEACHER),
                'admins' => $this->countAdminUsers($actor, $campus),
                'applicants' => $this->countWithRole($actor, $campus, RoleEnums::APPLICANT),
                'onlineNow' => count($onlineUserIds),
                'verified' => (clone $baseQuery)->whereNotNull('email_verified_at')->count(),
                'unverified' => (clone $baseQuery)->whereNull('email_verified_at')->count(),
                'mfaEnabled' => (clone $baseQuery)
                    ->where(fn (Builder $query): Builder => $query
                        ->whereNotNull('two_factor_secret')
                        ->orWhereNotNull('app_authentication_secret')
                        ->orWhere('has_email_authentication', true))
                    ->count(),
                'new7Days' => (clone $baseQuery)->where('created_at', '>=', now()->subDays(7))->count(),
                'new30Days' => (clone $baseQuery)->where('created_at', '>=', now()->subDays(30))->count(),
                'inactive30Days' => (clone $baseQuery)->whereNotIn('id', $recentUserIds ?: [0])->count(),
            ],
            'roleBreakdown' => collect(RoleEnums::cases())
                ->map(fn (RoleEnums $role): array => [
                    'role' => $role->value,
                    'label' => $role->label(),
                    'count' => $this->countWithRole($actor, $campus, $role),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentActivity(User $actor, Campus $campus): array
    {
        if (! Schema::hasTable('activity_log')) {
            return [];
        }

        $visibleUserIds = $this->authorizer->scopeVisibleUsers($actor, $campus)
            ->pluck('users.id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return Activity::query()
            ->where(function (Builder $query) use ($visibleUserIds): void {
                $query->where(function (Builder $query) use ($visibleUserIds): void {
                    $query->where('subject_type', User::class)
                        ->whereIn('subject_id', $visibleUserIds ?: [0]);
                })->orWhereIn('causer_id', $visibleUserIds ?: [0]);
            })
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'description' => $activity->description,
                'event' => $activity->event,
                'createdAt' => $activity->created_at?->toISOString(),
                'subjectType' => class_basename((string) $activity->subject_type),
                'causerId' => $activity->causer_id,
            ])
            ->all();
    }

    private function countWithRole(User $actor, Campus $campus, RoleEnums $role): int
    {
        return $this->authorizer->scopeVisibleUsers($actor, $campus)
            ->whereHas(
                'campusMemberships',
                fn (Builder $query): Builder => $query->where('role', $role->value),
            )
            ->count();
    }

    private function countAdminUsers(User $actor, Campus $campus): int
    {
        return $this->authorizer->scopeVisibleUsers($actor, $campus)
            ->whereHas(
                'campusMemberships',
                fn (Builder $query): Builder => $query->whereIn('role', RoleEnums::administrativeValues()),
            )
            ->count();
    }
}
