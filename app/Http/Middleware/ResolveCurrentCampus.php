<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\RoleEnums;
use App\Models\Campus;
use App\Models\User;
use App\Support\CurrentCampus;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

final class ResolveCurrentCampus
{
    public function __construct(
        private readonly CurrentCampus $currentCampus,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $campus = $this->resolveCampus($request);
        $user = $request->user();

        abort_unless($campus instanceof Campus && $user instanceof User, 404);

        $membership = $user->campusMemberships()
            ->where('campus_id', $campus->getKey())
            ->where('active', true)
            ->first();

        abort_unless($membership, 403);

        $role = $membership->role->value;
        $isAdministrator = in_array($role, RoleEnums::administrativeValues(), true);

        if (! $isAdministrator) {
            $assignedCampusId = $user->assignedCampus()?->getKey();
            abort_unless($assignedCampusId === $campus->getKey(), 403);
        }

        $previousCampusId = $this->permissionRegistrar->getPermissionsTeamId();
        $this->permissionRegistrar->setPermissionsTeamId($campus->getKey());
        $user->unsetRelation('roles')->unsetRelation('permissions');
        $this->currentCampus->set($campus);
        $request->attributes->set('currentCampus', $campus);

        try {
            return $next($request);
        } finally {
            $this->currentCampus->forget();
            $this->permissionRegistrar->setPermissionsTeamId($previousCampusId);
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }
    }

    private function resolveCampus(Request $request): ?Campus
    {
        $routeCampus = $request->route('campus');

        if ($routeCampus instanceof Campus) {
            return $routeCampus;
        }

        if (is_string($routeCampus)) {
            return Campus::query()->where('slug', $routeCampus)->first();
        }

        $tenant = Filament::getTenant();

        return $tenant instanceof Campus ? $tenant : null;
    }
}
