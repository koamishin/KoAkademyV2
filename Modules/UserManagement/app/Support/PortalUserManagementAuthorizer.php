<?php

declare(strict_types=1);

namespace Modules\UserManagement\Support;

use App\Enums\RoleEnums;
use App\Models\Campus;
use App\Models\User;
use App\Settings\ApplicationFeaturesSettings;
use Illuminate\Database\Eloquent\Builder;

final class PortalUserManagementAuthorizer
{
    /**
     * @return list<string>
     */
    public function viewerRoleValues(): array
    {
        return RoleEnums::administrativeValues();
    }

    /**
     * @return list<string>
     */
    public function operationalRoleValues(): array
    {
        return [
            RoleEnums::STUDENT->value,
            RoleEnums::TEACHER->value,
            RoleEnums::APPLICANT->value,
            RoleEnums::GUARDIAN->value,
        ];
    }

    public function canView(User $actor, Campus $campus): bool
    {
        return $this->isGlobalManager($actor)
            || $this->hasCampusRole($actor, $campus, $this->viewerRoleValues());
    }

    public function canMutateAny(User $actor, Campus $campus): bool
    {
        return $this->isGlobalManager($actor)
            || $this->hasCampusRole($actor, $campus, [RoleEnums::SCHOOL_ADMIN->value]);
    }

    public function canManage(User $actor, Campus $campus, User $target): bool
    {
        if ($this->isGlobalManager($actor)) {
            return true;
        }

        if (! $this->hasCampusRole($actor, $campus, [RoleEnums::SCHOOL_ADMIN->value])) {
            return false;
        }

        return $target->campusMemberships()
            ->whereBelongsTo($campus)
            ->where('active', true)
            ->whereIn('role', $this->operationalRoleValues())
            ->exists();
    }

    public function canImpersonate(User $actor, Campus $campus, User $target): bool
    {
        return app(ApplicationFeaturesSettings::class)->user_impersonation_enabled
            && $actor->isNot($target)
            && $this->canManage($actor, $campus, $target)
            && $actor->canImpersonate()
            && $target->canBeImpersonated();
    }

    /**
     * @return Builder<User>
     */
    public function scopeVisibleUsers(User $actor, Campus $campus): Builder
    {
        $query = User::query();

        if ($this->isGlobalManager($actor)) {
            return $query;
        }

        return $query->whereHas(
            'campusMemberships',
            fn (Builder $query): Builder => $query->whereBelongsTo($campus),
        );
    }

    /**
     * @return list<int>
     */
    public function manageableCampusIds(User $actor, Campus $campus): array
    {
        if ($this->isGlobalManager($actor)) {
            return Campus::query()->pluck('id')->map(fn ($id): int => (int) $id)->all();
        }

        return $this->hasCampusRole($actor, $campus, [RoleEnums::SCHOOL_ADMIN->value])
            ? [(int) $campus->getKey()]
            : [];
    }

    /**
     * @return list<string>
     */
    public function manageableRoleValues(User $actor, Campus $campus): array
    {
        return $this->isGlobalManager($actor)
            ? RoleEnums::values()
            : $this->operationalRoleValues();
    }

    public function isGlobalManager(User $actor): bool
    {
        return $actor->campusMemberships()
            ->where('active', true)
            ->where('role', RoleEnums::SUPER_ADMIN->value)
            ->exists();
    }

    /**
     * @param  list<string>  $roles
     */
    private function hasCampusRole(User $actor, Campus $campus, array $roles): bool
    {
        return $actor->campusMemberships()
            ->whereBelongsTo($campus)
            ->where('active', true)
            ->whereIn('role', $roles)
            ->exists();
    }
}
