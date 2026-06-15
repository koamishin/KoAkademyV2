<?php

use App\Enums\RoleEnums;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\EducationLevel;
use App\Models\Institution;
use App\Models\Person;
use App\Models\Program;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Support\CurrentCampus;
use Inertia\Testing\AssertableInertia;
use Modules\Classroom\Models\ClassMember;
use Modules\Classroom\Models\ClassOffering;
use Modules\Enrollment\Enums\EnrollmentClassification;
use Modules\Enrollment\Models\Enrollment;
use Modules\Enrollment\Models\EnrollmentPeriod;
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

function createCampusRole(RoleEnums $roleEnums): Role
{
    return Role::query()->firstOrCreate([
        'campus_id' => null,
        'name' => $roleEnums->value,
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
        ->get(route('campus.dashboard', ['campus' => $assignedCampus]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $assertableInertia): \Inertia\Testing\AssertableInertia => $assertableInertia
            ->where('currentCampus.id', $assignedCampus->id)
            ->where('auth.campusRole', RoleEnums::SCHOOL_ADMIN->value));

    $this->actingAs($user)
        ->get(route('campus.dashboard', ['campus' => $unassignedCampus]))
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
        ->get(route('campus.dashboard', ['campus' => $assignedCampus]))
        ->assertSuccessful();

    $this->actingAs($user)
        ->get(route('campus.dashboard', ['campus' => $otherCampus]))
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
        ->get(route('campus.dashboard', ['campus' => $campus]))
        ->assertSuccessful();

    expect(app(CurrentCampus::class)->get())->toBeNull();
});

test('student academic history contains only the assigned campus', function (): void {
    createCampusRole(RoleEnums::STUDENT);
    $formerCampus = createCampus('FORMER');
    $assignedCampus = createCampus('CURRENT');
    $user = User::factory()->create();
    $person = Person::query()->create([
        'user_id' => $user->id,
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
    ]);

    $user->campusMemberships()->create([
        'campus_id' => $formerCampus->id,
        'role' => RoleEnums::STUDENT,
        'active' => false,
    ]);
    $user->campusMemberships()->create([
        'campus_id' => $assignedCampus->id,
        'role' => RoleEnums::STUDENT,
        'active' => true,
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
    $educationLevel = EducationLevel::query()->create([
        'institution_id' => $assignedCampus->institution_id,
        'name' => 'Senior High School',
        'code' => 'SHS',
        'category' => 'basic',
    ]);

    foreach ([$formerCampus, $assignedCampus] as $campus) {
        $program = Program::query()->create([
            'campus_id' => $campus->id,
            'education_level_id' => $educationLevel->id,
            'name' => "{$campus->code} Program",
            'code' => "{$campus->code}-PROGRAM",
        ]);
        $curriculum = Curriculum::query()->create([
            'program_id' => $program->id,
            'name' => "{$campus->code} Curriculum",
            'code' => "{$campus->code}-CURRICULUM",
            'effective_year' => 2026,
        ]);
        $period = EnrollmentPeriod::query()->create([
            'campus_id' => $campus->id,
            'term_id' => $term->id,
            'name' => "{$campus->code} Enrollment",
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addDay(),
        ]);

        Enrollment::query()->create([
            'student_id' => $person->id,
            'campus_id' => $campus->id,
            'enrollment_period_id' => $period->id,
            'curriculum_id' => $curriculum->id,
            'classification' => EnrollmentClassification::Continuing,
        ]);
    }

    $this->actingAs($user)
        ->get(route('academic-history.show', ['campus' => $assignedCampus]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $assertableInertia): \Inertia\Testing\AssertableInertia => $assertableInertia
            ->has('enrollments', 1)
            ->where('enrollments.0.campus_id', $assignedCampus->id));
});
