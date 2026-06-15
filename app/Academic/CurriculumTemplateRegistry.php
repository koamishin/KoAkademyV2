<?php

declare(strict_types=1);

namespace App\Academic;

use App\Models\EducationLevel;
use App\Models\Program;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class CurriculumTemplateRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return [
            ...$this->matatagTemplates(),
            ...$this->strengthenedSeniorHighTemplates(),
            'ched-bsit-2015' => $this->bsitTemplate(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function optionsFor(EducationLevel $educationLevel, ?Program $program = null): array
    {
        return collect($this->all())
            ->filter(fn (array $template): bool => $this->supports($template, $educationLevel, $program))
            ->mapWithKeys(fn (array $template, string $key): array => [
                $key => "{$template['name']} - {$template['version']}",
            ])
            ->prepend('Start with a flexible blank curriculum', 'blank')
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function blank(): array
    {
        return [
            'key' => 'blank',
            'name' => 'Flexible curriculum',
            'version' => 'Custom',
            'authority' => null,
            'order' => null,
            'source_url' => null,
            'status' => 'custom',
            'subjects' => [],
            'elective_groups' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $template
     */
    private function supports(array $template, EducationLevel $educationLevel, ?Program $program): bool
    {
        $codes = array_map(strtoupper(...), $template['education_level_codes'] ?? []);
        $supportsLevel = ($codes !== [] && in_array(strtoupper($educationLevel->code), $codes, true))
            || (($template['category'] ?? null) === $educationLevel->category);

        if (! $supportsLevel) {
            return false;
        }

        $aliases = array_map(
            fn (string $alias): string => Str::upper(Str::squish($alias)),
            $template['program_aliases'] ?? [],
        );

        return $aliases === [] || ($program instanceof Program && collect([$program->code, $program->name])
            ->filter()
            ->map(fn (string $value): string => Str::upper(Str::squish($value)))
            ->intersect($aliases)
            ->isNotEmpty());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function matatagTemplates(): array
    {
        $subjectsByLevel = [
            'K' => ['Language, Literacy and Communication', 'Mathematics', 'Makabansa'],
            'G1' => ['Language', 'Reading and Literacy', 'Mathematics', 'Makabansa', 'Good Manners and Right Conduct'],
            'G2' => ['Filipino', 'English', 'Mathematics', 'Makabansa', 'Good Manners and Right Conduct'],
            'G3' => ['Filipino', 'English', 'Mathematics', 'Science', 'Makabansa', 'Good Manners and Right Conduct'],
            'G4' => ['Filipino', 'English', 'Mathematics', 'Science', 'Araling Panlipunan', 'Music and Arts', 'Physical Education and Health', 'Technology and Livelihood Education', 'Good Manners and Right Conduct'],
            'G5' => ['Filipino', 'English', 'Mathematics', 'Science', 'Araling Panlipunan', 'Music and Arts', 'Physical Education and Health', 'Technology and Livelihood Education', 'Good Manners and Right Conduct'],
            'G6' => ['Filipino', 'English', 'Mathematics', 'Science', 'Araling Panlipunan', 'Music and Arts', 'Physical Education and Health', 'Technology and Livelihood Education', 'Good Manners and Right Conduct'],
            'G7' => ['Filipino', 'English', 'Mathematics', 'Science', 'Araling Panlipunan', 'Music and Arts', 'Physical Education and Health', 'Technology and Livelihood Education', 'Values Education'],
            'G8' => ['Filipino', 'English', 'Mathematics', 'Science', 'Araling Panlipunan', 'Music and Arts', 'Physical Education and Health', 'Technology and Livelihood Education', 'Values Education'],
            'G9' => ['Filipino', 'English', 'Mathematics', 'Science', 'Araling Panlipunan', 'Music and Arts', 'Physical Education and Health', 'Technology and Livelihood Education', 'Values Education'],
            'G10' => ['Filipino', 'English', 'Mathematics', 'Science', 'Araling Panlipunan', 'Music and Arts', 'Physical Education and Health', 'Technology and Livelihood Education', 'Values Education'],
        ];

        return collect($subjectsByLevel)->mapWithKeys(function (array $subjects, string $levelCode): array {
            $key = 'deped-matatag-'.strtolower($levelCode).'-2024';

            return [$key => [
                'key' => $key,
                'name' => "MATATAG {$levelCode}",
                'version' => 'DO 010, s. 2024',
                'authority' => 'Department of Education',
                'order' => 'DepEd Order No. 10, s. 2024',
                'source_url' => 'https://www.deped.gov.ph/2024/07/23/july-23-2024-do-010-s-2024-policy-guidelines-on-the-implementation-of-the-matatag-curriculum/',
                'status' => 'official',
                'category' => in_array($levelCode, ['K', 'G1', 'G2', 'G3', 'G4', 'G5', 'G6'], true) ? 'grade_school' : 'high_school',
                'education_level_codes' => [$levelCode],
                'subjects' => collect($subjects)->values()->map(
                    fn (string $name, int $position): array => $this->subject(
                        code: 'MAT-'.$levelCode.'-'.Str::upper(Str::substr(Str::slug($name, ''), 0, 8)),
                        name: $name,
                        position: $position + 1,
                        contactHours: $levelCode === 'K' ? 5 : 4,
                    ),
                )->all(),
                'elective_groups' => [],
            ]];
        })->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function strengthenedSeniorHighTemplates(): array
    {
        return collect(['G11' => 1, 'G12' => 2])->mapWithKeys(function (int $year, string $levelCode): array {
            $key = 'deped-sshs-'.strtolower($levelCode).'-2026';
            $core = [
                ['Effective Communication', 1],
                ['Life and Career Skills', 1],
                ['General Mathematics', 1],
                ['General Science', 1],
                ['Pag-aaral ng Kasaysayan at Lipunang Pilipino', 2],
                ['Personal Development', 2],
            ];
            $electives = [
                ['Academic Elective: Applied Economics', 2],
                ['Academic Elective: Business Finance', 2],
                ['Academic Elective: Creative Writing', 2],
                ['Academic Elective: Programming', 2],
            ];

            return [$key => [
                'key' => $key,
                'name' => "Strengthened Senior High School {$levelCode}",
                'version' => 'SY 2026-2027',
                'authority' => 'Department of Education',
                'order' => 'Strengthened Senior High School Curriculum',
                'source_url' => 'https://www.deped.gov.ph/',
                'status' => 'official',
                'category' => 'high_school',
                'education_level_codes' => [$levelCode],
                'subjects' => [
                    ...collect($core)->map(fn (array $subject, int $position): array => $this->subject(
                        code: 'SSHS-'.$levelCode.'-CORE'.($position + 1),
                        name: $subject[0],
                        position: $position + 1,
                        year: $year,
                        term: $subject[1],
                        contactHours: 4,
                    ))->all(),
                    ...collect($electives)->map(fn (array $subject, int $position): array => $this->subject(
                        code: 'SSHS-'.$levelCode.'-ELEC'.($position + 1),
                        name: $subject[0],
                        position: count($core) + $position + 1,
                        year: $year,
                        term: $subject[1],
                        contactHours: 4,
                        required: false,
                        electiveGroup: 'ACADEMIC-ELECTIVES',
                    ))->all(),
                ],
                'elective_groups' => [[
                    'code' => 'ACADEMIC-ELECTIVES',
                    'name' => 'Academic Electives',
                    'minimum_subjects' => 1,
                    'maximum_subjects' => 2,
                    'minimum_units' => 0,
                    'maximum_units' => null,
                ]],
            ]];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function bsitTemplate(): array
    {
        $subjects = [
            ['IT101', 'Introduction to Computing', 1, 1, 3, 2, 3],
            ['IT102', 'Computer Programming 1', 1, 1, 3, 2, 3],
            ['GE-MATH', 'Mathematics in the Modern World', 1, 1, 3, 3, 0],
            ['IT103', 'Computer Programming 2', 1, 2, 3, 2, 3, ['IT102']],
            ['IT104', 'Discrete Mathematics', 1, 2, 3, 3, 0],
            ['GE-STS', 'Science, Technology and Society', 1, 2, 3, 3, 0],
            ['IT201', 'Data Structures and Algorithms', 2, 1, 3, 2, 3, ['IT103']],
            ['IT202', 'Information Management', 2, 1, 3, 2, 3, ['IT103']],
            ['IT203', 'Networking 1', 2, 2, 3, 2, 3],
            ['IT204', 'Platform Technologies', 2, 2, 3, 2, 3],
            ['IT301', 'Web Systems and Technologies', 3, 1, 3, 2, 3, ['IT201']],
            ['IT302', 'Systems Integration and Architecture', 3, 1, 3, 2, 3, ['IT202']],
            ['IT303', 'Information Assurance and Security 1', 3, 2, 3, 2, 3, ['IT203']],
            ['IT304', 'Human Computer Interaction', 3, 2, 3, 2, 3],
            ['IT401', 'Capstone Project 1', 4, 1, 3, 1, 6, ['IT301', 'IT302']],
            ['IT402', 'Capstone Project 2', 4, 2, 3, 1, 6, ['IT401']],
            ['IT-ELEC1', 'Professional Elective 1', 3, 1, 3, 2, 3, [], false, 'PROF-ELECTIVES'],
            ['IT-ELEC2', 'Professional Elective 2', 3, 2, 3, 2, 3, [], false, 'PROF-ELECTIVES'],
            ['IT-ELEC3', 'Professional Elective 3', 4, 1, 3, 2, 3, [], false, 'PROF-ELECTIVES'],
        ];

        return [
            'key' => 'ched-bsit-2015',
            'name' => 'Bachelor of Science in Information Technology',
            'version' => 'CMO 25, s. 2015 / CMO 13, s. 2021',
            'authority' => 'Commission on Higher Education',
            'order' => 'CHED Memorandum Order No. 25, s. 2015',
            'source_url' => 'https://legacy.ched.gov.ph/2015-ched-memorandum-orders/',
            'issuance_index_url' => 'https://legacy.ched.gov.ph/2025-ched-memorandum-orders/',
            'verified_through' => '2025 CHED Memorandum Orders',
            'status' => 'official',
            'category' => 'college',
            'education_level_codes' => ['UG', 'COL'],
            'program_aliases' => ['BSIT', 'BS Information Technology', 'Bachelor of Science in Information Technology'],
            'subjects' => collect($subjects)->map(fn (array $subject, int $position): array => $this->subject(
                code: $subject[0],
                name: $subject[1],
                position: $position + 1,
                year: $subject[2],
                term: $subject[3],
                units: $subject[4],
                contactHours: $subject[5],
                labHours: $subject[6],
                prerequisites: Arr::get($subject, 7, []),
                required: Arr::get($subject, 8, true),
                electiveGroup: Arr::get($subject, 9),
            ))->all(),
            'elective_groups' => [[
                'code' => 'PROF-ELECTIVES',
                'name' => 'Professional Electives',
                'minimum_subjects' => 2,
                'maximum_subjects' => 3,
                'minimum_units' => 6,
                'maximum_units' => 9,
            ]],
        ];
    }

    /**
     * @param  string[]  $prerequisites
     * @return array<string, mixed>
     */
    private function subject(
        string $code,
        string $name,
        int $position,
        int $year = 1,
        int $term = 1,
        float $units = 0,
        float $contactHours = 0,
        float $labHours = 0,
        array $prerequisites = [],
        bool $required = true,
        ?string $electiveGroup = null,
    ): array {
        return [
            'code' => $code,
            'name' => $name,
            'description' => null,
            'subject_type' => $labHours > 0 ? 'laboratory' : 'academic',
            'year_level' => $year,
            'term_sequence' => $term,
            'position' => $position,
            'credit_units' => $units,
            'contact_hours' => $contactHours,
            'lab_hours' => $labHours,
            'competency_hours' => 0,
            'is_required' => $required,
            'elective_group' => $electiveGroup,
            'prerequisites' => $prerequisites,
        ];
    }
}
