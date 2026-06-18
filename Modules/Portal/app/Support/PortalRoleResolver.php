<?php

declare(strict_types=1);

namespace Modules\Portal\Support;

use App\Enums\RoleEnums;
use App\Models\Campus;
use App\Models\User;
use Modules\Portal\Enums\PortalRole;

final class PortalRoleResolver
{
    public function resolve(User $user, ?Campus $campus = null): PortalRole
    {
        $role = $campus instanceof Campus
            ? $user->campusMemberships()
                ->where('campus_id', $campus->getKey())
                ->where('active', true)
                ->value('role')
            : $user->campusMemberships()
                ->where('active', true)
                ->orderByDesc('is_default')
                ->value('role');

        if ($role instanceof RoleEnums) {
            return $this->fromRoleEnum($role);
        }

        if (is_string($role)) {
            return $this->fromRoleEnum(RoleEnums::tryFrom($role));
        }

        return PortalRole::Unknown;
    }

    public function canAccessAdminPortal(User $user, ?Campus $campus = null): bool
    {
        return $this->resolve($user, $campus) === PortalRole::Admin;
    }

    private function fromRoleEnum(?RoleEnums $role): PortalRole
    {
        return match ($role) {
            RoleEnums::SUPER_ADMIN,
            RoleEnums::SCHOOL_ADMIN,
            RoleEnums::REGISTRAR,
            RoleEnums::ADMISSIONS_OFFICER,
            RoleEnums::ACADEMIC_COORDINATOR => PortalRole::Admin,
            RoleEnums::TEACHER => PortalRole::Faculty,
            RoleEnums::STUDENT => PortalRole::Student,
            RoleEnums::APPLICANT => PortalRole::Applicant,
            RoleEnums::GUARDIAN => PortalRole::Guardian,
            default => PortalRole::Unknown,
        };
    }
}
