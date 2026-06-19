<?php

use App\Enums\PersonRole;
use App\Enums\RoleEnums;
use App\Models\AcademicSetting;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\CurriculumItem;
use App\Models\EducationLevel;
use App\Models\Institution;
use App\Models\Person;
use App\Models\PersonRoleAssignment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Modules\Classroom\Models\ClassOffering;
use Modules\Enrollment\Enums\EnrollmentStatus;
use Modules\Enrollment\Models\Enrollment;
use Modules\Enrollment\Models\EnrollmentPeriod;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

test('admin can list and view campus scoped student records', function (): void {
    $context = adminStudentContext();
    $student = createCampusStudent($context['campus'], 'Ana', 'Reyes');

    actingAs($context['admin'])
        ->get(route('admin.students.index', ['campus' => $context['campus']]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrollment/StudentRecords')
            ->has('students.data', 1)
            ->where('students.data.0.fullName', $student->full_name)
        );

    actingAs($context['admin'])
        ->get(route('admin.students.show', ['campus' => $context['campus'], 'student' => $student]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrollment/StudentProfile')
            ->where('student.fullName', $student->full_name)
            ->has('enrollments')
            ->has('options.periods')
        );
});

test('non admin users cannot access student records', function (): void {
    $context = adminStudentContext();
    $studentUser = User::factory()->create();
    $studentUser->campusMemberships()->create([
        'campus_id' => $context['campus']->id,
        'role' => RoleEnums::STUDENT,
        'active' => true,
        'is_default' => true,
    ]);

    actingAs($studentUser)
        ->get(route('admin.students.index', ['campus' => $context['campus']]))
        ->assertForbidden();
});

test('admin can create and update a student profile with guardians', function (): void {
    $context = adminStudentContext();

    actingAs($context['admin'])
        ->post(route('admin.students.store', ['campus' => $context['campus']]), [
            'first_name' => 'Mika',
            'last_name' => 'Santos',
            'birth_date' => '2010-06-12',
            'sex' => 'female',
            'email' => 'mika@example.test',
            'phone' => '09170000000',
            'address' => 'Main Street',
            'status' => 'active',
            'student_number' => 'STU-LOCAL-1',
            'metadata' => [
                'learner_reference_number' => 'LRN-100',
                'previous_school' => 'Old School',
                'emergency_contact' => '09171111111',
            ],
            'guardians' => [
                [
                    'first_name' => 'Maria',
                    'last_name' => 'Santos',
                    'relationship' => 'mother',
                    'phone' => '09172222222',
                    'is_primary' => true,
                    'has_portal_access' => true,
                ],
            ],
        ])
        ->assertRedirect();

    $student = Person::query()->where('email', 'mika@example.test')->firstOrFail();

    assertDatabaseHas('person_roles', [
        'person_id' => $student->id,
        'campus_id' => $context['campus']->id,
        'role' => PersonRole::Student->value,
        'reference_number' => 'STU-LOCAL-1',
    ]);
    expect($student->guardians()->count())->toBe(1);

    actingAs($context['admin'])
        ->patch(route('admin.students.update', ['campus' => $context['campus'], 'student' => $student]), [
            'first_name' => 'Mikaela',
            'last_name' => 'Santos',
            'birth_date' => '2010-06-12',
            'sex' => 'female',
            'email' => 'mikaela@example.test',
            'phone' => '09170000001',
            'address' => 'Updated Street',
            'status' => 'active',
            'student_number' => 'STU-LOCAL-2',
            'metadata' => [
                'learner_reference_number' => 'LRN-101',
            ],
            'guardians' => [],
        ])
        ->assertRedirect();

    $student->refresh();

    expect($student->full_name)->toBe('Mikaela Santos')
        ->and($student->guardians()->count())->toBe(0);

    assertDatabaseHas('person_roles', [
        'person_id' => $student->id,
        'reference_number' => 'STU-LOCAL-2',
    ]);
});

test('admin can create enrollment with curriculum subjects and duplicate periods are rejected', function (): void {
    $context = adminStudentContext();
    $student = createCampusStudent($context['campus'], 'Ana', 'Reyes');

    actingAs($context['admin'])
        ->post(route('admin.students.enrollments.store', ['campus' => $context['campus'], 'student' => $student]), [
            'enrollment_period_id' => $context['period']->id,
            'curriculum_id' => $context['curriculum']->id,
            'classification' => 'new',
            'section_id' => $context['section']->id,
            'year_level' => 1,
            'selected_elective_item_ids' => [],
        ])
        ->assertRedirect();

    $enrollment = Enrollment::query()->where('student_id', $student->id)->firstOrFail();

    expect($enrollment->subjects()->count())->toBe(1)
        ->and($enrollment->status)->toBe(EnrollmentStatus::Pending);

    actingAs($context['admin'])
        ->post(route('admin.students.enrollments.store', ['campus' => $context['campus'], 'student' => $student]), [
            'enrollment_period_id' => $context['period']->id,
            'curriculum_id' => $context['curriculum']->id,
            'classification' => 'new',
        ])
        ->assertSessionHasErrors('student');
});

test('approval syncs student number and class roster while capacity waitlists overflow', function (): void {
    $context = adminStudentContext();
    AcademicSetting::query()->create([
        'key' => 'student_number_format',
        'value' => ['STU-{year}-{sequence:4}'],
    ]);
    $firstStudent = createCampusStudent($context['campus'], 'Ana', 'Reyes');
    $secondStudent = createCampusStudent($context['campus'], 'Ben', 'Cruz');

    $firstEnrollment = createStudentEnrollmentThroughPortal($context, $firstStudent);
    $secondEnrollment = createStudentEnrollmentThroughPortal($context, $secondStudent);

    actingAs($context['admin'])
        ->patch(route('admin.students.enrollment-subjects.update', [
            'campus' => $context['campus'],
            'student' => $firstStudent,
            'enrollment' => $firstEnrollment,
            'enrollmentSubject' => $firstEnrollment->subjects()->firstOrFail(),
        ]), [
            'class_offering_id' => $context['classOffering']->id,
            'status' => 'enrolled',
        ])
        ->assertRedirect();

    actingAs($context['admin'])
        ->post(route('admin.students.enrollments.approve', [
            'campus' => $context['campus'],
            'student' => $firstStudent,
            'enrollment' => $firstEnrollment,
        ]))
        ->assertRedirect();

    actingAs($context['admin'])
        ->post(route('admin.students.enrollments.approve', [
            'campus' => $context['campus'],
            'student' => $secondStudent,
            'enrollment' => $secondEnrollment,
        ]))
        ->assertRedirect();

    $firstEnrollment->refresh();
    $secondEnrollment->refresh();

    expect($firstEnrollment->status)->toBe(EnrollmentStatus::Approved)
        ->and($firstEnrollment->student_number)->toBe('STU-'.now()->format('Y').'-0001')
        ->and($secondEnrollment->status)->toBe(EnrollmentStatus::Waitlisted);

    assertDatabaseHas('person_roles', [
        'person_id' => $firstStudent->id,
        'campus_id' => $context['campus']->id,
        'reference_number' => $firstEnrollment->student_number,
    ]);
    assertDatabaseHas('class_members', [
        'class_offering_id' => $context['classOffering']->id,
        'person_id' => $firstStudent->id,
        'role' => 'student',
        'status' => 'active',
    ]);
});

/**
 * @return array{
 *     institution: Institution,
 *     campus: Campus,
 *     academicYear: AcademicYear,
 *     term: Term,
 *     program: Program,
 *     curriculum: Curriculum,
 *     curriculumItem: CurriculumItem,
 *     section: Section,
 *     period: EnrollmentPeriod,
 *     classOffering: ClassOffering,
 *     admin: User
 * }
 */
function adminStudentContext(): array
{
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => fake()->unique()->lexify('KO???')]);
    $campus = Campus::query()->create(['institution_id' => $institution->id, 'name' => 'Main Campus', 'code' => fake()->unique()->lexify('MAIN???')]);
    $academicYear = AcademicYear::query()->create([
        'institution_id' => $institution->id,
        'name' => '2026-2027',
        'starts_on' => '2026-06-01',
        'ends_on' => '2027-03-31',
        'is_current' => true,
    ]);
    $term = Term::query()->create([
        'academic_year_id' => $academicYear->id,
        'name' => 'First Term',
        'code' => 'T1',
        'sequence' => 1,
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-10-31',
        'status' => 'active',
    ]);
    $educationLevel = EducationLevel::query()->create([
        'institution_id' => $institution->id,
        'name' => 'College',
        'code' => fake()->unique()->lexify('COL???'),
        'category' => 'college',
    ]);
    $program = Program::query()->create([
        'campus_id' => $campus->id,
        'education_level_id' => $educationLevel->id,
        'name' => 'Information Technology',
        'code' => fake()->unique()->lexify('BSIT???'),
    ]);
    $curriculum = Curriculum::query()->create([
        'program_id' => $program->id,
        'name' => 'BSIT 2026',
        'code' => fake()->unique()->lexify('CURR???'),
        'effective_year' => 2026,
        'tuition_per_unit' => 375,
        'laboratory_fee_per_subject' => 2000,
    ]);
    $subject = Subject::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Programming Fundamentals',
        'code' => fake()->unique()->lexify('PROG???'),
    ]);
    $curriculumItem = CurriculumItem::query()->create([
        'curriculum_id' => $curriculum->id,
        'subject_id' => $subject->id,
        'year_level' => 1,
        'term_sequence' => 1,
        'credit_units' => 3,
        'contact_hours' => 3,
    ]);
    $section = Section::query()->create([
        'campus_id' => $campus->id,
        'program_id' => $program->id,
        'term_id' => $term->id,
        'name' => 'BSIT 1A',
        'code' => fake()->unique()->lexify('BSIT1A???'),
        'year_level' => 1,
        'capacity' => 1,
    ]);
    $period = EnrollmentPeriod::query()->create([
        'campus_id' => $campus->id,
        'term_id' => $term->id,
        'name' => 'Regular Enrollment',
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addWeek(),
    ]);
    $classOffering = ClassOffering::query()->create([
        'campus_id' => $campus->id,
        'term_id' => $term->id,
        'subject_id' => $subject->id,
        'section_id' => $section->id,
        'name' => 'Programming 1A',
        'code' => fake()->unique()->lexify('PROG1A???'),
        'status' => 'active',
    ]);
    $admin = User::factory()->create();
    $admin->campusMemberships()->create([
        'campus_id' => $campus->id,
        'role' => RoleEnums::REGISTRAR,
        'active' => true,
        'is_default' => true,
    ]);

    return compact(
        'institution',
        'campus',
        'academicYear',
        'term',
        'program',
        'curriculum',
        'curriculumItem',
        'section',
        'period',
        'classOffering',
        'admin',
    );
}

function createCampusStudent(Campus $campus, string $firstName, string $lastName): Person
{
    $student = Person::query()->create([
        'first_name' => $firstName,
        'last_name' => $lastName,
    ]);

    PersonRoleAssignment::query()->create([
        'person_id' => $student->id,
        'campus_id' => $campus->id,
        'role' => PersonRole::Student,
        'active' => true,
    ]);

    return $student;
}

/**
 * @param  array<string, mixed>  $context
 */
function createStudentEnrollmentThroughPortal(array $context, Person $student): Enrollment
{
    actingAs($context['admin'])
        ->post(route('admin.students.enrollments.store', ['campus' => $context['campus'], 'student' => $student]), [
            'enrollment_period_id' => $context['period']->id,
            'curriculum_id' => $context['curriculum']->id,
            'classification' => 'new',
            'section_id' => $context['section']->id,
            'year_level' => 1,
            'selected_elective_item_ids' => [],
        ])
        ->assertRedirect();

    return Enrollment::query()
        ->where('student_id', $student->id)
        ->where('enrollment_period_id', $context['period']->id)
        ->with('subjects')
        ->firstOrFail();
}
