<?php

declare(strict_types=1);

namespace Modules\Enrollment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Enrollment\Models\StudentDocument;

final class UpdateStudentDocumentRequest extends FormRequest
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
            'status' => ['required', Rule::in(StudentDocument::STATUSES)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
