<?php

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\CurriculumElectiveGroup;
use App\Models\CurriculumItem;
use App\Models\EducationLevel;
use App\Models\Institution;
use App\Models\Person;
use App\Models\Program;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Validation\ValidationException;
use Modules\Enrollment\Actions\CreateEnrollment;
use Modules\Enrollment\Enums\EnrollmentClassification;
use Modules\Enrollment\Models\EnrollmentPeriod;

test('enrollment loads required subjects and validates elective group choices', function (): void {
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => 'KO']);
    $campus = Campus::query()->create(['institution_id' => $institution->id, 'name' => 'Main', 'code' => 'MAIN']);
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
        'tuition_per_unit' => 375,
        'laboratory_fee_per_subject' => 2000,
    ]);
    $curriculumElectiveGroup = CurriculumElectiveGroup::query()->create([
        'curriculum_id' => $curriculum->id,
        'code' => 'PROF-ELECTIVES',
        'name' => 'Professional Electives',
        'minimum_subjects' => 1,
        'maximum_subjects' => 1,
        'minimum_units' => 3,
        'maximum_units' => 3,
    ]);
    $curriculumItem = curriculumEnrollmentItem($institution->id, $curriculum->id, 'CORE101', true);
    $firstElective = curriculumEnrollmentItem($institution->id, $curriculum->id, 'ELEC101', false, $curriculumElectiveGroup->id);
    $secondElective = curriculumEnrollmentItem($institution->id, $curriculum->id, 'ELEC102', false, $curriculumElectiveGroup->id);
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
    $enrollmentPeriod = EnrollmentPeriod::query()->create([
        'campus_id' => $campus->id,
        'term_id' => $term->id,
        'name' => 'Regular Enrollment',
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addWeek(),
    ]);
    $person = Person::query()->create(['first_name' => 'Ana', 'last_name' => 'Reyes']);

    expect(fn () => app(CreateEnrollment::class)->execute(
        $person,
        $enrollmentPeriod,
        $curriculum,
        EnrollmentClassification::NewStudent,
    ))->toThrow(ValidationException::class);

    expect(fn () => app(CreateEnrollment::class)->execute(
        $person,
        $enrollmentPeriod,
        $curriculum,
        EnrollmentClassification::NewStudent,
        selectedElectiveItemIds: [$firstElective->id, $secondElective->id],
    ))->toThrow(ValidationException::class);

    $enrollment = app(CreateEnrollment::class)->execute(
        $person,
        $enrollmentPeriod,
        $curriculum,
        EnrollmentClassification::NewStudent,
        selectedElectiveItemIds: [$firstElective->id],
    );

    expect($enrollment->subjects)->toHaveCount(2)
        ->and($enrollment->subjects->pluck('curriculum_item_id')->all())
        ->toContain($curriculumItem->id, $firstElective->id)
        ->not->toContain($secondElective->id)
        ->and($enrollment->assessment->tuition_total)->toBe('2250.00')
        ->and($enrollment->assessment->total)->toBe('2250.00');
});

function curriculumEnrollmentItem(
    int $institutionId,
    int $curriculumId,
    string $code,
    bool $required,
    ?int $electiveGroupId = null,
): CurriculumItem {
    $subject = Subject::query()->create([
        'institution_id' => $institutionId,
        'name' => $code,
        'code' => $code,
    ]);

    return CurriculumItem::query()->create([
        'curriculum_id' => $curriculumId,
        'subject_id' => $subject->id,
        'credit_units' => 3,
        'contact_hours' => 3,
        'is_required' => $required,
        'elective_group' => $electiveGroupId ? 'PROF-ELECTIVES' : null,
        'elective_group_id' => $electiveGroupId,
    ]);
}
