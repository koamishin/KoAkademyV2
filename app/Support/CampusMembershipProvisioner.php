<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\PersonRole;
use App\Enums\RoleEnums;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Admissions\Models\Application;

final class CampusMembershipProvisioner
{
    public function provision(User $user): ?Campus
    {
        if ($membership = $user->campusMemberships()->where('active', true)->orderByDesc('is_default')->first()) {
            $this->ensureCampusRole($membership->campus()->firstOrFail(), $membership->role);

            return $membership->campus;
        }

        return DB::transaction(function () use ($user): ?Campus {
            $role = $this->legacyRole($user);

            if (! $role instanceof RoleEnums) {
                return null;
            }

            if (Campus::query()->doesntExist() && in_array($role->value, RoleEnums::administrativeValues(), true)) {
                $this->createInitialCampus();
            }

            $this->provisionFromAuthoritativeRecords($user, $role);

            if ($campus = $user->fresh()->assignedCampus()) {
                return $campus;
            }

            $campuses = $role === RoleEnums::SUPER_ADMIN
                ? Campus::query()->orderBy('name')->get()
                : Campus::query()->orderBy('name')->limit(2)->get();

            if ($role !== RoleEnums::SUPER_ADMIN && $campuses->count() !== 1) {
                return null;
            }

            $this->createMemberships($user, $role, $campuses);

            return $user->fresh()->assignedCampus();
        });
    }

    private function createInitialCampus(): Campus
    {
        $institution = Institution::query()->firstOrCreate(
            ['code' => 'KO'],
            ['name' => 'Ko Academy'],
        );

        return Campus::query()->firstOrCreate(
            ['institution_id' => $institution->getKey(), 'code' => 'MAIN'],
            ['name' => 'Main Campus'],
        );
    }

    private function provisionFromAuthoritativeRecords(User $user, RoleEnums $roleEnums): void
    {
        $person = $user->person;

        if (! $person) {
            return;
        }

        $campusIds = $person->roles()
            ->where('active', true)
            ->whereNotNull('campus_id')
            ->when(
                ! in_array($roleEnums->value, RoleEnums::administrativeValues(), true),
                fn ($query) => $query->latest('id')->limit(1),
            )
            ->pluck('campus_id');

        if ($roleEnums === RoleEnums::APPLICANT) {
            $campusIds->push(
                Application::query()
                    ->where('person_id', $person->getKey())
                    ->whereNotNull('campus_id')
                    ->latest('id')
                    ->value('campus_id'),
            );
        }

        if ($roleEnums === RoleEnums::GUARDIAN) {
            $campusIds = $campusIds->merge(
                $person->students()
                    ->wherePivot('has_portal_access', true)
                    ->whereHas('roles', fn ($query) => $query
                        ->where('active', true)
                        ->where('role', PersonRole::Student))
                    ->with('roles')
                    ->get()
                    ->flatMap(fn ($student) => $student->roles
                        ->where('active', true)
                        ->where('role', PersonRole::Student)
                        ->pluck('campus_id')),
            );
        }

        $campuses = Campus::query()
            ->whereKey($campusIds->filter()->unique())
            ->orderBy('name')
            ->get();

        $this->createMemberships($user, $roleEnums, $campuses);
    }

    /**
     * @param  Collection<int, Campus>  $campuses
     */
    private function createMemberships(User $user, RoleEnums $roleEnums, Collection $campuses): void
    {
        foreach ($campuses as $index => $campus) {
            $this->ensureCampusRole($campus, $roleEnums);

            $user->campusMemberships()->updateOrCreate(
                ['campus_id' => $campus->getKey()],
                [
                    'role' => $roleEnums,
                    'active' => true,
                    'is_default' => $index === 0,
                ],
            );
        }
    }

    private function ensureCampusRole(Campus $campus, RoleEnums $roleEnums): Role
    {
        $campusRole = Role::query()->firstOrCreate([
            'campus_id' => $campus->getKey(),
            'name' => $roleEnums->value,
            'guard_name' => 'web',
        ]);

        if ($campusRole->permissions()->doesntExist()) {
            $globalRole = Role::query()
                ->whereNull('campus_id')
                ->where('name', $roleEnums->value)
                ->where('guard_name', 'web')
                ->first();

            if ($globalRole) {
                $campusRole->syncPermissions($globalRole->permissions);
            }
        }

        return $campusRole;
    }

    private function legacyRole(User $user): ?RoleEnums
    {
        $roleNames = $user->roles()->pluck('name');

        foreach ([
            ...RoleEnums::administrativeValues(),
            RoleEnums::TEACHER->value,
            RoleEnums::APPLICANT->value,
            RoleEnums::STUDENT->value,
            RoleEnums::GUARDIAN->value,
        ] as $roleName) {
            if ($roleNames->contains($roleName)) {
                return RoleEnums::from($roleName);
            }
        }

        return null;
    }
}
