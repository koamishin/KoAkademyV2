<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curricula', function (Blueprint $table): void {
            $table->char('currency', 3)->default('PHP')->after('is_customized');
            $table->decimal('tuition_per_unit', 12, 2)->nullable()->after('currency');
            $table->decimal('laboratory_fee_per_subject', 12, 2)->nullable()->after('tuition_per_unit');
        });

        Schema::create('curriculum_miscellaneous_fees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('curriculum_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamps();
            $table->unique(['curriculum_id', 'code']);
        });

        Schema::create('enrollment_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enrollment_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('currency', 3)->default('PHP');
            $table->decimal('tuition_total', 12, 2)->default(0);
            $table->decimal('laboratory_total', 12, 2)->default(0);
            $table->decimal('miscellaneous_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamp('assessed_at');
            $table->timestamps();
        });

        Schema::create('enrollment_assessment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enrollment_assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('curriculum_miscellaneous_fee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->string('code');
            $table->string('description');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_amount', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_assessment_lines');
        Schema::dropIfExists('enrollment_assessments');
        Schema::dropIfExists('curriculum_miscellaneous_fees');

        Schema::table('curricula', function (Blueprint $table): void {
            $table->dropColumn(['currency', 'tuition_per_unit', 'laboratory_fee_per_subject']);
        });
    }
};
