<?php

declare(strict_types=1);

namespace Modules\Enrollment\Http\Requests;

use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Enrollment\Enums\EnrollmentClassification;
use Modules\Enrollment\Models\EnrollmentPeriod;

final class StoreStudentEnrollmentRequest extends FormRequest
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
            'enrollment_period_id' => [
                'required',
                Rule::exists(EnrollmentPeriod::class, 'id')
                    ->where('campus_id', $campus?->getKey()),
            ],
            'curriculum_id' => [
                'required',
                Rule::exists(Curriculum::class, 'id')
                    ->where(fn ($query) => $query->whereIn(
                        'program_id',
                        $campus?->programs()->select('id') ?? [],
                    )),
            ],
            'classification' => ['required', Rule::enum(EnrollmentClassification::class)],
            'section_id' => [
                'nullable',
                Rule::exists(Section::class, 'id')
                    ->where('campus_id', $campus?->getKey()),
            ],
            'year_level' => ['nullable', 'integer', 'min:1', 'max:20'],
            'selected_elective_item_ids' => ['nullable', 'array'],
            'selected_elective_item_ids.*' => ['integer', 'exists:curriculum_items,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
