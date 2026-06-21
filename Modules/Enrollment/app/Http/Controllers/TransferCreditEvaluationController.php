<?php

declare(strict_types=1);

namespace Modules\Enrollment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\CurriculumItem;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Enrollment\Http\Requests\StoreTransferCreditEvaluationRequest;
use Modules\Enrollment\Models\TransferCreditEvaluation;
use Modules\Enrollment\Support\AdminStudentAuthorizer;

final class TransferCreditEvaluationController extends Controller
{
    public function store(
        StoreTransferCreditEvaluationRequest $request,
        Campus $campus,
        Person $student,
        AdminStudentAuthorizer $authorizer,
    ): RedirectResponse {
        $authorizer->abortUnlessCanManage($request->user(), $campus);
        $authorizer->abortUnlessStudentBelongsToCampus($student, $campus);

        $validated = $request->validated();
        $this->validateCurriculumItems($validated);

        DB::transaction(function () use ($request, $campus, $student, $validated): void {
            $evaluation = TransferCreditEvaluation::query()->create([
                'campus_id' => $campus->id,
                'student_id' => $student->id,
                'curriculum_id' => $validated['curriculum_id'],
                'evaluator_id' => $validated['status'] === 'draft' ? null : $request->user()->id,
                'source_school_name' => $validated['source_school_name'],
                'source_school_address' => $validated['source_school_address'] ?? null,
                'previous_program' => $validated['previous_program'] ?? null,
                'status' => $validated['status'],
                'evaluated_at' => $validated['status'] === 'draft' ? null : now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $evaluation->subjects()->createMany(
                collect($validated['subjects'])
                    ->map(fn (array $subject): array => [
                        'curriculum_item_id' => $subject['curriculum_item_id'] ?? null,
                        'previous_subject_code' => $subject['previous_subject_code'] ?? null,
                        'previous_subject_name' => $subject['previous_subject_name'],
                        'previous_units' => $subject['previous_units'] ?? null,
                        'previous_grade' => $subject['previous_grade'] ?? null,
                        'school_year' => $subject['school_year'] ?? null,
                        'term' => $subject['term'] ?? null,
                        'status' => $subject['status'],
                        'credited_units' => $subject['credited_units'] ?? null,
                        'remarks' => $subject['remarks'] ?? null,
                    ])
                    ->all(),
            );
        });

        return back()->with('status', 'Transfer credit evaluation saved.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validateCurriculumItems(array $validated): void
    {
        $mappedItemIds = collect($validated['subjects'])
            ->pluck('curriculum_item_id')
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($mappedItemIds->isEmpty()) {
            return;
        }

        $validCount = CurriculumItem::query()
            ->where('curriculum_id', $validated['curriculum_id'])
            ->whereIn('id', $mappedItemIds)
            ->count();

        if ($validCount !== $mappedItemIds->count()) {
            throw ValidationException::withMessages([
                'subjects' => 'One or more credited subjects do not belong to the selected curriculum.',
            ]);
        }
    }
}
