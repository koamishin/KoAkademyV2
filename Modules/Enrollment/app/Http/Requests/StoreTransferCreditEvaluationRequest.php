<?php

declare(strict_types=1);

namespace Modules\Enrollment\Http\Requests;

use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\CurriculumItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Enrollment\Models\TransferCreditEvaluation;
use Modules\Enrollment\Models\TransferCreditSubject;

final class StoreTransferCreditEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Campus|null $campus */
        $campus = $this->route('campus');

        return [
            'curriculum_id' => [
                'required',
                Rule::exists(Curriculum::class, 'id')
                    ->where(fn ($query) => $query->whereIn(
                        'program_id',
                        $campus?->programs()->select('id') ?? [],
                    )),
            ],
            'source_school_name' => ['required', 'string', 'max:255'],
            'source_school_address' => ['nullable', 'string', 'max:255'],
            'previous_program' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(TransferCreditEvaluation::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*.curriculum_item_id' => ['nullable', Rule::exists(CurriculumItem::class, 'id')],
            'subjects.*.previous_subject_code' => ['nullable', 'string', 'max:100'],
            'subjects.*.previous_subject_name' => ['required', 'string', 'max:255'],
            'subjects.*.previous_units' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'subjects.*.previous_grade' => ['nullable', 'string', 'max:50'],
            'subjects.*.school_year' => ['nullable', 'string', 'max:50'],
            'subjects.*.term' => ['nullable', 'string', 'max:50'],
            'subjects.*.status' => ['required', Rule::in(TransferCreditSubject::STATUSES)],
            'subjects.*.credited_units' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'subjects.*.remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
