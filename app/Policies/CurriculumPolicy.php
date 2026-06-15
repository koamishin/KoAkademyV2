<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Curriculum;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class CurriculumPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Subject');
    }

    public function view(AuthUser $authUser, Curriculum $curriculum): bool
    {
        return $authUser->can('View:Subject');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Subject');
    }

    public function update(AuthUser $authUser, Curriculum $curriculum): bool
    {
        return $authUser->can('Update:Subject');
    }

    public function delete(AuthUser $authUser, Curriculum $curriculum): bool
    {
        return $authUser->can('Delete:Subject');
    }

    public function restore(AuthUser $authUser, Curriculum $curriculum): bool
    {
        return $authUser->can('Restore:Subject');
    }

    public function forceDelete(AuthUser $authUser, Curriculum $curriculum): bool
    {
        return $authUser->can('ForceDelete:Subject');
    }
}
