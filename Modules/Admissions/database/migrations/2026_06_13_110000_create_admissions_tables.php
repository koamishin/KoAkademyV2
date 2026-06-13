<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_forms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('schema');
            $table->json('document_requirements')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('admission_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_form_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->dateTime('opens_at');
            $table->dateTime('closes_at');
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('applications', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('admission_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('application_number')->unique();
            $table->string('status')->default('draft')->index();
            $table->json('answers')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();
        });
        Schema::create('application_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('requirement_key');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('status')->default('pending')->index();
            $table->timestamps();
        });
        Schema::create('application_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->text('notes');
            $table->string('recommendation')->nullable();
            $table->timestamps();
        });
        Schema::create('application_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status')->index();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_histories');
        Schema::dropIfExists('application_reviews');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('admission_periods');
        Schema::dropIfExists('application_forms');
    }
};
