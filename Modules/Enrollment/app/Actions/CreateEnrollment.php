<?php

declare(strict_types=1);

namespace Modules\Enrollment\Actions;

use App\Models\Curriculum;
use App\Models\CurriculumItem;
use App\Models\Person;
use App\Models\Section;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Enrollment\Enums\EnrollmentClassification;
use Modules\Enrollment\Enums\EnrollmentStatus;
use Modules\Enrollment\Models\Enrollment;
use Modules\Enrollment\Models\EnrollmentPeriod;

final class CreateEnrollment
{
    public function __construct(
        private readonly CreateEnrollmentAssessment $createEnrollmentAssessment,
    ) {}

    /**
     * @param  int[]  $selectedElectiveItemIds
     */
    public function execute(
        Person $student,
        EnrollmentPeriod $period,
        Curriculum $curriculum,
        EnrollmentClassification $classification,
        ?int $sectionId = null,
        array $selectedElectiveItemIds = [],
        ?int $yearLevel = null,
    ): Enrollment {
        if (Enrollment::query()->where('student_id', $student->id)->where('enrollment_period_id', $period->id)->exists()) {
            throw ValidationException::withMessages(['student' => 'This student already has an enrollment for the selected period.']);
        }

        $section = $sectionId === null
            ? null
            : Section::query()
                ->whereKey($sectionId)
                ->where('campus_id', $period->campus_id)
                ->where('program_id', $curriculum->program_id)
                ->where('term_id', $period->term_id)
                ->firstOrFail();
        $resolvedYearLevel = $section?->year_level ?? $yearLevel ?? 1;
        $termSequence = $period->term()->value('sequence');
        $items = $curriculum->items()
            ->with(['electiveGroup', 'subject'])
            ->where(fn ($query) => $query
                ->whereNull('year_level')
                ->orWhere('year_level', $resolvedYearLevel))
            ->where(fn ($query) => $query
                ->whereNull('term_sequence')
                ->orWhere('term_sequence', $termSequence))
            ->get();
        $selectedItems = $this->validateElectiveSelections($items, $selectedElectiveItemIds);

        return DB::transaction(function () use ($student, $period, $curriculum, $classification, $sectionId, $items, $selectedItems): Enrollment {
            $enrollment = Enrollment::query()->create([
                'student_id' => $student->id,
                'campus_id' => $period->campus_id,
                'enrollment_period_id' => $period->id,
                'curriculum_id' => $curriculum->id,
                'section_id' => $sectionId,
                'classification' => $classification,
                'status' => EnrollmentStatus::Pending,
            ]);
            $assessedItems = $items
                ->where('is_required', true)
                ->merge($selectedItems)
                ->unique('id');
            $enrollment->subjects()->createMany(
                $assessedItems
                    ->map(fn (CurriculumItem $item): array => ['curriculum_item_id' => $item->id])
                    ->values()
                    ->all(),
            );
            $this->createEnrollmentAssessment->execute($enrollment, $curriculum, $assessedItems);

            return $enrollment->load(['subjects', 'assessment.lines']);
        });
    }

    /**
     * @param  Collection<int, CurriculumItem>  $items
     * @param  int[]  $selectedElectiveItemIds
     * @return Collection<int, CurriculumItem>
     */
    private function validateElectiveSelections(Collection $items, array $selectedElectiveItemIds): Collection
    {
        $selectedIds = collect($selectedElectiveItemIds)->map(fn (mixed $id): int => (int) $id)->unique();
        $selectedItems = $items->whereIn('id', $selectedIds)->where('is_required', false);

        if ($selectedItems->count() !== $selectedIds->count()) {
            throw ValidationException::withMessages([
                'electives' => 'One or more selected electives do not belong to this curriculum year and term.',
            ]);
        }

        foreach ($items->pluck('electiveGroup')->filter()->unique('id') as $group) {
            $availableGroupItems = $items->where('elective_group_id', $group->id);
            $groupItems = $selectedItems->where('elective_group_id', $group->id);
            $subjectCount = $groupItems->count();
            $unitCount = (float) $groupItems->sum('credit_units');
            $minimumSubjects = min($group->minimum_subjects, $availableGroupItems->count());
            $minimumUnits = min((float) $group->minimum_units, (float) $availableGroupItems->sum('credit_units'));

            if ($subjectCount < $minimumSubjects
                || ($group->maximum_subjects !== null && $subjectCount > $group->maximum_subjects)
                || $unitCount < $minimumUnits
                || ($group->maximum_units !== null && $unitCount > (float) $group->maximum_units)) {
                throw ValidationException::withMessages([
                    'electives' => "The selected subjects do not satisfy the {$group->name} requirements.",
                ]);
            }
        }

        return $selectedItems;
    }
}
