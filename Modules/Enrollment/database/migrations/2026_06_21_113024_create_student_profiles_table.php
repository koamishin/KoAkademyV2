<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->unique()->constrained('people')->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('psa_birth_certificate_number')->nullable();
            $table->string('learner_reference_number')->nullable()->index();
            $table->string('nationality')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('religion')->nullable();
            $table->string('mother_tongue')->nullable();
            $table->boolean('is_indigenous_people')->default(false)->index();
            $table->string('indigenous_community')->nullable();
            $table->boolean('has_disability')->default(false)->index();
            $table->string('disability_type')->nullable();
            $table->boolean('is_4ps_beneficiary')->default(false)->index();
            $table->string('four_ps_household_id')->nullable();
            $table->string('annual_family_income_bracket')->nullable()->index();
            $table->decimal('household_gross_income', 12, 2)->nullable();
            $table->boolean('has_government_subsidy')->default(false)->index();
            $table->string('subsidy_program')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->json('current_address')->nullable();
            $table->json('permanent_address')->nullable();
            $table->string('previous_school_name')->nullable()->index();
            $table->string('previous_school_address')->nullable();
            $table->string('previous_school_type')->nullable();
            $table->string('last_grade_level_completed')->nullable();
            $table->string('last_school_year_attended')->nullable();
            $table->string('senior_high_school_strand')->nullable();
            $table->unsignedTinyInteger('college_year_level')->nullable()->index();
            $table->json('reporting_flags')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
