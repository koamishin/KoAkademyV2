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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Classroom\Models\ClassOffering;
use Modules\Enrollment\Enums\EnrollmentStatus;
use Modules\Enrollment\Models\Enrollment;
use Modules\Enrollment\Models\EnrollmentPeriod;
use Modules\Enrollment\Models\StudentDocument;
use Modules\Enrollment\Models\StudentProfile;
use Modules\Enrollment\Models\TransferCreditEvaluation;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

test('admin can list and view campus scoped student records', function (): void {
    $context = adminStudentContext();
    $student = createCampusStudent($context['campus'], 'Ana', 'Reyes');

    actingAs($context['admin'])
        ->get(route('admin.students.index', ['campus' => $context['campus']]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrollment/StudentRecords')
            ->has('students.data', 1)
            ->has('students.data.0.documentSummary')
            ->has('summary.documentGaps')
            ->has('summary.transferReviews')
            ->has('summary.archived')
            ->has('charts.profileStatuses')
            ->has('charts.enrollmentStatuses')
            ->has('charts.documentReadiness')
            ->where('students.data.0.fullName', $student->full_name)
        );

    actingAs($context['admin'])
        ->get(route('admin.students.show', ['campus' => $context['campus'], 'student' => $student]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrollment/StudentProfile')
            ->where('student.fullName', $student->full_name)
            ->has('student.documentSummary')
            ->has('documents')
            ->has('transferCredits')
            ->has('enrollments')
            ->has('options.periods')
        );
});

test('admin can open full page create and edit student screens', function (): void {
    $context = adminStudentContext();
    $student = createCampusStudent($context['campus'], 'Ana', 'Reyes');

    actingAs($context['admin'])
        ->get(route('admin.students.create', ['campus' => $context['campus']]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrollment/StudentCreate')
            ->where('options.academicStyle.level', 'college')
            ->has('options.statuses')
            ->has('options.documentTypes')
            ->has('options.programs')
            ->has('options.programs.0.educationLevel')
        );

    actingAs($context['admin'])
        ->get(route('admin.students.edit', ['campus' => $context['campus'], 'student' => $student]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrollment/StudentEdit')
            ->where('student.fullName', $student->full_name)
            ->where('form.first_name', 'Ana')
            ->has('documents')
            ->has('transferCredits')
            ->has('enrollments')
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

    $student = createCampusStudent($context['campus'], 'Ana', 'Reyes');

    actingAs($studentUser)
        ->get(route('admin.students.create', ['campus' => $context['campus']]))
        ->assertForbidden();

    actingAs($studentUser)
        ->get(route('admin.students.edit', ['campus' => $context['campus'], 'student' => $student]))
        ->assertForbidden();

    actingAs($studentUser)
        ->delete(route('admin.students.destroy', ['campus' => $context['campus'], 'student' => $student]))
        ->assertForbidden();

    actingAs($studentUser)
        ->post(route('admin.students.restore', ['campus' => $context['campus'], 'student' => $student]))
        ->assertForbidden();
});

test('admin can archive and restore student records', function (): void {
    $context = adminStudentContext();
    $student = createCampusStudent($context['campus'], 'Ana', 'Reyes');

    actingAs($context['admin'])
        ->delete(route('admin.students.destroy', ['campus' => $context['campus'], 'student' => $student]))
        ->assertRedirect(route('admin.students.index', ['campus' => $context['campus']]));

    assertSoftDeleted('people', ['id' => $student->id]);

    actingAs($context['admin'])
        ->get(route('admin.students.index', ['campus' => $context['campus']]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('students.data', 0)
            ->where('summary.archived', 1)
        );

    actingAs($context['admin'])
        ->get(route('admin.students.index', ['campus' => $context['campus'], 'view' => 'archived']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.fullName', $student->full_name)
            ->where('students.data.0.can.restore', true)
        );

    actingAs($context['admin'])
        ->post(route('admin.students.restore', ['campus' => $context['campus'], 'student' => $student->id]))
        ->assertRedirect(route('admin.students.show', ['campus' => $context['campus'], 'student' => $student->id]));

    expect(Person::query()->find($student->id))->not->toBeNull();
});

test('admin can create and update a student profile with guardians', function (): void {
    $context = adminStudentContext();
    Storage::fake('local');

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
            'profile' => [
                'psa_birth_certificate_number' => 'PSA-100',
                'learner_reference_number' => 'LRN-100',
                'nationality' => 'Filipino',
                'mother_tongue' => 'Cebuano',
                'is_4ps_beneficiary' => true,
                'four_ps_household_id' => '4PS-100',
                'annual_family_income_bracket' => '250001_400000',
                'household_gross_income' => 320000,
                'current_address' => [
                    'house' => '12 Main Street',
                    'barangay' => 'Centro',
                    'city' => 'Dumaguete',
                    'province' => 'Negros Oriental',
                    'country' => 'Philippines',
                    'zip_code' => '6200',
                ],
                'permanent_address' => [
                    'house' => '12 Main Street',
                    'barangay' => 'Centro',
                    'city' => 'Dumaguete',
                    'province' => 'Negros Oriental',
                    'country' => 'Philippines',
                    'zip_code' => '6200',
                ],
                'previous_school_name' => 'Old School',
                'previous_school_type' => 'private',
                'college_year_level' => 1,
                'reporting_flags' => ['intake_classification' => 'transferee'],
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
            'documents' => [
                [
                    'document_type' => 'student_photo',
                    'file' => UploadedFile::fake()->create('photo.jpg', 120, 'image/jpeg'),
                    'notes' => 'Registrar intake photo.',
                ],
                [
                    'document_type' => 'psa_birth_certificate',
                    'file' => UploadedFile::fake()->create('psa.pdf', 240, 'application/pdf'),
                ],
            ],
        ])
        ->assertRedirect();

    $student = Person::query()->where('email', 'mika@example.test')->firstOrFail();
    $profile = StudentProfile::query()->where('person_id', $student->id)->firstOrFail();
    $document = StudentDocument::query()->where('student_id', $student->id)->where('document_type', 'student_photo')->firstOrFail();

    assertDatabaseHas('person_roles', [
        'person_id' => $student->id,
        'campus_id' => $context['campus']->id,
        'role' => PersonRole::Student->value,
        'reference_number' => 'STU-LOCAL-1',
    ]);
    expect($student->guardians()->count())->toBe(1)
        ->and($profile->learner_reference_number)->toBe('LRN-100')
        ->and($profile->is_4ps_beneficiary)->toBeTrue()
        ->and($profile->annual_family_income_bracket)->toBe('250001_400000')
        ->and($document->status)->toBe('pending');

    Storage::disk('local')->assertExists($document->path);

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
            'profile' => [
                'learner_reference_number' => 'LRN-101',
                'annual_family_income_bracket' => '100000_250000',
            ],
            'guardians' => [],
        ])
        ->assertRedirect();

    $student->refresh();

    expect($student->full_name)->toBe('Mikaela Santos')
        ->and($student->guardians()->count())->toBe(0)
        ->and($student->studentProfile()->first()?->learner_reference_number)->toBe('LRN-101');

    assertDatabaseHas('person_roles', [
        'person_id' => $student->id,
        'reference_number' => 'STU-LOCAL-2',
    ]);
});

test('admin can upload and review student documents with campus scoping', function (): void {
    $context = adminStudentContext();
    Storage::fake('local');
    $student = createCampusStudent($context['campus'], 'Ana', 'Reyes');

    actingAs($context['admin'])
        ->post(route('admin.students.documents.store', ['campus' => $context['campus'], 'student' => $student]), [
            'document_type' => 'form_137',
            'file' => UploadedFile::fake()->create('form-137.pdf', 200, 'application/pdf'),
            'notes' => 'Certified true copy.',
        ])
        ->assertRedirect();

    $document = StudentDocument::query()->where('student_id', $student->id)->firstOrFail();

    Storage::disk('local')->assertExists($document->path);

    actingAs($context['admin'])
        ->patch(route('admin.students.documents.update', [
            'campus' => $context['campus'],
            'student' => $student,
            'document' => $document,
        ]), [
            'status' => 'verified',
            'notes' => 'Reviewed by registrar.',
        ])
        ->assertRedirect();

    expect($document->refresh()->status)->toBe('verified')
        ->and($document->reviewed_by)->toBe($context['admin']->id);
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

test('admin can record transfer credits and reject curriculum mismatches', function (): void {
    $context = adminStudentContext();
    $student = createCampusStudent($context['campus'], 'Tala', 'Garcia');

    actingAs($context['admin'])
        ->post(route('admin.students.transfer-credits.store', ['campus' => $context['campus'], 'student' => $student]), [
            'curriculum_id' => $context['curriculum']->id,
            'source_school_name' => 'Previous College',
            'source_school_address' => 'Old City',
            'previous_program' => 'BS Computer Science',
            'status' => 'approved',
            'subjects' => [
                [
                    'curriculum_item_id' => $context['curriculumItem']->id,
                    'previous_subject_code' => 'CS101',
                    'previous_subject_name' => 'Intro to Programming',
                    'previous_units' => 3,
                    'previous_grade' => '1.75',
                    'school_year' => '2025-2026',
                    'term' => 'First Term',
                    'status' => 'credited',
                    'credited_units' => 3,
                    'remarks' => 'Equivalent syllabus.',
                ],
            ],
        ])
        ->assertRedirect();

    $evaluation = TransferCreditEvaluation::query()
        ->where('student_id', $student->id)
        ->with('subjects')
        ->firstOrFail();

    expect($evaluation->status)->toBe('approved')
        ->and($evaluation->subjects)->toHaveCount(1)
        ->and($evaluation->subjects->first()->status)->toBe('credited')
        ->and($evaluation->subjects->first()->credited_units)->toBe('3.00');

    $otherCurriculum = Curriculum::query()->create([
        'program_id' => $context['program']->id,
        'name' => 'Other Curriculum',
        'code' => fake()->unique()->lexify('OTH???'),
        'effective_year' => 2027,
    ]);
    $otherSubject = Subject::query()->create([
        'institution_id' => $context['institution']->id,
        'name' => 'Other Subject',
        'code' => fake()->unique()->lexify('OTH???'),
    ]);
    $otherItem = CurriculumItem::query()->create([
        'curriculum_id' => $otherCurriculum->id,
        'subject_id' => $otherSubject->id,
        'year_level' => 1,
        'term_sequence' => 1,
        'credit_units' => 3,
    ]);

    actingAs($context['admin'])
        ->post(route('admin.students.transfer-credits.store', ['campus' => $context['campus'], 'student' => $student]), [
            'curriculum_id' => $context['curriculum']->id,
            'source_school_name' => 'Previous College',
            'status' => 'in_review',
            'subjects' => [
                [
                    'curriculum_item_id' => $otherItem->id,
                    'previous_subject_name' => 'Wrong Curriculum Subject',
                    'status' => 'credited',
                    'credited_units' => 3,
                ],
            ],
        ])
        ->assertSessionHasErrors('subjects');
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
