<?php

declare(strict_types=1);

namespace Database\Seeders\Academic;

use App\Enums\PersonRole;
use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\Person;
use App\Models\Program;
use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Enrollment\Actions\CreateEnrollment;
use Modules\Enrollment\Enums\EnrollmentClassification;
use Modules\Enrollment\Models\Enrollment;
use Modules\Enrollment\Models\EnrollmentPeriod;
use Modules\Enrollment\Models\StudentDocument;
use Modules\Enrollment\Models\StudentProfile;

class AcademicStudentPopulationSeeder extends Seeder
{
    private const STUDENTS_PER_CAMPUS = 200;

    public function run(CreateEnrollment $createEnrollment): void
    {
        $this->clearSeededPopulation();

        $campuses = Campus::query()
            ->whereIn('code', ['MAIN', 'SOUTH', 'COLLEGE'])
            ->orderByRaw("case code when 'MAIN' then 1 when 'SOUTH' then 2 when 'COLLEGE' then 3 else 4 end")
            ->get();

        foreach ($campuses as $campus) {
            $this->seedCampusStudents($campus, $createEnrollment);
        }
    }

    private function seedCampusStudents(Campus $campus, CreateEnrollment $createEnrollment): void
    {
        $programs = Program::query()
            ->with([
                'curricula' => fn ($query) => $query->where('status', 'active')->orderBy('code'),
                'curricula.items.electiveGroup',
                'curricula.items.subject',
            ])
            ->where('campus_id', $campus->getKey())
            ->where('status', 'active')
            ->orderBy('code')
            ->get()
            ->filter(fn (Program $program): bool => ($program->settings['seed_campus_code'] ?? null) === $campus->code
                && $program->curricula->isNotEmpty())
            ->values();

        if ($programs->isEmpty()) {
            return;
        }

        for ($number = 1; $number <= self::STUDENTS_PER_CAMPUS; $number++) {
            $program = $programs[($number - 1) % $programs->count()];
            $curriculum = $program->curricula[($number - 1) % $program->curricula->count()];
            $section = $this->sectionFor($campus, $program, $number);
            $period = $this->periodFor($campus, $section);

            if (! $curriculum instanceof Curriculum || ! $section instanceof Section || ! $period instanceof EnrollmentPeriod) {
                continue;
            }

            $student = $this->seedStudent($campus, $program, $number);
            $this->seedProfile($student, $campus, $program, $section, $number);
            $this->seedGuardian($student, $campus, $number);
            $this->seedDocuments($student, $campus);
            $this->seedEnrollment($createEnrollment, $student, $period, $curriculum, $section, $number);
        }
    }

    private function clearSeededPopulation(): void
    {
        $studentIds = Person::query()
            ->where('email', 'like', 'student.%@koacademy.example')
            ->pluck('id');
        $guardianIds = Person::query()
            ->where('email', 'like', 'guardian.%@koacademy.example')
            ->pluck('id');

        if ($studentIds->isEmpty() && $guardianIds->isEmpty()) {
            return;
        }

        $enrollmentIds = Enrollment::query()
            ->whereIn('student_id', $studentIds)
            ->pluck('id');
        $assessmentIds = DB::table('enrollment_assessments')
            ->whereIn('enrollment_id', $enrollmentIds)
            ->pluck('id');

        DB::table('enrollment_assessment_lines')->whereIn('enrollment_assessment_id', $assessmentIds)->delete();
        DB::table('enrollment_assessments')->whereIn('id', $assessmentIds)->delete();
        DB::table('enrollment_status_histories')->whereIn('enrollment_id', $enrollmentIds)->delete();
        DB::table('enrollment_subjects')->whereIn('enrollment_id', $enrollmentIds)->delete();
        Enrollment::query()->whereIn('id', $enrollmentIds)->delete();
        StudentDocument::query()->whereIn('student_id', $studentIds)->delete();
        StudentProfile::query()->whereIn('person_id', $studentIds)->delete();
        DB::table('guardian_student')
            ->whereIn('student_id', $studentIds)
            ->orWhereIn('guardian_id', $guardianIds)
            ->delete();
        DB::table('person_roles')
            ->whereIn('person_id', $studentIds->merge($guardianIds)->all())
            ->delete();
        Person::query()->whereIn('id', $studentIds->merge($guardianIds)->all())->delete();
    }

    private function sectionFor(Campus $campus, Program $program, int $number): ?Section
    {
        $sections = Section::query()
            ->where('campus_id', $campus->getKey())
            ->where('program_id', $program->getKey())
            ->orderBy('code')
            ->get();

        if ($sections->isEmpty()) {
            return null;
        }

        return $sections[($number - 1) % $sections->count()];
    }

    private function periodFor(Campus $campus, Section $section): ?EnrollmentPeriod
    {
        return EnrollmentPeriod::query()
            ->where('campus_id', $campus->getKey())
            ->where('term_id', $section->term_id)
            ->where('active', true)
            ->first();
    }

    private function seedStudent(Campus $campus, Program $program, int $number): Person
    {
        $referenceNumber = sprintf('%s-%05d', $campus->code, $number);
        $firstName = $this->firstNames()[$number % count($this->firstNames())];
        $lastName = $this->lastNames()[$number % count($this->lastNames())];

        $student = Person::query()->updateOrCreate(
            ['email' => sprintf('student.%s.%03d@koacademy.example', strtolower($campus->code), $number)],
            [
                'first_name' => $firstName,
                'middle_name' => $this->middleNames()[$number % count($this->middleNames())],
                'last_name' => $lastName,
                'birth_date' => now()->subYears($this->ageFor($campus, $program, $number))->subDays($number)->toDateString(),
                'sex' => $number % 2 === 0 ? 'female' : 'male',
                'phone' => sprintf('+63917%07d', $number),
                'address' => "{$number} Learner Street, {$campus->name}",
                'status' => 'active',
                'metadata' => [
                    'seeded' => true,
                    'campus_code' => $campus->code,
                    'program_code' => $program->code,
                    'reference_number' => $referenceNumber,
                ],
            ],
        );

        $student->roles()->updateOrCreate(
            ['campus_id' => $campus->getKey(), 'role' => PersonRole::Student],
            ['reference_number' => $referenceNumber, 'active' => true],
        );

        return $student;
    }

    private function seedProfile(Person $student, Campus $campus, Program $program, Section $section, int $number): void
    {
        StudentProfile::query()->updateOrCreate(
            ['person_id' => $student->getKey()],
            [
                'campus_id' => $campus->getKey(),
                'psa_birth_certificate_number' => sprintf('PSA-%s-%05d', $campus->code, $number),
                'learner_reference_number' => sprintf('1%011d', ($campus->getKey() * 100000) + $number),
                'nationality' => 'Filipino',
                'civil_status' => 'single',
                'religion' => $number % 5 === 0 ? 'Prefer not to say' : 'Roman Catholic',
                'mother_tongue' => $number % 3 === 0 ? 'Filipino' : 'English',
                'is_indigenous_people' => $number % 37 === 0,
                'indigenous_community' => $number % 37 === 0 ? 'Self-identified IP community' : null,
                'has_disability' => $number % 41 === 0,
                'disability_type' => $number % 41 === 0 ? 'Learning support need' : null,
                'is_4ps_beneficiary' => $number % 6 === 0,
                'four_ps_household_id' => $number % 6 === 0 ? sprintf('4PS-%s-%05d', $campus->code, $number) : null,
                'annual_family_income_bracket' => $this->incomeBracket($number),
                'household_gross_income' => $this->householdIncome($number),
                'has_government_subsidy' => $number % 4 === 0,
                'subsidy_program' => $number % 4 === 0 ? $this->subsidyProgram($campus) : null,
                'emergency_contact_name' => "Guardian {$student->last_name}",
                'emergency_contact_relationship' => $number % 2 === 0 ? 'mother' : 'father',
                'emergency_contact_phone' => sprintf('+63918%07d', $number),
                'current_address' => $this->address($campus, $number),
                'permanent_address' => $this->address($campus, $number),
                'previous_school_name' => $number % 5 === 0 ? 'Previous Learning Center' : null,
                'previous_school_address' => $number % 5 === 0 ? 'Previous School City' : null,
                'previous_school_type' => $number % 5 === 0 ? 'private' : null,
                'last_grade_level_completed' => $this->lastGradeCompleted($program, $section),
                'last_school_year_attended' => '2025-2026',
                'senior_high_school_strand' => $program->code === 'SHS' ? ($section->code === 'SHS-11ABM' ? 'ABM' : 'STEM') : null,
                'college_year_level' => in_array($program->code, ['BSIT', 'BSBA'], true) ? $section->year_level : null,
                'reporting_flags' => [
                    'seeded' => true,
                    'ched_reportable' => in_array($program->code, ['BSIT', 'BSBA'], true),
                    'deped_lis_reportable' => in_array($program->code, ['ELEM', 'JHS', 'SHS'], true),
                    'campus_code' => $campus->code,
                ],
            ],
        );
    }

    private function seedGuardian(Person $student, Campus $campus, int $number): void
    {
        $guardian = Person::query()->updateOrCreate(
            ['email' => sprintf('guardian.%s.%03d@koacademy.example', strtolower($campus->code), $number)],
            [
                'first_name' => $this->guardianFirstNames()[$number % count($this->guardianFirstNames())],
                'last_name' => $student->last_name,
                'phone' => sprintf('+63919%07d', $number),
                'address' => $student->address,
                'status' => 'active',
                'metadata' => ['seeded' => true, 'campus_code' => $campus->code],
            ],
        );

        $guardian->roles()->updateOrCreate(
            ['campus_id' => $campus->getKey(), 'role' => PersonRole::Guardian],
            ['reference_number' => sprintf('GUA-%s-%05d', $campus->code, $number), 'active' => true],
        );

        DB::table('guardian_student')->updateOrInsert(
            ['guardian_id' => $guardian->getKey(), 'student_id' => $student->getKey()],
            [
                'relationship' => $number % 2 === 0 ? 'mother' : 'father',
                'is_primary' => true,
                'has_portal_access' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function seedDocuments(Person $student, Campus $campus): void
    {
        foreach (['student_photo', 'psa_birth_certificate', 'form_137', 'form_138'] as $type) {
            StudentDocument::query()->updateOrCreate(
                ['student_id' => $student->getKey(), 'campus_id' => $campus->getKey(), 'document_type' => $type],
                [
                    'disk' => 'local',
                    'path' => sprintf('seeded/%s/%s/%d.pdf', strtolower($campus->code), $type, $student->getKey()),
                    'original_name' => "{$type}.pdf",
                    'mime_type' => 'application/pdf',
                    'size' => 128000,
                    'status' => 'verified',
                    'issued_on' => now()->subYear()->toDateString(),
                    'reviewed_at' => now(),
                    'notes' => 'Seeded verified document for demo readiness.',
                    'metadata' => ['seeded' => true],
                ],
            );
        }
    }

    private function seedEnrollment(
        CreateEnrollment $createEnrollment,
        Person $student,
        EnrollmentPeriod $period,
        Curriculum $curriculum,
        Section $section,
        int $number,
    ): void {
        if (Enrollment::query()->where('student_id', $student->getKey())->where('enrollment_period_id', $period->getKey())->exists()) {
            return;
        }

        $createEnrollment->execute(
            student: $student,
            period: $period,
            curriculum: $curriculum,
            classification: $this->classification($number),
            sectionId: $section->getKey(),
            selectedElectiveItemIds: $this->selectedElectiveItemIds($curriculum, $period, $section),
            yearLevel: $section->year_level,
        );
    }

    /**
     * @return int[]
     */
    private function selectedElectiveItemIds(Curriculum $curriculum, EnrollmentPeriod $period, Section $section): array
    {
        $items = $curriculum->items()
            ->with('electiveGroup')
            ->where(fn ($query) => $query->whereNull('year_level')->orWhere('year_level', $section->year_level))
            ->where(fn ($query) => $query->whereNull('term_sequence')->orWhere('term_sequence', $period->term()->value('sequence')))
            ->get();

        return $items
            ->where('is_required', false)
            ->groupBy('elective_group_id')
            ->flatMap(function (Collection $groupItems): Collection {
                $group = $groupItems->first()?->electiveGroup;
                $minimumSubjects = $group ? min($group->minimum_subjects, $groupItems->count()) : 0;

                return $groupItems->take($minimumSubjects)->pluck('id');
            })
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    private function classification(int $number): EnrollmentClassification
    {
        return match ($number % 5) {
            0 => EnrollmentClassification::Transferee,
            1 => EnrollmentClassification::NewStudent,
            2 => EnrollmentClassification::Continuing,
            3 => EnrollmentClassification::Returning,
            default => EnrollmentClassification::CrossEnrolled,
        };
    }

    private function ageFor(Campus $campus, Program $program, int $number): int
    {
        if ($campus->code === 'MAIN') {
            return 6 + ($number % 6);
        }

        if ($program->code === 'SHS') {
            return 16 + ($number % 2);
        }

        if ($campus->code === 'SOUTH') {
            return 12 + ($number % 4);
        }

        return 18 + ($number % 5);
    }

    private function incomeBracket(int $number): string
    {
        return ['below_100000', '100001_250000', '250001_500000', '500001_1000000', 'above_1000000'][$number % 5];
    }

    private function householdIncome(int $number): int
    {
        return [85000, 180000, 360000, 720000, 1200000][$number % 5];
    }

    private function subsidyProgram(Campus $campus): string
    {
        return $campus->code === 'COLLEGE' ? 'UniFAST / TES' : 'ESC / SHS voucher';
    }

    /**
     * @return array<string, string>
     */
    private function address(Campus $campus, int $number): array
    {
        return [
            'line1' => "{$number} Learner Street",
            'barangay' => 'Barangay '.(($number % 20) + 1),
            'city' => $campus->code === 'COLLEGE' ? 'Taguig City' : 'Quezon City',
            'province' => 'Metro Manila',
            'postal_code' => $campus->code === 'COLLEGE' ? '1630' : '1100',
            'country' => 'Philippines',
        ];
    }

    private function lastGradeCompleted(Program $program, Section $section): ?string
    {
        return match ($program->code) {
            'ELEM' => 'Kindergarten',
            'JHS' => 'Grade '.max((int) $section->year_level - 1, 6),
            'SHS' => 'Grade 10',
            'BSIT', 'BSBA' => 'Grade 12',
            default => null,
        };
    }

    /**
     * @return string[]
     */
    private function firstNames(): array
    {
        return ['Ana', 'Ben', 'Carla', 'Diego', 'Ella', 'Francis', 'Gina', 'Hector', 'Iris', 'Jonas', 'Kara', 'Luis'];
    }

    /**
     * @return string[]
     */
    private function middleNames(): array
    {
        return ['Reyes', 'Cruz', 'Santos', 'Garcia', 'Mendoza', 'Flores'];
    }

    /**
     * @return string[]
     */
    private function lastNames(): array
    {
        return ['Dela Cruz', 'Santos', 'Reyes', 'Garcia', 'Mendoza', 'Ramos', 'Aquino', 'Bautista', 'Castro', 'Torres'];
    }

    /**
     * @return string[]
     */
    private function guardianFirstNames(): array
    {
        return ['Maria', 'Jose', 'Lorna', 'Ramon', 'Teresa', 'Antonio', 'Elena', 'Roberto'];
    }
}
