<?php

use AlizHarb\ActivityLog\Resources\ActivityLogs\ActivityLogResource;
use App\Enums\RoleEnums;
use App\Filament\Resources\Campuses\CampusResource;
use App\Filament\Resources\Subjects\SubjectResource;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\Role as CampusRole;
use App\Models\User;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

function assignLegacyRole(User $user, RoleEnums $role): void
{
    $permissionRole = Role::query()->firstOrCreate([
        'campus_id' => null,
        'name' => $role->value,
        'guard_name' => 'web',
    ]);

    $user->assignRole($permissionRole);
}

test('the first administrator login bootstraps the initial campus', function (): void {
    $user = User::factory()->create();
    assignLegacyRole($user, RoleEnums::SUPER_ADMIN);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect();

    $campus = Campus::query()->sole();

    expect($campus->code)->toBe('MAIN')
        ->and($user->fresh()->assignedCampus()?->is($campus))->toBeTrue()
        ->and($user->fresh()->campusMemberships()->where('role', RoleEnums::SUPER_ADMIN)->exists())->toBeTrue();
});

test('legacy role accounts are assigned when there is one campus', function (RoleEnums $role): void {
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => 'KO']);
    $campus = Campus::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Main Campus',
        'code' => 'MAIN',
    ]);
    $user = User::factory()->create();
    assignLegacyRole($user, $role);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('campus.dashboard', ['campus' => $campus]));

    expect($user->fresh()->assignedCampus()?->is($campus))->toBeTrue()
        ->and($user->fresh()->campusMemberships()->value('role'))->toBe($role);
})->with([
    RoleEnums::SUPER_ADMIN,
    RoleEnums::SCHOOL_ADMIN,
    RoleEnums::REGISTRAR,
    RoleEnums::ADMISSIONS_OFFICER,
    RoleEnums::ACADEMIC_COORDINATOR,
    RoleEnums::TEACHER,
    RoleEnums::APPLICANT,
    RoleEnums::STUDENT,
    RoleEnums::GUARDIAN,
]);

test('accounts without an assignable campus see a pending page instead of a forbidden response', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('campus.assignment.pending'));

    $this->actingAs($user)
        ->get(route('campus.assignment.pending'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('CampusAssignmentRequired'));
});

test('shield roles are owned and scoped by campus', function (): void {
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => 'KO']);
    $campus = Campus::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Main Campus',
        'code' => 'MAIN',
    ]);
    $otherCampus = Campus::query()->create([
        'institution_id' => $institution->id,
        'name' => 'North Campus',
        'code' => 'NORTH',
    ]);

    $campusRole = CampusRole::query()->create([
        'campus_id' => $campus->id,
        'name' => RoleEnums::REGISTRAR->value,
        'guard_name' => 'web',
    ]);
    CampusRole::query()->create([
        'campus_id' => $otherCampus->id,
        'name' => RoleEnums::REGISTRAR->value,
        'guard_name' => 'web',
    ]);
    $user = User::factory()->create();
    assignLegacyRole($user, RoleEnums::SUPER_ADMIN);
    $user->campusMemberships()->create([
        'campus_id' => $campus->id,
        'role' => RoleEnums::SUPER_ADMIN,
        'is_default' => true,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($campus);

    expect($campusRole->campus->is($campus))->toBeTrue()
        ->and($campusRole->team->is($campus))->toBeTrue()
        ->and(RoleResource::scopeEloquentQueryToTenant(CampusRole::query(), $campus)
            ->pluck('campus_id')
            ->unique()
            ->all())->toBe([$campus->id]);
});

test('institution activity logs are not tenant scoped', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect(ActivityLogResource::isScopedToTenant())->toBeFalse();
});

test('super administrators may open the campus creation page when scoped permissions are missing', function (): void {
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => 'KO']);
    $campus = Campus::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Main Campus',
        'code' => 'MAIN',
    ]);
    $user = User::factory()->create();

    $user->campusMemberships()->create([
        'campus_id' => $campus->id,
        'role' => RoleEnums::SUPER_ADMIN,
        'is_default' => true,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($campus);

    $this->get(CampusResource::getUrl('create', tenant: $campus))
        ->assertSuccessful();
});

test('super administrators may open all resource pages when scoped permissions are missing', function (): void {
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => 'KO']);
    $campus = Campus::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Main Campus',
        'code' => 'MAIN',
    ]);
    $user = User::factory()->create();

    $user->campusMemberships()->create([
        'campus_id' => $campus->id,
        'role' => RoleEnums::SUPER_ADMIN,
        'is_default' => true,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($campus);

    $this->get(SubjectResource::getUrl('index', tenant: $campus))
        ->assertSuccessful();
});

test('administrators without subject permissions remain forbidden', function (): void {
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => 'KO']);
    $campus = Campus::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Main Campus',
        'code' => 'MAIN',
    ]);
    $user = User::factory()->create();

    $user->campusMemberships()->create([
        'campus_id' => $campus->id,
        'role' => RoleEnums::SCHOOL_ADMIN,
        'is_default' => true,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($campus);

    $this->get(SubjectResource::getUrl('index', tenant: $campus))
        ->assertForbidden();
});

test('repeated panel access does not duplicate the campus shield role assignment', function (): void {
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => 'KO']);
    $campus = Campus::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Main Campus',
        'code' => 'MAIN',
    ]);
    $user = User::factory()->create();

    $user->campusMemberships()->create([
        'campus_id' => $campus->id,
        'role' => RoleEnums::SUPER_ADMIN,
        'is_default' => true,
    ]);

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue()
        ->and($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue()
        ->and(DB::table(config('permission.table_names.model_has_roles'))
            ->where('campus_id', $campus->id)
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->count())->toBe(1);
});
