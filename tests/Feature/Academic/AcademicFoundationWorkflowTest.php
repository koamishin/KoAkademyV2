<?php

use App\Actions\Academic\ApplyAcademicPreset;
use App\Models\AcademicSetting;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\CurriculumItem;
use App\Models\EducationLevel;
use App\Models\Institution;
use App\Models\Person;
use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Modules\Classroom\Actions\GradeSubmission;
use Modules\Classroom\Actions\SubmitAssignment;
use Modules\Classroom\Models\Assignment;
use Modules\Classroom\Models\ClassMember;
use Modules\Classroom\Models\ClassOffering;
use Modules\Enrollment\Actions\ApproveEnrollment;
use Modules\Enrollment\Actions\CreateEnrollment;
use Modules\Enrollment\Enums\EnrollmentClassification;
use Modules\Enrollment\Enums\EnrollmentStatus;
use Modules\Enrollment\Models\EnrollmentPeriod;

beforeEach(function (): void {
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => 'KO']);
    $campus = Campus::query()->create(['institution_id' => $institution->id, 'name' => 'Main', 'code' => 'MAIN']);
    $academicYear = AcademicYear::query()->create([
        'institution_id' => $institution->id,
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
        'institution_id' => $institution->id,
        'name' => 'College',
        'code' => 'COL',
        'category' => 'college',
    ]);
    $program = Program::query()->create([
        'campus_id' => $campus->id,
        'education_level_id' => $educationLevel->id,
        'name' => 'Information Technology',
        'code' => 'BSIT',
    ]);
    $curriculum = Curriculum::query()->create([
        'program_id' => $program->id,
        'name' => 'BSIT 2026',
        'code' => 'BSIT-2026',
        'effective_year' => 2026,
    ]);
    $subject = Subject::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Programming Fundamentals',
        'code' => 'PROG101',
    ]);
    CurriculumItem::query()->create([
        'curriculum_id' => $curriculum->id,
        'subject_id' => $subject->id,
        'credit_units' => 3,
        'contact_hours' => 3,
    ]);
    $section = Section::query()->create([
        'campus_id' => $campus->id,
        'program_id' => $program->id,
        'term_id' => $term->id,
        'name' => 'BSIT 1A',
        'code' => 'BSIT-1A',
        'capacity' => 1,
    ]);
    $enrollmentPeriod = EnrollmentPeriod::query()->create([
        'campus_id' => $campus->id,
        'term_id' => $term->id,
        'name' => 'Regular Enrollment',
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addWeek(),
    ]);

    $this->academic = compact(
        'institution',
        'campus',
        'term',
        'curriculum',
        'subject',
        'section',
        'enrollmentPeriod',
    );
});

test('starter presets are editable and idempotent while guardian links support multiple students', function (): void {
    $institution = $this->academic['institution'];

    app(ApplyAcademicPreset::class)->execute($institution, 'grade_school');
    app(ApplyAcademicPreset::class)->execute($institution, 'grade_school');

    $guardian = Person::query()->create(['first_name' => 'Maria', 'last_name' => 'Santos']);
    $firstStudent = Person::query()->create(['first_name' => 'Ana', 'last_name' => 'Santos']);
    $secondStudent = Person::query()->create(['first_name' => 'Leo', 'last_name' => 'Santos']);
    $guardian->students()->attach([
        $firstStudent->id => ['relationship' => 'mother', 'is_primary' => true],
        $secondStudent->id => ['relationship' => 'mother', 'is_primary' => false],
    ]);

    expect(EducationLevel::query()->where('institution_id', $institution->id)->where('category', 'grade_school')->count())->toBe(7)
        ->and($guardian->students()->count())->toBe(2)
        ->and($firstStudent->guardians()->whereKey($guardian->id)->exists())->toBeTrue();
});

test('enrollment loads curriculum subjects rejects duplicates and waitlists at section capacity', function (): void {
    $student = Person::query()->create(['first_name' => 'Ana', 'last_name' => 'Reyes']);
    $secondStudent = Person::query()->create(['first_name' => 'Ben', 'last_name' => 'Cruz']);
    $actor = User::factory()->create();
    AcademicSetting::query()->create([
        'key' => 'student_number_format',
        'value' => ['STU-{year}-{sequence:4}'],
    ]);

    $enrollment = app(CreateEnrollment::class)->execute(
        $student,
        $this->academic['enrollmentPeriod'],
        $this->academic['curriculum'],
        EnrollmentClassification::NewStudent,
        $this->academic['section']->id,
    );

    expect($enrollment->subjects)->toHaveCount(1);
    expect(fn () => app(CreateEnrollment::class)->execute(
        $student,
        $this->academic['enrollmentPeriod'],
        $this->academic['curriculum'],
        EnrollmentClassification::NewStudent,
    ))->toThrow(ValidationException::class);

    $approved = app(ApproveEnrollment::class)->execute($enrollment, $actor);
    $waitlisted = app(ApproveEnrollment::class)->execute(
        app(CreateEnrollment::class)->execute(
            $secondStudent,
            $this->academic['enrollmentPeriod'],
            $this->academic['curriculum'],
            EnrollmentClassification::NewStudent,
            $this->academic['section']->id,
        ),
        $actor,
    );

    expect($approved->status)->toBe(EnrollmentStatus::Approved)
        ->and($approved->student_number)->toBe('STU-'.now()->format('Y').'-0001')
        ->and($waitlisted->status)->toBe(EnrollmentStatus::Waitlisted)
        ->and($waitlisted->student_number)->toBeNull();
});

test('class members can submit work and scores cannot exceed assignment points', function (): void {
    $student = Person::query()->create(['first_name' => 'Ana', 'last_name' => 'Reyes']);
    $teacher = User::factory()->create();
    $classOffering = ClassOffering::query()->create([
        'campus_id' => $this->academic['campus']->id,
        'term_id' => $this->academic['term']->id,
        'subject_id' => $this->academic['subject']->id,
        'name' => 'Programming 1A',
        'code' => 'PROG-1A',
    ]);
    ClassMember::query()->create([
        'class_offering_id' => $classOffering->id,
        'person_id' => $student->id,
    ]);
    $assignment = Assignment::query()->create([
        'class_offering_id' => $classOffering->id,
        'author_id' => $teacher->id,
        'title' => 'First Exercise',
        'points' => 100,
    ]);

    $submission = app(SubmitAssignment::class)->execute($assignment, $student, 'My answer');
    $graded = app(GradeSubmission::class)->execute($submission, $teacher, 95, 'Good work');

    expect($graded->status)->toBe('returned')
        ->and($graded->score)->toBe('95.00')
        ->and($graded->feedback)->toBe('Good work');

    expect(fn () => app(GradeSubmission::class)->execute($graded, $teacher, 101))
        ->toThrow(ValidationException::class);
});
