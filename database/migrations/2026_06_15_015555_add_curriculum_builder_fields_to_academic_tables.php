<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curricula', function (Blueprint $table): void {
            $table->string('template_key')->nullable()->after('effective_year')->index();
            $table->string('template_version')->nullable()->after('template_key');
            $table->string('template_authority')->nullable()->after('template_version');
            $table->string('template_source_url')->nullable()->after('template_authority');
            $table->boolean('is_customized')->default(false)->after('template_source_url');
        });

        Schema::create('curriculum_elective_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('curriculum_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->unsignedSmallInteger('minimum_subjects')->default(0);
            $table->unsignedSmallInteger('maximum_subjects')->nullable();
            $table->decimal('minimum_units', 6, 2)->default(0);
            $table->decimal('maximum_units', 6, 2)->nullable();
            $table->timestamps();
            $table->unique(['curriculum_id', 'code']);
        });

        Schema::table('curriculum_items', function (Blueprint $table): void {
            $table->unsignedSmallInteger('position')->default(1)->after('term_sequence');
            $table->foreignId('elective_group_id')->nullable()->after('elective_group')->index();
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_items', function (Blueprint $table): void {
            $table->dropColumn('elective_group_id');
            $table->dropColumn('position');
        });

        Schema::dropIfExists('curriculum_elective_groups');

        Schema::table('curricula', function (Blueprint $table): void {
            $table->dropColumn([
                'template_key',
                'template_version',
                'template_authority',
                'template_source_url',
                'is_customized',
            ]);
        });
    }
};
