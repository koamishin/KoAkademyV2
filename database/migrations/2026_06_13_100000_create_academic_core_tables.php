<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('timezone')->default('Asia/Manila');
            $table->string('locale')->default('en');
            $table->string('status')->default('active')->index();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('campuses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('address')->nullable();
            $table->string('timezone')->default('Asia/Manila');
            $table->string('status')->default('active')->index();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['institution_id', 'code']);
        });

        Schema::create('people', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('sex')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('person_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->index();
            $table->string('reference_number')->nullable()->index();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['person_id', 'campus_id', 'role']);
        });

        Schema::create('guardian_student', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guardian_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('people')->cascadeOnDelete();
            $table->string('relationship');
            $table->boolean('is_primary')->default(false);
            $table->boolean('has_portal_access')->default(true);
            $table->timestamps();
            $table->unique(['guardian_id', 'student_id']);
        });

        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('draft')->index();
            $table->boolean('is_current')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('terms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedSmallInteger('sequence');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('draft')->index();
            $table->timestamps();
            $table->unique(['academic_year_id', 'code']);
        });

        Schema::create('grading_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sequence');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestamps();
        });

        Schema::create('education_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('category')->index();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->json('features')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->unique(['institution_id', 'code']);
        });

        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('education_level_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('award_type')->nullable();
            $table->string('status')->default('active')->index();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['campus_id', 'code']);
        });

        Schema::create('curricula', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedSmallInteger('effective_year');
            $table->string('status')->default('draft')->index();
            $table->timestamps();
            $table->unique(['program_id', 'code']);
        });

        Schema::create('subjects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->string('subject_type')->default('academic')->index();
            $table->decimal('default_credit_units', 6, 2)->default(0);
            $table->decimal('default_contact_hours', 6, 2)->default(0);
            $table->decimal('default_lab_hours', 6, 2)->default(0);
            $table->decimal('default_competency_hours', 8, 2)->default(0);
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->unique(['institution_id', 'code']);
        });

        Schema::create('curriculum_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('curriculum_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('year_level')->nullable();
            $table->unsignedSmallInteger('term_sequence')->nullable();
            $table->string('elective_group')->nullable();
            $table->decimal('credit_units', 6, 2)->default(0);
            $table->decimal('contact_hours', 6, 2)->default(0);
            $table->decimal('lab_hours', 6, 2)->default(0);
            $table->decimal('competency_hours', 8, 2)->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            $table->unique(['curriculum_id', 'subject_id']);
        });

        Schema::create('subject_prerequisites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prerequisite_subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('requirement')->default('completed');
            $table->timestamps();
            $table->unique(['subject_id', 'prerequisite_subject_id']);
        });

        Schema::create('rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedInteger('capacity')->nullable();
            $table->string('room_type')->default('classroom');
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->unique(['campus_id', 'code']);
        });

        Schema::create('sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedSmallInteger('year_level')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->unique(['campus_id', 'term_id', 'code']);
        });

        Schema::create('staff_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->string('position');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('academic_module_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('module')->unique();
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('academic_sequences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->unsignedBigInteger('next_value')->default(1);
            $table->timestamps();
            $table->unique(['campus_id', 'key']);
        });

        Schema::create('academic_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['campus_id', 'key']);
        });
    }

    public function down(): void
    {
        foreach ([
            'academic_settings', 'academic_sequences', 'academic_module_settings', 'staff_assignments', 'sections',
            'rooms', 'subject_prerequisites', 'curriculum_items', 'subjects', 'curricula',
            'programs', 'education_levels', 'grading_periods', 'terms', 'academic_years',
            'guardian_student', 'person_roles', 'people', 'campuses', 'institutions',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
