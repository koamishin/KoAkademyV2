<?php

use App\Academic\CurriculumTemplateRegistry;
use App\Actions\Academic\CreateCurriculumFromBuilder;
use App\Enums\RoleEnums;
use App\Filament\Resources\Curricula\CurriculumResource;
use App\Filament\Resources\Subjects\SubjectResource;
use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\EducationLevel;
use App\Models\Institution;
use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;

test('official curriculum templates have unique keys and source metadata', function (): void {
    $templates = app(CurriculumTemplateRegistry::class)->all();

    expect($templates)->not->toBeEmpty()
        ->and(array_keys($templates))->toHaveCount(count(array_unique(array_keys($templates))));

    foreach ($templates as $key => $template) {
        expect($template['key'])->toBe($key)
            ->and($template['authority'])->not->toBeEmpty()
            ->and($template['version'])->not->toBeEmpty()
            ->and($template['source_url'])->toStartWith('https://')
            ->and($template['subjects'])->not->toBeEmpty();

        $subjectCodes = array_column($template['subjects'], 'code');

        expect($subjectCodes)->toHaveCount(count(array_unique($subjectCodes)));
    }
});

test('registry offers bsit only to matching college programs and blank to custom levels', function (): void {
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => 'KO']);
    $college = EducationLevel::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Undergraduate',
        'code' => 'UG',
        'category' => 'college',
    ]);
    $custom = EducationLevel::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Executive Education',
        'code' => 'EXEC',
        'category' => 'custom',
    ]);
    $registry = app(CurriculumTemplateRegistry::class);

    expect($registry->optionsFor($college, new Program(['name' => 'Bachelor of Science in Information Technology', 'code' => 'BSIT'])))
        ->toHaveKey('ched-bsit-2015')
        ->and($registry->optionsFor($college, new Program(['name' => 'Bachelor of Arts', 'code' => 'BA'])))
        ->not->toHaveKey('ched-bsit-2015')
        ->and($registry->optionsFor($custom))
        ->toBe(['blank' => 'Start with a flexible blank curriculum']);
});

test('builder atomically creates an editable curriculum and reuses compatible subjects', function (): void {
    [$campus, $educationLevel, $program] = curriculumBuilderContext();
    $template = app(CurriculumTemplateRegistry::class)->find('ched-bsit-2015');
    $existingSubject = Subject::query()->create([
        'institution_id' => $campus->institution_id,
        'code' => 'IT101',
        'name' => 'Introduction to Computing',
        'subject_type' => 'laboratory',
    ]);

    $curriculum = app(CreateCurriculumFromBuilder::class)->execute($campus, [
        'education_level_id' => $educationLevel->id,
        'program_id' => $program->id,
        'template_key' => 'ched-bsit-2015',
        'name' => 'BSIT Curriculum 2026',
        'code' => 'bsit-2026',
        'effective_year' => 2026,
        'status' => 'draft',
        'subjects' => $template['subjects'],
        'elective_groups' => $template['elective_groups'],
    ]);

    expect($curriculum->code)->toBe('BSIT-2026')
        ->and($curriculum->template_authority)->toBe('Commission on Higher Education')
        ->and($curriculum->tuition_per_unit)->toBe('375.00')
        ->and($curriculum->laboratory_fee_per_subject)->toBe('2000.00')
        ->and($curriculum->is_customized)->toBeFalse()
        ->and($curriculum->items)->toHaveCount(count($template['subjects']))
        ->and($curriculum->electiveGroups)->toHaveCount(1)
        ->and($curriculum->items->firstWhere('subject.code', 'IT101')->subject_id)->toBe($existingSubject->id)
        ->and(Subject::query()->where('institution_id', $campus->institution_id)->where('code', 'IT101')->count())->toBe(1);
});

test('builder rolls back when a subject code conflicts with existing catalog details', function (): void {
    [$campus, $educationLevel, $program] = curriculumBuilderContext();
    $template = app(CurriculumTemplateRegistry::class)->find('ched-bsit-2015');
    Subject::query()->create([
        'institution_id' => $campus->institution_id,
        'code' => 'IT101',
        'name' => 'A Different Subject',
        'subject_type' => 'laboratory',
    ]);

    expect(fn () => app(CreateCurriculumFromBuilder::class)->execute($campus, [
        'education_level_id' => $educationLevel->id,
        'program_id' => $program->id,
        'template_key' => 'ched-bsit-2015',
        'name' => 'BSIT Curriculum 2026',
        'code' => 'BSIT-2026',
        'effective_year' => 2026,
        'status' => 'draft',
        'subjects' => $template['subjects'],
        'elective_groups' => $template['elective_groups'],
    ]))->toThrow(ValidationException::class);

    expect(Curriculum::query()->count())->toBe(0)
        ->and(Subject::query()->count())->toBe(1);
});

test('curriculum builder renders for the current campus', function (): void {
    [$campus] = curriculumBuilderContext();
    $user = User::factory()->create();
    $user->campusMemberships()->create([
        'campus_id' => $campus->id,
        'role' => RoleEnums::SUPER_ADMIN,
        'is_default' => true,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($campus);

    $this->get(SubjectResource::getUrl('create', ['tenant' => $campus]))
        ->assertSuccessful()
        ->assertSee('Build a curriculum');
});

test('hidden curriculum management pages render for the current campus', function (): void {
    [$campus, $educationLevel, $program] = curriculumBuilderContext();
    $user = User::factory()->create();
    $user->campusMemberships()->create([
        'campus_id' => $campus->id,
        'role' => RoleEnums::SUPER_ADMIN,
        'is_default' => true,
    ]);
    $curriculum = app(CreateCurriculumFromBuilder::class)->execute($campus, [
        'education_level_id' => $educationLevel->id,
        'program_id' => $program->id,
        'template_key' => 'blank',
        'name' => 'Institutional Curriculum 2026',
        'code' => 'INST-2026',
        'effective_year' => 2026,
        'status' => 'draft',
        'subjects' => [[
            'code' => 'INST101',
            'name' => 'Institutional Foundations',
            'subject_type' => 'academic',
            'year_level' => 1,
            'term_sequence' => 1,
            'credit_units' => 3,
            'contact_hours' => 3,
            'lab_hours' => 0,
            'competency_hours' => 0,
            'is_required' => true,
            'prerequisites' => [],
        ]],
        'elective_groups' => [],
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($campus);

    $this->get(CurriculumResource::getUrl('index', ['tenant' => $campus]))
        ->assertSuccessful()
        ->assertSee('Institutional Curriculum 2026');
    $this->get(CurriculumResource::getUrl('edit', ['tenant' => $campus, 'record' => $curriculum]))
        ->assertSuccessful()
        ->assertSee('Curriculum identity');
});

/**
 * @return array{Campus, EducationLevel, Program}
 */
function curriculumBuilderContext(): array
{
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => 'KO']);
    $campus = Campus::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Main Campus',
        'code' => 'MAIN',
    ]);
    $educationLevel = EducationLevel::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Undergraduate',
        'code' => 'UG',
        'category' => 'college',
    ]);
    $program = Program::query()->create([
        'campus_id' => $campus->id,
        'education_level_id' => $educationLevel->id,
        'name' => 'Bachelor of Science in Information Technology',
        'code' => 'BSIT',
    ]);

    return [$campus, $educationLevel, $program];
}
