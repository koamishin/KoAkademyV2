<?php

namespace Database\Seeders;

use App\Enums\RoleEnums;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    protected array $permissionMap = [
        'viewAny' => 'ViewAny',
        'view' => 'View',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'restore' => 'Restore',
        'forceDelete' => 'ForceDelete',
        'forceDeleteAny' => 'ForceDeleteAny',
        'restoreAny' => 'RestoreAny',
        'replicate' => 'Replicate',
        'reorder' => 'Reorder',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = $this->discoverPermissionsFromPolicies();
        $this->createPermissions($permissions);

        $roles = $this->createRoles();
        $this->assignPermissionsToRoles($roles, $permissions);
        $this->createDefaultUsers($roles);
    }

    protected function discoverPermissionsFromPolicies(): array
    {
        $policyPaths = [
            app_path('Policies'),
            ...collect(File::directories(base_path('Modules')))
                ->map(fn (string $module): string => $module.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Policies')
                ->filter(fn (string $path): bool => File::isDirectory($path))
                ->all(),
        ];
        $permissions = [];

        $policyFiles = collect($policyPaths)->flatMap(fn (string $path): array => File::files($path));

        foreach ($policyFiles as $file) {
            $policyName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $policyName = preg_replace('/Policy$/', '', $policyName);

            if (empty($policyName)) {
                continue;
            }

            $content = $file->getContents();

            foreach ($this->permissionMap as $method => $permissionSuffix) {
                if (preg_match('/function\s+'.$method.'\s*\(/', $content)) {
                    $permissions[] = "{$permissionSuffix}:{$policyName}";
                }
            }
        }

        return $permissions;
    }

    protected function createPermissions(array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    protected function createRoles(): array
    {
        $roles = [];

        foreach (RoleEnums::cases() as $enumCase) {
            $role = Role::firstOrCreate([
                'name' => $enumCase->value,
                'guard_name' => 'web',
            ]);
            $roles[$enumCase->value] = $role;
        }

        return $roles;
    }

    protected function assignPermissionsToRoles(array $roles, array $permissions): void
    {
        $superAdminPermissions = Permission::all()->pluck('name')->toArray();
        $roles[RoleEnums::SUPER_ADMIN->value]->syncPermissions($superAdminPermissions);

        foreach ([
            RoleEnums::SCHOOL_ADMIN,
            RoleEnums::REGISTRAR,
            RoleEnums::ADMISSIONS_OFFICER,
            RoleEnums::ACADEMIC_COORDINATOR,
        ] as $role) {
            $roles[$role->value]->syncPermissions(array_filter(
                $permissions,
                fn (string $permission): bool => ! str_contains($permission, 'Role:'),
            ));
        }

        foreach ([RoleEnums::TEACHER, RoleEnums::APPLICANT, RoleEnums::STUDENT, RoleEnums::GUARDIAN] as $role) {
            $roles[$role->value]->syncPermissions([]);
        }
    }

    protected function createDefaultUsers(array $roles): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole($roles[RoleEnums::SUPER_ADMIN->value]);

        $user = User::firstOrCreate(
            ['email' => 'user@user.com'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $user->assignRole($roles[RoleEnums::STUDENT->value]);
    }
}
