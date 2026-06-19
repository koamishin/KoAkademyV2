<?php

declare(strict_types=1);

namespace Modules\Enrollment\Support;

use App\Models\Campus;
use App\Models\Person;
use App\Models\User;
use Modules\Portal\Enums\PortalRole;
use Modules\Portal\Support\PortalRoleResolver;

final class AdminStudentAuthorizer
{
    public function __construct(private readonly PortalRoleResolver $portalRoleResolver) {}

    public function canManage(User $user, Campus $campus): bool
    {
        return $this->portalRoleResolver->resolve($user, $campus) === PortalRole::Admin;
    }

    public function studentBelongsToCampus(Person $student, Campus $campus): bool
    {
        return $student->roles()
            ->where('campus_id', $campus->getKey())
            ->where('role', 'student')
            ->where('active', true)
            ->exists()
            || $student->enrollments()
                ->where('campus_id', $campus->getKey())
                ->exists();
    }

    public function abortUnlessCanManage(User $user, Campus $campus): void
    {
        abort_unless($this->canManage($user, $campus), 403);
    }

    public function abortUnlessStudentBelongsToCampus(Person $student, Campus $campus): void
    {
        abort_unless($this->studentBelongsToCampus($student, $campus), 404);
    }
}
