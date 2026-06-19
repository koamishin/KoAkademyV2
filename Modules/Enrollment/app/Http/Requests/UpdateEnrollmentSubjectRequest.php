<?php

declare(strict_types=1);

namespace Modules\Enrollment\Http\Requests;

use App\Models\Campus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Classroom\Models\ClassOffering;

final class UpdateEnrollmentSubjectRequest extends FormRequest
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
            'class_offering_id' => [
                'nullable',
                Rule::exists(ClassOffering::class, 'id')
                    ->where('campus_id', $campus?->getKey()),
            ],
            'status' => ['required', Rule::in(['enrolled', 'dropped', 'completed', 'withdrawn'])],
            'final_result' => ['nullable', 'string', 'max:50'],
        ];
    }
}
