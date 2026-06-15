<?php

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Curriculum;
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
use Modules\Enrollment\Models\Enrollment;
use Modules\Enrollment\Models\EnrollmentPeriod;

test('unconfigured existing curricula cannot create financial assessments', function (): void {
    $context = pricingAssessmentContext(configurePricing: false);

    expect(fn () => app(CreateEnrollment::class)->execute(
        $context['student'],
        $context['period'],
        $context['curriculum'],
        EnrollmentClassification::NewStudent,
    ))->toThrow(ValidationException::class);

    expect(Enrollment::query()->count())->toBe(0);
});

test('assessment charges units flat laboratory subjects and curriculum miscellaneous fees once', function (): void {
    $context = pricingAssessmentContext();
    $curriculum = $context['curriculum'];
    $curriculum->miscellaneousFees()->createMany([
        ['code' => 'REG', 'name' => 'Registration Fee', 'amount' => 500, 'is_active' => true, 'position' => 1],
        ['code' => 'LIB', 'name' => 'Library Fee', 'amount' => 300, 'is_active' => true, 'position' => 2],
        ['code' => 'OLD', 'name' => 'Inactive Fee', 'amount' => 999, 'is_active' => false, 'position' => 3],
    ]);
    assessmentItem($curriculum, 'CORE101', 1, 1, 3, 0);
    assessmentItem($curriculum, 'LAB201', 1, 1, 2, 2);
    assessmentItem($curriculum, 'LAB202', 1, 1, 1, 6);
    assessmentItem($curriculum, 'NEXTTERM', 1, 2, 20, 0);
    assessmentItem($curriculum, 'NEXTYEAR', 2, 1, 20, 0);

    $enrollment = app(CreateEnrollment::class)->execute(
        $context['student'],
        $context['period'],
        $curriculum,
        EnrollmentClassification::NewStudent,
    );
    $assessment = $enrollment->assessment;

    expect($enrollment->subjects)->toHaveCount(3)
        ->and($assessment->currency)->toBe('PHP')
        ->and($assessment->tuition_total)->toBe('2250.00')
        ->and($assessment->laboratory_total)->toBe('4000.00')
        ->and($assessment->miscellaneous_total)->toBe('800.00')
        ->and($assessment->total)->toBe('7050.00')
        ->and($assessment->lines()->where('type', 'tuition')->count())->toBe(3)
        ->and($assessment->lines()->where('type', 'laboratory')->count())->toBe(2)
        ->and($assessment->lines()->where('type', 'miscellaneous')->count())->toBe(2)
        ->and($assessment->lines()->where('type', 'laboratory')->pluck('amount')->map(
            fn (mixed $amount): float => (float) $amount,
        )->all())
        ->each->toBe(2000.0);
});

test('assessment snapshots do not change when curriculum pricing is edited later', function (): void {
    $context = pricingAssessmentContext();
    $curriculum = $context['curriculum'];
    $fee = $curriculum->miscellaneousFees()->create([
        'code' => 'REG',
        'name' => 'Registration Fee',
        'amount' => 500,
    ]);
    assessmentItem($curriculum, 'LAB101', 1, 1, 3, 4);

    $assessment = app(CreateEnrollment::class)->execute(
        $context['student'],
        $context['period'],
        $curriculum,
        EnrollmentClassification::NewStudent,
    )->assessment;

    $curriculum->update(['tuition_per_unit' => 999, 'laboratory_fee_per_subject' => 9999]);
    $fee->update(['name' => 'Changed Fee', 'amount' => 900]);
    $assessment->refresh();

    expect($assessment->tuition_total)->toBe('1125.00')
        ->and($assessment->laboratory_total)->toBe('2000.00')
        ->and($assessment->miscellaneous_total)->toBe('500.00')
        ->and($assessment->total)->toBe('3625.00')
        ->and($assessment->lines()->where('type', 'miscellaneous')->value('description'))->toBe('Registration Fee');
});

/**
 * @return array{
 *     curriculum: Curriculum,
 *     student: Person,
 *     period: EnrollmentPeriod
 * }
 */
function pricingAssessmentContext(bool $configurePricing = true): array
{
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => fake()->unique()->lexify('KO???')]);
    $campus = Campus::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Main Campus',
        'code' => fake()->unique()->lexify('MAIN???'),
    ]);
    $educationLevel = EducationLevel::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Undergraduate',
        'code' => fake()->unique()->lexify('UG???'),
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
        'tuition_per_unit' => $configurePricing ? 375 : null,
        'laboratory_fee_per_subject' => $configurePricing ? 2000 : null,
    ]);
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
    $period = EnrollmentPeriod::query()->create([
        'campus_id' => $campus->id,
        'term_id' => $term->id,
        'name' => 'Regular Enrollment',
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addWeek(),
    ]);
    $student = Person::query()->create(['first_name' => 'Ana', 'last_name' => 'Reyes']);

    if (! $configurePricing) {
        assessmentItem($curriculum, 'CORE101', 1, 1, 3, 0);
    }

    return compact('curriculum', 'student', 'period');
}

function assessmentItem(
    Curriculum $curriculum,
    string $code,
    int $yearLevel,
    int $termSequence,
    float $creditUnits,
    float $labHours,
): CurriculumItem {
    $subject = Subject::query()->create([
        'institution_id' => $curriculum->program->campus->institution_id,
        'name' => $code,
        'code' => $code,
        'subject_type' => $labHours > 0 ? 'laboratory' : 'academic',
    ]);

    return CurriculumItem::query()->create([
        'curriculum_id' => $curriculum->id,
        'subject_id' => $subject->id,
        'year_level' => $yearLevel,
        'term_sequence' => $termSequence,
        'credit_units' => $creditUnits,
        'contact_hours' => $creditUnits,
        'lab_hours' => $labHours,
        'is_required' => true,
    ]);
}
