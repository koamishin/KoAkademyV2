<?php

declare(strict_types=1);

namespace App\Actions\Academic;

use App\Academic\CurriculumTemplateRegistry;
use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\CurriculumItem;
use App\Models\EducationLevel;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class CreateCurriculumFromBuilder
{
    public function __construct(private CurriculumTemplateRegistry $curriculumTemplateRegistry) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Campus $campus, array $data): Curriculum
    {
        $data += [
            'tuition_per_unit' => 375,
            'laboratory_fee_per_subject' => 2000,
            'miscellaneous_fees' => [],
        ];

        $validated = Validator::make($data, [
            'education_level_id' => ['required', 'integer', 'exists:education_levels,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'program_name' => ['required_without:program_id', 'nullable', 'string', 'max:255'],
            'program_code' => ['required_without:program_id', 'nullable', 'string', 'max:50'],
            'award_type' => ['nullable', 'string', 'max:100'],
            'template_key' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'effective_year' => ['required', 'integer', 'min:1900', 'max:2200'],
            'status' => ['required', 'in:draft,active,inactive,archived'],
            'tuition_per_unit' => ['required', 'numeric', 'min:0'],
            'laboratory_fee_per_subject' => ['required', 'numeric', 'min:0'],
            'miscellaneous_fees' => ['nullable', 'array'],
            'miscellaneous_fees.*.code' => ['required', 'string', 'max:50', 'distinct:strict'],
            'miscellaneous_fees.*.name' => ['required', 'string', 'max:255'],
            'miscellaneous_fees.*.description' => ['nullable', 'string'],
            'miscellaneous_fees.*.amount' => ['required', 'numeric', 'min:0'],
            'miscellaneous_fees.*.is_active' => ['required', 'boolean'],
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*.code' => ['required', 'string', 'max:50', 'distinct:strict'],
            'subjects.*.name' => ['required', 'string', 'max:255'],
            'subjects.*.subject_type' => ['required', 'in:academic,laboratory,competency'],
            'subjects.*.year_level' => ['nullable', 'integer', 'min:0', 'max:20'],
            'subjects.*.term_sequence' => ['nullable', 'integer', 'min:1', 'max:12'],
            'subjects.*.credit_units' => ['required', 'numeric', 'min:0'],
            'subjects.*.contact_hours' => ['required', 'numeric', 'min:0'],
            'subjects.*.lab_hours' => ['required', 'numeric', 'min:0'],
            'subjects.*.competency_hours' => ['required', 'numeric', 'min:0'],
            'subjects.*.is_required' => ['required', 'boolean'],
            'subjects.*.elective_group' => ['nullable', 'string', 'max:50'],
            'subjects.*.prerequisites' => ['nullable', 'array'],
            'elective_groups' => ['nullable', 'array'],
        ])->validate();

        return DB::transaction(function () use ($campus, $validated): Curriculum {
            $educationLevel = EducationLevel::query()
                ->whereKey($validated['education_level_id'])
                ->where('institution_id', $campus->institution_id)
                ->firstOrFail();
            $program = $this->resolveProgram($campus, $educationLevel, $validated);
            $template = $validated['template_key'] === 'blank'
                ? $this->curriculumTemplateRegistry->blank()
                : $this->curriculumTemplateRegistry->find($validated['template_key']);

            if ($template === null) {
                throw ValidationException::withMessages(['template_key' => 'The selected curriculum template is unavailable.']);
            }

            if (! array_key_exists($validated['template_key'], $this->curriculumTemplateRegistry->optionsFor($educationLevel, $program))) {
                throw ValidationException::withMessages([
                    'template_key' => 'The selected template does not support this education level and program.',
                ]);
            }

            if (Curriculum::query()
                ->where('program_id', $program->getKey())
                ->where('code', Str::upper($validated['code']))
                ->exists()) {
                throw ValidationException::withMessages([
                    'code' => 'This curriculum code is already used by the selected program.',
                ]);
            }

            $curriculum = Curriculum::query()->create([
                'program_id' => $program->id,
                'name' => $validated['name'],
                'code' => Str::upper($validated['code']),
                'effective_year' => $validated['effective_year'],
                'template_key' => $template['key'] === 'blank' ? null : $template['key'],
                'template_version' => $template['version'],
                'template_authority' => $template['authority'],
                'template_source_url' => $template['source_url'],
                'is_customized' => $template['key'] === 'blank',
                'currency' => 'PHP',
                'tuition_per_unit' => $validated['tuition_per_unit'],
                'laboratory_fee_per_subject' => $validated['laboratory_fee_per_subject'],
                'status' => $validated['status'],
            ]);

            foreach ($validated['miscellaneous_fees'] ?? [] as $position => $fee) {
                $curriculum->miscellaneousFees()->create([
                    'code' => Str::upper($fee['code']),
                    'name' => $fee['name'],
                    'description' => $fee['description'] ?? null,
                    'amount' => $fee['amount'],
                    'is_active' => $fee['is_active'],
                    'position' => $position + 1,
                ]);
            }

            $groups = collect($validated['elective_groups'] ?? [])
                ->mapWithKeys(function (array $group) use ($curriculum): array {
                    $model = $curriculum->electiveGroups()->create([
                        'code' => Str::upper($group['code']),
                        'name' => $group['name'],
                        'minimum_subjects' => $group['minimum_subjects'] ?? 0,
                        'maximum_subjects' => $group['maximum_subjects'] ?? null,
                        'minimum_units' => $group['minimum_units'] ?? 0,
                        'maximum_units' => $group['maximum_units'] ?? null,
                    ]);

                    return [$model->code => $model];
                });

            $subjectsByCode = collect();

            foreach ($validated['subjects'] as $position => $subjectData) {
                $subject = $this->resolveSubject($campus, $subjectData);
                $subjectsByCode->put($subject->code, $subject);
                $groupCode = filled($subjectData['elective_group'] ?? null)
                    ? Str::upper($subjectData['elective_group'])
                    : null;

                CurriculumItem::query()->create([
                    'curriculum_id' => $curriculum->id,
                    'subject_id' => $subject->id,
                    'year_level' => $subjectData['year_level'] ?? null,
                    'term_sequence' => $subjectData['term_sequence'] ?? null,
                    'position' => $subjectData['position'] ?? $position + 1,
                    'elective_group' => $groupCode,
                    'elective_group_id' => $groupCode ? $groups->get($groupCode)?->id : null,
                    'credit_units' => $subjectData['credit_units'],
                    'contact_hours' => $subjectData['contact_hours'],
                    'lab_hours' => $subjectData['lab_hours'],
                    'competency_hours' => $subjectData['competency_hours'],
                    'is_required' => $subjectData['is_required'],
                ]);
            }

            foreach ($validated['subjects'] as $subjectData) {
                $subject = $subjectsByCode->get(Str::upper($subjectData['code']));

                foreach ($subjectData['prerequisites'] ?? [] as $prerequisiteCode) {
                    $normalizedPrerequisiteCode = Str::upper($prerequisiteCode);

                    if (! $subjectsByCode->has($normalizedPrerequisiteCode)) {
                        throw ValidationException::withMessages([
                            'subjects' => "Prerequisite {$prerequisiteCode} is not part of this curriculum.",
                        ]);
                    }

                    $prerequisite = $subjectsByCode->get($normalizedPrerequisiteCode);

                    DB::table('subject_prerequisites')->updateOrInsert(
                        ['subject_id' => $subject->id, 'prerequisite_subject_id' => $prerequisite->id],
                        ['requirement' => 'completed', 'created_at' => now(), 'updated_at' => now()],
                    );
                }
            }

            return $curriculum->load([
                'program.educationLevel',
                'items.subject',
                'electiveGroups',
                'miscellaneousFees',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveProgram(Campus $campus, EducationLevel $educationLevel, array $data): Program
    {
        if (filled($data['program_id'] ?? null)) {
            return Program::query()
                ->whereKey($data['program_id'])
                ->where('campus_id', $campus->id)
                ->where('education_level_id', $educationLevel->id)
                ->firstOrFail();
        }

        return Program::query()->create([
            'campus_id' => $campus->id,
            'education_level_id' => $educationLevel->id,
            'name' => $data['program_name'],
            'code' => Str::upper($data['program_code']),
            'award_type' => $data['award_type'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveSubject(Campus $campus, array $data): Subject
    {
        $code = Str::upper($data['code']);
        $subject = Subject::query()
            ->where('institution_id', $campus->institution_id)
            ->where('code', $code)
            ->first();

        if ($subject !== null) {
            if (Str::lower($subject->name) !== Str::lower($data['name'])
                || $subject->subject_type !== $data['subject_type']) {
                throw ValidationException::withMessages([
                    'subjects' => "Subject code {$code} already exists with different details.",
                ]);
            }

            return $subject;
        }

        return Subject::query()->create([
            'institution_id' => $campus->institution_id,
            'code' => $code,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'subject_type' => $data['subject_type'],
            'default_credit_units' => $data['credit_units'],
            'default_contact_hours' => $data['contact_hours'],
            'default_lab_hours' => $data['lab_hours'],
            'default_competency_hours' => $data['competency_hours'],
            'status' => 'active',
        ]);
    }
}
