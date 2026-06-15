<?php

declare(strict_types=1);

namespace Modules\Admissions\Http\Requests;

use App\Support\CurrentCampus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $campusId = app(CurrentCampus::class)->id();

        return [
            'admission_period_id' => [
                'required',
                'integer',
                Rule::exists('admission_periods', 'id')->where('campus_id', $campusId),
            ],
            'program_id' => [
                'nullable',
                'integer',
                Rule::exists('programs', 'id')->where('campus_id', $campusId),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'answers' => ['nullable', 'array'],
        ];
    }
}
