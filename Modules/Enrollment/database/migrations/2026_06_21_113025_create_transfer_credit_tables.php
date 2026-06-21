<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_credit_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('curriculum_id')->constrained()->restrictOnDelete();
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_school_name');
            $table->string('source_school_address')->nullable();
            $table->string('previous_program')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('evaluated_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['campus_id', 'student_id']);
        });

        Schema::create('transfer_credit_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_credit_evaluation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('previous_subject_code')->nullable();
            $table->string('previous_subject_name');
            $table->decimal('previous_units', 5, 2)->nullable();
            $table->string('previous_grade')->nullable();
            $table->string('school_year')->nullable();
            $table->string('term')->nullable();
            $table->string('status')->default('pending')->index();
            $table->decimal('credited_units', 5, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['transfer_credit_evaluation_id', 'curriculum_item_id'], 'transfer_credit_subject_curriculum_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_credit_subjects');
        Schema::dropIfExists('transfer_credit_evaluations');
    }
};
