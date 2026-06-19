<?php

declare(strict_types=1);

namespace Modules\Enrollment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
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
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date'],
            'sex' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive', 'graduated', 'transferred'])],
            'student_number' => ['nullable', 'string', 'max:100'],
            'metadata' => ['nullable', 'array'],
            'metadata.learner_reference_number' => ['nullable', 'string', 'max:100'],
            'metadata.previous_school' => ['nullable', 'string', 'max:255'],
            'metadata.emergency_contact' => ['nullable', 'string', 'max:255'],
            'guardians' => ['nullable', 'array'],
            'guardians.*.id' => ['nullable', 'integer', 'exists:people,id'],
            'guardians.*.first_name' => ['required_without:guardians.*.id', 'nullable', 'string', 'max:255'],
            'guardians.*.last_name' => ['required_without:guardians.*.id', 'nullable', 'string', 'max:255'],
            'guardians.*.email' => ['nullable', 'email', 'max:255'],
            'guardians.*.phone' => ['nullable', 'string', 'max:50'],
            'guardians.*.relationship' => ['required_with:guardians', 'string', 'max:100'],
            'guardians.*.is_primary' => ['boolean'],
            'guardians.*.has_portal_access' => ['boolean'],
        ];
    }
}
