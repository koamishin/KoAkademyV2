<?php

declare(strict_types=1);

namespace Modules\Enrollment\Models;

use App\Models\Campus;
use App\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StudentProfile extends Model
{
    protected $fillable = [
        'person_id',
        'campus_id',
        'psa_birth_certificate_number',
        'learner_reference_number',
        'nationality',
        'civil_status',
        'religion',
        'mother_tongue',
        'is_indigenous_people',
        'indigenous_community',
        'has_disability',
        'disability_type',
        'is_4ps_beneficiary',
        'four_ps_household_id',
        'annual_family_income_bracket',
        'household_gross_income',
        'has_government_subsidy',
        'subsidy_program',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'current_address',
        'permanent_address',
        'previous_school_name',
        'previous_school_address',
        'previous_school_type',
        'last_grade_level_completed',
        'last_school_year_attended',
        'senior_high_school_strand',
        'college_year_level',
        'reporting_flags',
    ];

    protected $attributes = [
        'is_indigenous_people' => false,
        'has_disability' => false,
        'is_4ps_beneficiary' => false,
        'has_government_subsidy' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_indigenous_people' => 'boolean',
            'has_disability' => 'boolean',
            'is_4ps_beneficiary' => 'boolean',
            'has_government_subsidy' => 'boolean',
            'household_gross_income' => 'decimal:2',
            'current_address' => 'array',
            'permanent_address' => 'array',
            'reporting_flags' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }
}
