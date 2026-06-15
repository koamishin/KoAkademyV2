<?php

use App\Enums\RoleEnums;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\Person;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Support\CurrentCampus;
use Inertia\Testing\AssertableInertia;
use Modules\Classroom\Models\ClassMember;
use Modules\Classroom\Models\ClassOffering;
use Spatie\Permission\Models\Role;

function createCampus(string $code): Campus
{
    $institution = Institution::query()->firstOrCreate(
        ['code' => 'KO'],
        ['name' => 'Ko Academy'],
    );

    return Campus::query()->create([
        'institution_id' => $institution->id,
        'name' => "{$code} Campus",
        'code' => $code,
    ]);
}

function createCampusRole(RoleEnums $role): Role
{
    return Role::query()->firstOrCreate([
        'campus_id' => null,
        'name' => $role->value,
        'guard_name' => 'web',
    ]);
}

test('administrators may enter assigned campuses but not unassigned campuses', function (): void {
    createCampusRole(RoleEnums::SCHOOL_ADMIN);
    $assignedCampus = createCampus('MAIN');
    $unassignedCampus = createCampus('NORTH');
    $user = User::factory()->create();

    $user->campusMemberships()->create([
        'campus_id' => $assignedCampus->id,
        'role' => RoleEnums::SCHOOL_ADMIN,
        'is_default' => true,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard', ['campus' => $assignedCampus]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('currentCampus.id', $assignedCampus->id)
            ->where('auth.campusRole', RoleEnums::SCHOOL_ADMIN->value));

    $this->actingAs($user)
        ->get(route('dashboard', ['campus' => $unassignedCampus]))
        ->assertForbidden();
});

test('students are locked to their active assigned campus', function (): void {
    createCampusRole(RoleEnums::STUDENT);
    $assignedCampus = createCampus('MAIN');
    $otherCampus = createCampus('NORTH');
    $user = User::factory()->create();

    $user->campusMemberships()->create([
        'campus_id' => $assignedCampus->id,
        'role' => RoleEnums::STUDENT,
        'is_default' => true,
    ]);
    $user->campusMemberships()->create([
        'campus_id' => $otherCampus->id,
        'role' => RoleEnums::STUDENT,
        'active' => false,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard', ['campus' => $assignedCampus]))
        ->assertSuccessful();

    $this->actingAs($user)
        ->get(route('dashboard', ['campus' => $otherCampus]))
        ->assertForbidden();
});

test('classroom records cannot be opened through another campus URL', function (): void {
    createCampusRole(RoleEnums::STUDENT);
    $assignedCampus = createCampus('MAIN');
    $otherCampus = createCampus('NORTH');
    $user = User::factory()->create();
    $person = Person::query()->create([
        'user_id' => $user->id,
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
    ]);

    $user->campusMemberships()->create([
        'campus_id' => $assignedCampus->id,
        'role' => RoleEnums::STUDENT,
        'is_default' => true,
    ]);

    $academicYear = AcademicYear::query()->create([
        'institution_id' => $assignedCampus->institution_id,
        'name' => '2026-2027',
        'starts_on' => '2026-06-01',
        'ends_on' => '2027-03-31',
    ]);
    $term = Term::query()->create([
        'academic_year_id' => $academicYear->id,
        'name' => 'First Term',
        'code' => 'T1',
        'sequence' => 1,
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-10-31',
    ]);
    $subject = Subject::query()->create([
        'institution_id' => $assignedCampus->institution_id,
        'name' => 'Mathematics',
        'code' => 'MATH',
    ]);

    $classOffering = ClassOffering::query()->create([
        'campus_id' => $otherCampus->id,
        'term_id' => $term->id,
        'subject_id' => $subject->id,
        'name' => 'Other Campus Class',
        'code' => 'OTHER-1',
    ]);
    ClassMember::query()->create([
        'class_offering_id' => $classOffering->id,
        'person_id' => $person->id,
    ]);

    $this->actingAs($user)
        ->get(route('classroom.show', [
            'campus' => $assignedCampus,
            'classOffering' => $classOffering,
        ]))
        ->assertNotFound();
});

test('campus context is cleared after every request', function (): void {
    createCampusRole(RoleEnums::STUDENT);
    $campus = createCampus('MAIN');
    $user = User::factory()->create();

    $user->campusMemberships()->create([
        'campus_id' => $campus->id,
        'role' => RoleEnums::STUDENT,
        'is_default' => true,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard', ['campus' => $campus]))
        ->assertSuccessful();

    expect(app(CurrentCampus::class)->get())->toBeNull();
});
