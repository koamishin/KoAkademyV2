<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->dateTime('opens_at');
            $table->dateTime('closes_at');
            $table->boolean('active')->default(true)->index();
            $table->json('policies')->nullable();
            $table->timestamps();
        });
        Schema::create('enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('enrollment_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_id')->constrained()->restrictOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('student_number')->nullable()->unique();
            $table->string('classification')->index();
            $table->string('status')->default('draft')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'enrollment_period_id']);
        });
        Schema::create('enrollment_subjects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_item_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('class_offering_id')->nullable()->index();
            $table->string('status')->default('enrolled')->index();
            $table->string('final_result')->nullable();
            $table->timestamps();
            $table->unique(['enrollment_id', 'curriculum_item_id']);
        });
        Schema::create('enrollment_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_status_histories');
        Schema::dropIfExists('enrollment_subjects');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('enrollment_periods');
    }
};
