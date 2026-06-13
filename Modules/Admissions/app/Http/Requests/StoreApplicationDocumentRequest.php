<?php

declare(strict_types=1);

namespace Modules\Admissions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreApplicationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->person?->id === $this->route('application')?->person_id;
    }

    public function rules(): array
    {
        return ['requirement_key' => ['required', 'string', 'max:100'], 'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:10240']];
    }
}
