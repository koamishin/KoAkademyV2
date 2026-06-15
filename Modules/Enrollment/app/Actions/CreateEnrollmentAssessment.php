<?php

declare(strict_types=1);

namespace Modules\Enrollment\Actions;

use App\Models\Curriculum;
use App\Models\CurriculumItem;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Enrollment\Models\Enrollment;
use Modules\Enrollment\Models\EnrollmentAssessment;

final class CreateEnrollmentAssessment
{
    /**
     * @param  Collection<int, CurriculumItem>  $items
     */
    public function execute(Enrollment $enrollment, Curriculum $curriculum, Collection $items): EnrollmentAssessment
    {
        if ($curriculum->tuition_per_unit === null || $curriculum->laboratory_fee_per_subject === null) {
            throw ValidationException::withMessages([
                'curriculum' => 'Configure the curriculum tuition and laboratory rates before enrollment.',
            ]);
        }

        $tuitionRate = (float) $curriculum->tuition_per_unit;
        $laboratoryRate = (float) $curriculum->laboratory_fee_per_subject;
        $tuitionTotal = $items->sum(fn (CurriculumItem $item): float => (float) $item->credit_units * $tuitionRate);
        $laboratoryItems = $items->filter(fn (CurriculumItem $item): bool => (float) $item->lab_hours > 0);
        $laboratoryTotal = $laboratoryItems->count() * $laboratoryRate;
        $miscellaneousFees = $curriculum->miscellaneousFees()->where('is_active', true)->orderBy('position')->get();
        $miscellaneousTotal = (float) $miscellaneousFees->sum('amount');

        $assessment = EnrollmentAssessment::query()->create([
            'enrollment_id' => $enrollment->getKey(),
            'currency' => 'PHP',
            'tuition_total' => $tuitionTotal,
            'laboratory_total' => $laboratoryTotal,
            'miscellaneous_total' => $miscellaneousTotal,
            'total' => $tuitionTotal + $laboratoryTotal + $miscellaneousTotal,
            'assessed_at' => now(),
        ]);

        foreach ($items as $item) {
            $assessment->lines()->create([
                'curriculum_item_id' => $item->getKey(),
                'type' => 'tuition',
                'code' => $item->subject->code,
                'description' => "{$item->subject->name} tuition",
                'quantity' => $item->credit_units,
                'unit_amount' => $tuitionRate,
                'amount' => (float) $item->credit_units * $tuitionRate,
                'metadata' => [
                    'subject_name' => $item->subject->name,
                    'subject_code' => $item->subject->code,
                    'credit_units' => (float) $item->credit_units,
                ],
            ]);
        }

        foreach ($laboratoryItems as $item) {
            $assessment->lines()->create([
                'curriculum_item_id' => $item->getKey(),
                'type' => 'laboratory',
                'code' => "{$item->subject->code}-LAB",
                'description' => "{$item->subject->name} laboratory fee",
                'quantity' => 1,
                'unit_amount' => $laboratoryRate,
                'amount' => $laboratoryRate,
                'metadata' => [
                    'subject_name' => $item->subject->name,
                    'subject_code' => $item->subject->code,
                    'lab_hours' => (float) $item->lab_hours,
                ],
            ]);
        }

        foreach ($miscellaneousFees as $fee) {
            $assessment->lines()->create([
                'curriculum_miscellaneous_fee_id' => $fee->getKey(),
                'type' => 'miscellaneous',
                'code' => $fee->code,
                'description' => $fee->name,
                'quantity' => 1,
                'unit_amount' => $fee->amount,
                'amount' => $fee->amount,
                'metadata' => ['description' => $fee->description],
            ]);
        }

        return $assessment->load('lines');
    }
}
