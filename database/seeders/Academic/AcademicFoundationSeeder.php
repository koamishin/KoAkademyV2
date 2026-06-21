<?php

declare(strict_types=1);

namespace Database\Seeders\Academic;

use App\Models\AcademicSetting;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\CurriculumElectiveGroup;
use App\Models\CurriculumItem;
use App\Models\CurriculumMiscellaneousFee;
use App\Models\EducationLevel;
use App\Models\Institution;
use App\Models\Program;
use App\Models\Room;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Term;
use App\Settings\ApplicationDetailsSettings;
use App\Settings\ApplicationFeaturesSettings;
use App\Settings\ApplicationSecuritySettings;
use App\Settings\ApplicationSetupSettings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Classroom\Models\ClassMeeting;
use Modules\Classroom\Models\ClassOffering;
use Modules\Enrollment\Models\EnrollmentPeriod;

class AcademicFoundationSeeder extends Seeder
{
    use WithoutModelEvents;

    public function __construct(private readonly AcademicSeedCatalog $catalog = new AcademicSeedCatalog) {}

    public function run(): void
    {
        DB::transaction(function (): void {
            $institution = $this->seedInstitution();
            $campuses = $this->seedCampuses($institution);
            $academicYear = $this->seedAcademicYear($institution);
            $terms = $this->seedTerms($academicYear);
            $levels = $this->seedEducationLevels($institution);

            $this->seedSettings();
            $this->seedRooms($campuses);
            $this->seedPrograms($institution, $campuses->keyBy('code'), $levels, $terms);
            $this->seedEnrollmentPeriods($campuses, $terms);
            $this->seedModuleSettings();
            $this->seedApplicationSettings($institution, $campuses->first(), $academicYear, $terms);
        });
    }

    private function seedInstitution(): Institution
    {
        $data = $this->catalog->institution();

        return Institution::query()->updateOrCreate(
            ['code' => $data['code']],
            collect($data)->except('code')->all(),
        );
    }

    /**
     * @return Collection<int, Campus>
     */
    private function seedCampuses(Institution $institution): Collection
    {
        return collect($this->catalog->campuses())
            ->map(fn (array $campus): Campus => Campus::query()->updateOrCreate(
                ['institution_id' => $institution->id, 'code' => $campus['code']],
                collect($campus)->except('code')->all(),
            )->ensureSlug())
            ->values();
    }

    private function seedAcademicYear(Institution $institution): AcademicYear
    {
        $data = $this->catalog->academicYear();

        AcademicYear::query()
            ->where('institution_id', $institution->id)
            ->where('name', '!=', $data['name'])
            ->update(['is_current' => false]);

        return AcademicYear::query()->updateOrCreate(
            ['institution_id' => $institution->id, 'name' => $data['name']],
            collect($data)->except('name')->all(),
        );
    }

    /**
     * @return Collection<string, Term>
     */
    private function seedTerms(AcademicYear $academicYear): Collection
    {
        return collect($this->catalog->terms())
            ->mapWithKeys(fn (array $term): array => [
                $term['code'] => Term::query()->updateOrCreate(
                    ['academic_year_id' => $academicYear->id, 'code' => $term['code']],
                    collect($term)->except('code')->all(),
                ),
            ]);
    }

    /**
     * @return Collection<string, EducationLevel>
     */
    private function seedEducationLevels(Institution $institution): Collection
    {
        return collect($this->catalog->educationLevels())
            ->mapWithKeys(fn (array $level): array => [
                $level['code'] => EducationLevel::query()->updateOrCreate(
                    ['institution_id' => $institution->id, 'code' => $level['code']],
                    collect($level)->except('code')->all(),
                ),
            ]);
    }

    private function seedSettings(): void
    {
        foreach ($this->catalog->operations() as $key => $value) {
            AcademicSetting::query()->updateOrCreate(
                ['campus_id' => null, 'key' => $key],
                ['value' => (array) $value],
            );
        }
    }

    /**
     * @param  Collection<int, Campus>  $campuses
     */
    private function seedRooms(Collection $campuses): void
    {
        foreach ($campuses as $campus) {
            foreach ($this->catalog->rooms() as $room) {
                Room::query()->updateOrCreate(
                    ['campus_id' => $campus->id, 'code' => $room['code']],
                    collect($room)->except('code')->all(),
                );
            }
        }
    }

    /**
     * @param  Collection<string, Campus>  $campuses
     * @param  Collection<string, EducationLevel>  $levels
     * @param  Collection<string, Term>  $terms
     */
    private function seedPrograms(Institution $institution, Collection $campuses, Collection $levels, Collection $terms): void
    {
        foreach ($this->catalog->programs() as $programData) {
            $campus = $campuses->get($programData['campus_code'] ?? 'MAIN');
            $level = $levels->get($programData['level_code']);
            $term = $terms->get($programData['term_code']);

            if (! $campus instanceof Campus || ! $level instanceof EducationLevel || ! $term instanceof Term) {
                continue;
            }

            $program = Program::query()->updateOrCreate(
                ['campus_id' => $campus->id, 'code' => $programData['code']],
                [
                    'education_level_id' => $level->id,
                    'name' => $programData['name'],
                    'award_type' => $programData['award_type'],
                    'status' => 'active',
                    'settings' => [
                        'seeded' => true,
                        'seed_level_code' => $programData['level_code'],
                        'seed_campus_code' => $campus->code,
                    ],
                ],
            );

            foreach ($programData['sections'] as $sectionData) {
                Section::query()->updateOrCreate(
                    ['campus_id' => $campus->id, 'term_id' => $term->id, 'code' => $sectionData['code']],
                    [
                        'program_id' => $program->id,
                        'name' => $sectionData['name'],
                        'year_level' => $sectionData['year_level'],
                        'capacity' => $sectionData['capacity'],
                        'status' => 'active',
                    ],
                );
            }

            foreach ($programData['curricula'] as $curriculumData) {
                $this->seedCurriculum($institution, $campus, $program, $term, $curriculumData);
            }
        }

        $this->retireStaleSeededPrograms($institution);
    }

    private function retireStaleSeededPrograms(Institution $institution): void
    {
        $expectedCampusesByProgramCode = collect($this->catalog->programs())
            ->mapWithKeys(fn (array $program): array => [$program['code'] => $program['campus_code']]);

        Program::query()
            ->with('campus')
            ->whereHas('campus', fn ($query) => $query->where('institution_id', $institution->getKey()))
            ->whereIn('code', $expectedCampusesByProgramCode->keys()->all())
            ->get()
            ->each(function (Program $program) use ($expectedCampusesByProgramCode): void {
                $expectedCampusCode = $expectedCampusesByProgramCode->get($program->code);
                $currentCampusCode = $program->campus?->code;

                if ($currentCampusCode === $expectedCampusCode) {
                    return;
                }

                $settings = $program->settings ?? [];

                $program->update([
                    'status' => 'inactive',
                    'settings' => [
                        ...$settings,
                        'seeded' => true,
                        'retired_by_academic_seed' => true,
                        'expected_campus_code' => $expectedCampusCode,
                    ],
                ]);

                $program->curricula()->update(['status' => 'archived']);
            });
    }

    /**
     * @param  array<string, mixed>  $curriculumData
     */
    private function seedCurriculum(Institution $institution, Campus $campus, Program $program, Term $term, array $curriculumData): Curriculum
    {
        $curriculum = Curriculum::query()->updateOrCreate(
            ['program_id' => $program->id, 'code' => Str::upper($curriculumData['code'])],
            [
                'name' => $curriculumData['name'],
                'effective_year' => $curriculumData['effective_year'],
                'template_version' => $curriculumData['template_version'],
                'template_authority' => $curriculumData['template_authority'],
                'template_source_url' => $curriculumData['template_source_url'],
                'is_customized' => $curriculumData['is_customized'],
                'currency' => $curriculumData['currency'],
                'tuition_per_unit' => $curriculumData['tuition_per_unit'],
                'laboratory_fee_per_subject' => $curriculumData['laboratory_fee_per_subject'],
                'status' => $curriculumData['status'],
            ],
        );

        $groups = $this->seedElectiveGroups($curriculum, $curriculumData['elective_groups']);
        $subjectsByCode = collect();

        foreach ($curriculumData['subjects'] as $position => $subjectData) {
            $subject = $this->seedSubject($institution, $subjectData);
            $subjectsByCode->put($subject->code, $subject);

            CurriculumItem::query()->updateOrCreate(
                ['curriculum_id' => $curriculum->id, 'subject_id' => $subject->id],
                [
                    'year_level' => $subjectData['year_level'],
                    'term_sequence' => $subjectData['term_sequence'],
                    'position' => $position + 1,
                    'elective_group' => $subjectData['elective_group'] ? Str::upper($subjectData['elective_group']) : null,
                    'elective_group_id' => $subjectData['elective_group'] ? $groups->get(Str::upper($subjectData['elective_group']))?->id : null,
                    'credit_units' => $subjectData['credit_units'],
                    'contact_hours' => $subjectData['contact_hours'],
                    'lab_hours' => $subjectData['lab_hours'],
                    'competency_hours' => $subjectData['competency_hours'],
                    'is_required' => $subjectData['is_required'],
                ],
            );
        }

        foreach ($curriculumData['subjects'] as $subjectData) {
            $subject = $subjectsByCode->get(Str::upper($subjectData['code']));

            foreach ($subjectData['prerequisites'] as $prerequisiteCode) {
                $prerequisite = $subjectsByCode->get(Str::upper($prerequisiteCode));

                if ($subject instanceof Subject && $prerequisite instanceof Subject) {
                    DB::table('subject_prerequisites')->updateOrInsert(
                        ['subject_id' => $subject->id, 'prerequisite_subject_id' => $prerequisite->id],
                        ['requirement' => 'completed', 'created_at' => now(), 'updated_at' => now()],
                    );
                }
            }
        }

        $this->seedMiscellaneousFees($curriculum, $curriculumData['miscellaneous_fees']);
        $this->seedClassOfferings($campus, $program, $term, $curriculum);

        return $curriculum;
    }

    /**
     * @param  array<int, array<string, mixed>>  $subjectData
     */
    private function seedSubject(Institution $institution, array $subjectData): Subject
    {
        return Subject::query()->updateOrCreate(
            ['institution_id' => $institution->id, 'code' => Str::upper($subjectData['code'])],
            [
                'name' => $subjectData['name'],
                'description' => null,
                'subject_type' => $subjectData['subject_type'],
                'default_credit_units' => $subjectData['credit_units'],
                'default_contact_hours' => $subjectData['contact_hours'],
                'default_lab_hours' => $subjectData['lab_hours'],
                'default_competency_hours' => $subjectData['competency_hours'],
                'status' => 'active',
            ],
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $groups
     * @return Collection<string, CurriculumElectiveGroup>
     */
    private function seedElectiveGroups(Curriculum $curriculum, array $groups): Collection
    {
        return collect($groups)->mapWithKeys(function (array $group) use ($curriculum): array {
            $model = CurriculumElectiveGroup::query()->updateOrCreate(
                ['curriculum_id' => $curriculum->id, 'code' => Str::upper($group['code'])],
                [
                    'name' => $group['name'],
                    'minimum_subjects' => $group['minimum_subjects'] ?? 0,
                    'maximum_subjects' => $group['maximum_subjects'] ?? null,
                    'minimum_units' => $group['minimum_units'] ?? 0,
                    'maximum_units' => $group['maximum_units'] ?? null,
                ],
            );

            return [$model->code => $model];
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $fees
     */
    private function seedMiscellaneousFees(Curriculum $curriculum, array $fees): void
    {
        foreach ($fees as $position => $fee) {
            CurriculumMiscellaneousFee::query()->updateOrCreate(
                ['curriculum_id' => $curriculum->id, 'code' => Str::upper($fee['code'])],
                [
                    'name' => $fee['name'],
                    'description' => $fee['description'] ?? null,
                    'amount' => $fee['amount'],
                    'is_active' => $fee['is_active'],
                    'position' => $position + 1,
                ],
            );
        }
    }

    private function seedClassOfferings(Campus $campus, Program $program, Term $term, Curriculum $curriculum): void
    {
        $section = Section::query()
            ->where('campus_id', $campus->id)
            ->where('program_id', $program->id)
            ->where('term_id', $term->id)
            ->orderBy('year_level')
            ->first();
        $room = Room::query()->where('campus_id', $campus->id)->orderBy('code')->first();

        if (! $section instanceof Section || ! $room instanceof Room) {
            return;
        }

        $curriculum->items()
            ->with('subject')
            ->where('term_sequence', 1)
            ->where('is_required', true)
            ->limit(2)
            ->get()
            ->each(function (CurriculumItem $item, int $index) use ($campus, $term, $section, $room): void {
                $subject = $item->subject;
                $code = "{$section->code}-{$subject->code}";
                $offering = ClassOffering::query()->updateOrCreate(
                    ['term_id' => $term->id, 'code' => $code],
                    [
                        'campus_id' => $campus->id,
                        'subject_id' => $subject->id,
                        'section_id' => $section->id,
                        'name' => "{$subject->name} - {$section->name}",
                        'capacity' => $section->capacity,
                        'status' => 'published',
                    ],
                );

                ClassMeeting::query()->updateOrCreate(
                    ['class_offering_id' => $offering->id, 'day_of_week' => $index + 1],
                    [
                        'room_id' => $room->id,
                        'starts_at' => sprintf('%02d:00', 8 + $index),
                        'ends_at' => sprintf('%02d:00', 9 + $index),
                        'recurs_from' => $term->starts_on,
                        'recurs_until' => $term->ends_on,
                        'cancelled' => false,
                    ],
                );
            });
    }

    /**
     * @param  Collection<int, Campus>  $campuses
     * @param  Collection<string, Term>  $terms
     */
    private function seedEnrollmentPeriods(Collection $campuses, Collection $terms): void
    {
        foreach ($campuses as $campus) {
            foreach ($terms as $term) {
                EnrollmentPeriod::query()->updateOrCreate(
                    ['campus_id' => $campus->id, 'term_id' => $term->id, 'name' => "{$term->name} Enrollment"],
                    [
                        'opens_at' => $term->starts_on->copy()->subMonth(),
                        'closes_at' => $term->starts_on->copy()->addWeeks(2),
                        'active' => true,
                        'policies' => [
                            'allow_new' => true,
                            'allow_continuing' => true,
                            'allow_transferee' => true,
                        ],
                    ],
                );
            }
        }
    }

    private function seedModuleSettings(): void
    {
        foreach (['academic_core', 'admissions', 'enrollment', 'classroom'] as $module) {
            DB::table('academic_module_settings')->updateOrInsert(
                ['module' => $module],
                ['enabled' => true, 'settings' => json_encode(['seeded' => true]), 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    /**
     * @param  Collection<string, Term>  $terms
     */
    private function seedApplicationSettings(Institution $institution, Campus $campus, AcademicYear $academicYear, Collection $terms): void
    {
        $applicationDetailsSettings = app(ApplicationDetailsSettings::class);

        foreach ($this->catalog->applicationDetails() as $key => $value) {
            $applicationDetailsSettings->{$key} = $value;
        }

        $applicationDetailsSettings->save();

        $applicationFeaturesSettings = app(ApplicationFeaturesSettings::class);

        foreach ($this->catalog->applicationFeatures() as $key => $value) {
            $applicationFeaturesSettings->{$key} = $value;
        }

        $applicationFeaturesSettings->save();

        $applicationSecuritySettings = app(ApplicationSecuritySettings::class);

        foreach ($this->catalog->applicationSecurity() as $key => $value) {
            $applicationSecuritySettings->{$key} = $value;
        }

        $applicationSecuritySettings->save();

        $applicationSetupSettings = app(ApplicationSetupSettings::class);
        $applicationSetupSettings->setup_version = 1;
        $applicationSetupSettings->status = 'completed';
        $applicationSetupSettings->current_step = 7;
        $applicationSetupSettings->draft = $this->setupDraft($institution, $campus, $academicYear, $terms);
        $applicationSetupSettings->completed_at ??= now()->toISOString();
        $applicationSetupSettings->completed_by_user_id = null;
        $applicationSetupSettings->save();
    }

    /**
     * @param  Collection<string, Term>  $terms
     * @return array<string, mixed>
     */
    private function setupDraft(Institution $institution, Campus $campus, AcademicYear $academicYear, Collection $terms): array
    {
        return [
            'institution_name' => $institution->name,
            'institution_code' => $institution->code,
            'locale' => $institution->locale,
            'timezone' => $institution->timezone,
            'site_description' => $this->catalog->applicationDetails()['site_description'],
            'contact_email' => $this->catalog->applicationDetails()['contact_email'],
            'support_phone' => $this->catalog->applicationDetails()['support_phone'],
            'support_url' => $this->catalog->applicationDetails()['support_url'],
            'site_logo_path' => null,
            'site_favicon_path' => null,
            'campus_name' => $campus->name,
            'campus_code' => $campus->code,
            'campus_slug' => $campus->slug,
            'campus_address' => $campus->address,
            'campus_timezone' => $campus->timezone,
            'education_profiles' => ['grade_school', 'high_school', 'college'],
            'academic_year_name' => $academicYear->name,
            'academic_year_starts_on' => $academicYear->starts_on->toDateString(),
            'academic_year_ends_on' => $academicYear->ends_on->toDateString(),
            'term_template' => 'custom',
            'terms' => $terms
                ->values()
                ->map(fn (Term $term): array => [
                    'name' => $term->name,
                    'code' => $term->code,
                    'starts_on' => $term->starts_on->toDateString(),
                    'ends_on' => $term->ends_on->toDateString(),
                ])
                ->all(),
            ...$this->catalog->operations(),
            'modules' => [
                'admissions' => true,
                'enrollment' => true,
                'classroom' => true,
                'notifications' => true,
            ],
            ...$this->catalog->applicationFeatures(),
            ...$this->catalog->applicationSecurity(),
        ];
    }
}
