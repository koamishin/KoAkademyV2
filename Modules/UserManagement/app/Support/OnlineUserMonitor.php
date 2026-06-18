<?php

declare(strict_types=1);

namespace Modules\UserManagement\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class OnlineUserMonitor
{
    public function onlineThreshold(): int
    {
        return now()->subMinutes(5)->timestamp;
    }

    public function recentThreshold(): int
    {
        return now()->subDays(30)->timestamp;
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, object>
     */
    public function sessionsForUserIds(array $userIds, bool $onlineOnly = false): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        return DB::table('sessions')
            ->whereIn('user_id', $userIds)
            ->when($onlineOnly, fn ($query) => $query->where('last_activity', '>=', $this->onlineThreshold()))
            ->orderByDesc('last_activity')
            ->get()
            ->map(function (object $session): object {
                $session->last_seen_at = Carbon::createFromTimestamp((int) $session->last_activity)->toISOString();
                $session->user_agent_summary = $this->summarizeUserAgent((string) ($session->user_agent ?? ''));
                $session->online = (int) $session->last_activity >= $this->onlineThreshold();

                return $session;
            });
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, array{active: int, total: int, last_seen_at: string|null, ip_address: string|null, user_agent: string|null}>
     */
    public function sessionSummaryForUserIds(array $userIds): array
    {
        return $this->sessionsForUserIds($userIds)
            ->groupBy('user_id')
            ->map(function (Collection $sessions): array {
                $latest = $sessions->first();

                return [
                    'active' => $sessions->where('online', true)->count(),
                    'total' => $sessions->count(),
                    'last_seen_at' => $latest?->last_seen_at,
                    'ip_address' => $latest?->ip_address,
                    'user_agent' => $latest?->user_agent_summary,
                ];
            })
            ->all();
    }

    /**
     * @return list<int>
     */
    public function onlineUserIds(): array
    {
        return DB::table('sessions')
            ->where('last_activity', '>=', $this->onlineThreshold())
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @param  list<int>  $visibleUserIds
     * @return list<array<string, mixed>>
     */
    public function onlineUsers(array $visibleUserIds): array
    {
        $onlineIds = array_values(array_intersect($visibleUserIds, $this->onlineUserIds()));
        $sessionSummary = $this->sessionSummaryForUserIds($onlineIds);

        return User::query()
            ->whereKey($onlineIds)
            ->with(['campusMemberships.campus:id,name,code,slug'])
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->campusMemberships->pluck('role.value')->unique()->values()->all(),
                'activeSessions' => $sessionSummary[$user->id]['active'] ?? 0,
                'lastSeenAt' => $sessionSummary[$user->id]['last_seen_at'] ?? null,
                'ipAddress' => $sessionSummary[$user->id]['ip_address'] ?? null,
                'userAgent' => $sessionSummary[$user->id]['user_agent'] ?? null,
            ])
            ->all();
    }

    public function forceLogout(User $target, ?string $exceptSessionId = null): int
    {
        return DB::table('sessions')
            ->where('user_id', $target->getKey())
            ->when($exceptSessionId, fn ($query) => $query->where('id', '!=', $exceptSessionId))
            ->delete();
    }

    private function summarizeUserAgent(string $userAgent): string
    {
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Browser',
        };

        $platform = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS') => 'macOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Device',
        };

        return $userAgent === '' ? 'Unknown device' : Str::limit("{$browser} on {$platform}", 80);
    }
}
