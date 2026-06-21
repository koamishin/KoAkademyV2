<?php

declare(strict_types=1);

namespace Modules\Enrollment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Enrollment\Models\StudentDocument;

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
            'profile' => ['nullable', 'array'],
            'profile.psa_birth_certificate_number' => ['nullable', 'string', 'max:100'],
            'profile.learner_reference_number' => ['nullable', 'string', 'max:100'],
            'profile.nationality' => ['nullable', 'string', 'max:100'],
            'profile.civil_status' => ['nullable', 'string', 'max:50'],
            'profile.religion' => ['nullable', 'string', 'max:100'],
            'profile.mother_tongue' => ['nullable', 'string', 'max:100'],
            'profile.is_indigenous_people' => ['boolean'],
            'profile.indigenous_community' => ['nullable', 'string', 'max:255'],
            'profile.has_disability' => ['boolean'],
            'profile.disability_type' => ['nullable', 'string', 'max:255'],
            'profile.is_4ps_beneficiary' => ['boolean'],
            'profile.four_ps_household_id' => ['nullable', 'string', 'max:100'],
            'profile.annual_family_income_bracket' => ['nullable', 'string', 'max:100'],
            'profile.household_gross_income' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'profile.has_government_subsidy' => ['boolean'],
            'profile.subsidy_program' => ['nullable', 'string', 'max:255'],
            'profile.emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'profile.emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'profile.emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'profile.current_address' => ['nullable', 'array'],
            'profile.current_address.house' => ['nullable', 'string', 'max:255'],
            'profile.current_address.barangay' => ['nullable', 'string', 'max:255'],
            'profile.current_address.city' => ['nullable', 'string', 'max:255'],
            'profile.current_address.province' => ['nullable', 'string', 'max:255'],
            'profile.current_address.country' => ['nullable', 'string', 'max:100'],
            'profile.current_address.zip_code' => ['nullable', 'string', 'max:20'],
            'profile.permanent_address' => ['nullable', 'array'],
            'profile.permanent_address.house' => ['nullable', 'string', 'max:255'],
            'profile.permanent_address.barangay' => ['nullable', 'string', 'max:255'],
            'profile.permanent_address.city' => ['nullable', 'string', 'max:255'],
            'profile.permanent_address.province' => ['nullable', 'string', 'max:255'],
            'profile.permanent_address.country' => ['nullable', 'string', 'max:100'],
            'profile.permanent_address.zip_code' => ['nullable', 'string', 'max:20'],
            'profile.previous_school_name' => ['nullable', 'string', 'max:255'],
            'profile.previous_school_address' => ['nullable', 'string', 'max:255'],
            'profile.previous_school_type' => ['nullable', 'string', 'max:100'],
            'profile.last_grade_level_completed' => ['nullable', 'string', 'max:100'],
            'profile.last_school_year_attended' => ['nullable', 'string', 'max:50'],
            'profile.senior_high_school_strand' => ['nullable', 'string', 'max:100'],
            'profile.college_year_level' => ['nullable', 'integer', 'min:1', 'max:20'],
            'profile.reporting_flags' => ['nullable', 'array'],
            'guardians' => ['nullable', 'array'],
            'guardians.*.id' => ['nullable', 'integer', 'exists:people,id'],
            'guardians.*.first_name' => ['required_without:guardians.*.id', 'nullable', 'string', 'max:255'],
            'guardians.*.last_name' => ['required_without:guardians.*.id', 'nullable', 'string', 'max:255'],
            'guardians.*.email' => ['nullable', 'email', 'max:255'],
            'guardians.*.phone' => ['nullable', 'string', 'max:50'],
            'guardians.*.relationship' => ['required_with:guardians', 'string', 'max:100'],
            'guardians.*.is_primary' => ['boolean'],
            'guardians.*.has_portal_access' => ['boolean'],
            'documents' => ['nullable', 'array'],
            'documents.*.document_type' => ['required_with:documents', Rule::in(array_keys(StudentDocument::TYPES))],
            'documents.*.file' => ['required_with:documents', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'documents.*.issued_on' => ['nullable', 'date'],
            'documents.*.expires_on' => ['nullable', 'date'],
            'documents.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
