<?php

use App\Enums\RoleEnums;
use App\Filament\Resources\Curricula\CurriculumResource;
use App\Models\AcademicModuleSetting;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\Person;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Modules\Classroom\Models\ClassMember;
use Modules\Classroom\Models\ClassOffering;

function portalCampus(): Campus
{
    $institution = Institution::query()->create([
        'name' => 'Ko Academy',
        'code' => 'KO',
    ]);

    return Campus::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Main Campus',
        'code' => 'MAIN',
    ]);
}

function portalUser(Campus $campus, RoleEnums $role, ?Person $person = null): User
{
    $user = User::factory()->create();

    $user->campusMemberships()->create([
        'campus_id' => $campus->id,
        'role' => $role,
        'active' => true,
        'is_default' => true,
    ]);

    if ($person instanceof Person) {
        $person->update(['user_id' => $user->id]);
    }

    return $user->refresh();
}

function portalPerson(array $attributes = []): Person
{
    return Person::query()->create([
        'first_name' => $attributes['first_name'] ?? 'Ada',
        'last_name' => $attributes['last_name'] ?? 'Lovelace',
        'email' => $attributes['email'] ?? fake()->unique()->safeEmail(),
    ]);
}

function portalTerm(Campus $campus): Term
{
    $academicYear = AcademicYear::query()->create([
        'institution_id' => $campus->institution_id,
        'name' => '2026-2027',
        'starts_on' => now()->startOfYear(),
        'ends_on' => now()->endOfYear(),
        'status' => 'active',
        'is_current' => true,
    ]);

    return Term::query()->create([
        'academic_year_id' => $academicYear->id,
        'name' => 'First Term',
        'code' => 'T1',
        'sequence' => 1,
        'starts_on' => now()->startOfMonth(),
        'ends_on' => now()->endOfMonth(),
        'status' => 'active',
    ]);
}

function portalSubject(Campus $campus, string $code): Subject
{
    return Subject::query()->create([
        'institution_id' => $campus->institution_id,
        'name' => "Subject {$code}",
        'code' => $code,
    ]);
}

function portalClass(Campus $campus, Term $term, Subject $subject, ?Person $teacher = null): ClassOffering
{
    return ClassOffering::query()->create([
        'campus_id' => $campus->id,
        'term_id' => $term->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher?->id,
        'name' => $subject->name,
        'code' => $subject->code,
        'status' => 'active',
    ]);
}

function portalNavigationTitles(TestResponse $response): array
{
    return collect($response->inertiaProps('portal.navigation'))
        ->flatMap(fn (array $group): array => collect($group['items'])->pluck('title')->all())
        ->values()
        ->all();
}

test('login landing redirects each role to the campus portal dashboard', function (RoleEnums $role): void {
    $campus = portalCampus();
    $user = portalUser($campus, $role);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('campus.dashboard', ['campus' => $campus]));
})->with([
    RoleEnums::STUDENT,
    RoleEnums::TEACHER,
    RoleEnums::SCHOOL_ADMIN,
]);

test('student users receive the student dashboard and student navigation', function (): void {
    $campus = portalCampus();
    $user = portalUser($campus, RoleEnums::STUDENT, portalPerson());

    $response = $this->actingAs($user)
        ->get(route('campus.dashboard', ['campus' => $campus]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/StudentDashboard')
            ->where('portal.role', 'student')
            ->where('portal.canAccessAdminPortal', false)
        );

    expect(portalNavigationTitles($response))
        ->toContain('Dashboard', 'My Classes', 'Academic History', 'Applications')
        ->not->toContain('Applications Queue', 'Enrollment Queue', 'Classes');
});

test('teacher users receive the faculty dashboard and faculty navigation', function (): void {
    $campus = portalCampus();
    $user = portalUser($campus, RoleEnums::TEACHER, portalPerson(['first_name' => 'Grace']));

    $response = $this->actingAs($user)
        ->get(route('campus.dashboard', ['campus' => $campus]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/FacultyDashboard')
            ->where('portal.role', 'faculty')
            ->where('portal.canAccessAdminPortal', false)
        );

    expect(portalNavigationTitles($response))
        ->toContain('Dashboard', 'My Classes')
        ->not->toContain('Academic History', 'Applications Queue');
});

test('admin users receive the operations dashboard and admin navigation', function (): void {
    $campus = portalCampus();
    $user = portalUser($campus, RoleEnums::SCHOOL_ADMIN);

    $response = $this->actingAs($user)
        ->get(route('campus.dashboard', ['campus' => $campus]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/AdminDashboard')
            ->where('portal.role', 'admin')
            ->where('portal.canAccessAdminPortal', true)
        );

    expect(portalNavigationTitles($response))
        ->toContain('Dashboard', 'Applications Queue', 'Enrollment Queue', 'Classes', 'Curriculum Manager', 'Advanced Admin')
        ->not->toContain('Academic History');
});

test('admin curriculum manager portal route redirects to Filament manager', function (): void {
    $campus = portalCampus();
    $user = portalUser($campus, RoleEnums::SCHOOL_ADMIN);

    $this->actingAs($user)
        ->get(route('admin.curricula.index', ['campus' => $campus]))
        ->assertRedirect(CurriculumResource::getUrl('index', ['tenant' => $campus]));
});

test('non admin users cannot access the curriculum manager portal route', function (): void {
    $campus = portalCampus();
    $user = portalUser($campus, RoleEnums::STUDENT);

    $this->actingAs($user)
        ->get(route('admin.curricula.index', ['campus' => $campus]))
        ->assertForbidden();
});

test('users cannot access a different campus portal', function (): void {
    $campus = portalCampus();
    $otherCampus = Campus::query()->create([
        'institution_id' => $campus->institution_id,
        'name' => 'North Campus',
        'code' => 'NORTH',
    ]);
    $user = portalUser($campus, RoleEnums::STUDENT);

    $this->actingAs($user)
        ->get(route('campus.dashboard', ['campus' => $otherCampus]))
        ->assertForbidden();
});

test('disabled modules are removed from portal navigation', function (): void {
    $campus = portalCampus();
    $user = portalUser($campus, RoleEnums::STUDENT);

    AcademicModuleSetting::query()->updateOrCreate(
        ['module' => 'classroom'],
        ['enabled' => false],
    );

    $response = $this->actingAs($user)
        ->get(route('campus.dashboard', ['campus' => $campus]))
        ->assertOk();

    expect(portalNavigationTitles($response))->not->toContain('My Classes');
});

test('classroom listings are scoped for students teachers and admins', function (): void {
    $campus = portalCampus();
    $term = portalTerm($campus);
    $teacherPerson = portalPerson(['first_name' => 'Teacher']);
    $studentPerson = portalPerson(['first_name' => 'Student']);
    $otherTeacher = portalPerson(['first_name' => 'Other']);

    $teacher = portalUser($campus, RoleEnums::TEACHER, $teacherPerson);
    $student = portalUser($campus, RoleEnums::STUDENT, $studentPerson);
    $admin = portalUser($campus, RoleEnums::SCHOOL_ADMIN);

    $teacherClass = portalClass($campus, $term, portalSubject($campus, 'ENG101'), $teacherPerson);
    $studentOnlyClass = portalClass($campus, $term, portalSubject($campus, 'MATH101'), $otherTeacher);
    portalClass($campus, $term, portalSubject($campus, 'SCI101'), $otherTeacher);

    ClassMember::query()->create([
        'class_offering_id' => $teacherClass->id,
        'person_id' => $studentPerson->id,
        'role' => 'student',
        'status' => 'active',
    ]);
    ClassMember::query()->create([
        'class_offering_id' => $studentOnlyClass->id,
        'person_id' => $studentPerson->id,
        'role' => 'student',
        'status' => 'active',
    ]);

    $this->actingAs($student)
        ->get(route('classroom.index', ['campus' => $campus]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('classroom/Index')
            ->has('classes', 2)
        );

    $this->actingAs($teacher)
        ->get(route('classroom.index', ['campus' => $campus]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('classroom/Index')
            ->has('classes', 1)
        );

    $this->actingAs($admin)
        ->get(route('admin.classes.index', ['campus' => $campus]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('classroom/AdminOperations')
            ->has('classes.data', 3)
        );
});
