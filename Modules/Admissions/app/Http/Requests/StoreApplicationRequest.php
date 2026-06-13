<?php

declare(strict_types=1);

namespace Modules\Admissions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'admission_period_id' => ['required', 'integer', 'exists:admission_periods,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'answers' => ['nullable', 'array'],
        ];
    }
}
