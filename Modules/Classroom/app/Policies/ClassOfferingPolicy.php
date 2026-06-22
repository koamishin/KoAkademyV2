<?php

declare(strict_types=1);

namespace Modules\Classroom\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Classroom\Models\ClassOffering;

class ClassOfferingPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ClassOffering');
    }

    public function view(AuthUser $authUser, ClassOffering $classOffering): bool
    {
        return $authUser->can('View:ClassOffering');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ClassOffering');
    }

    public function update(AuthUser $authUser, ClassOffering $classOffering): bool
    {
        return $authUser->can('Update:ClassOffering');
    }

    public function delete(AuthUser $authUser, ClassOffering $classOffering): bool
    {
        return $authUser->can('Delete:ClassOffering');
    }

    public function restore(AuthUser $authUser, ClassOffering $classOffering): bool
    {
        return $authUser->can('Restore:ClassOffering');
    }

    public function forceDelete(AuthUser $authUser, ClassOffering $classOffering): bool
    {
        return $authUser->can('ForceDelete:ClassOffering');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ClassOffering');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ClassOffering');
    }

    public function replicate(AuthUser $authUser, ClassOffering $classOffering): bool
    {
        return $authUser->can('Replicate:ClassOffering');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ClassOffering');
    }
}
