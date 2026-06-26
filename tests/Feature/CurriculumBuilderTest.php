<?php

use App\Academic\CurriculumTemplateRegistry;
use App\Actions\Academic\CreateCurriculumFromBuilder;
use App\Enums\RoleEnums;
use App\Filament\Resources\Curricula\CurriculumResource;
use App\Filament\Resources\Curricula\Pages\CreateCurriculum;
use App\Filament\Resources\Curricula\Pages\ListCurricula;
use App\Filament\Resources\Subjects\Pages\CreateSubject;
use App\Filament\Resources\Subjects\SubjectResource;
use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\EducationLevel;
use App\Models\Institution;
use App\Models\Program;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

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
    $educationLevel = EducationLevel::query()->create([
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
    $curriculumTemplateRegistry = app(CurriculumTemplateRegistry::class);

    expect($curriculumTemplateRegistry->optionsFor($educationLevel, new Program(['name' => 'Bachelor of Science in Information Technology', 'code' => 'BSIT'])))
        ->toHaveKey('ched-bsit-2015')
        ->and($curriculumTemplateRegistry->optionsFor($educationLevel, new Program(['name' => 'Bachelor of Arts', 'code' => 'BA'])))
        ->not->toHaveKey('ched-bsit-2015')
        ->and($curriculumTemplateRegistry->optionsFor($custom))
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

test('curriculum manager is visible and builder renders for the current campus', function (): void {
    [$campus] = curriculumBuilderContext();
    $user = curriculumManagerUser($campus, RoleEnums::SUPER_ADMIN);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($campus);

    expect(CurriculumResource::shouldRegisterNavigation())->toBeTrue()
        ->and(CurriculumResource::getNavigationLabel())->toBe('Curriculum Manager');

    $this->get(CurriculumResource::getUrl('create', ['tenant' => $campus]))
        ->assertSuccessful()
        ->assertSee('Build a curriculum');
});

test('curriculum management pages render for the current campus', function (): void {
    [$campus, $educationLevel, $program] = curriculumBuilderContext();
    $user = curriculumManagerUser($campus, RoleEnums::SUPER_ADMIN);
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

test('curriculum manager tabs group curricula by school type', function (): void {
    [$campus] = curriculumBuilderContext();
    $user = curriculumManagerUser($campus, RoleEnums::SUPER_ADMIN);

    $elementary = curriculumForSchoolType($campus, 'Elementary Education', 'ELEM', 'grade_school', 'Elementary Curriculum');
    $juniorHigh = curriculumForSchoolType($campus, 'Junior High School', 'JHS', 'high_school', 'Junior High Curriculum');
    $seniorHigh = curriculumForSchoolType($campus, 'Senior High School', 'SHS', 'high_school', 'Senior High Curriculum');
    $college = curriculumForSchoolType($campus, 'College', 'COL', 'college', 'College Curriculum');
    $other = curriculumForSchoolType($campus, 'Executive Education', 'EXEC', 'custom', 'Executive Curriculum');

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($campus);

    Livewire::test(ListCurricula::class)
        ->set('activeTab', 'elementary')
        ->assertCanSeeTableRecords([$elementary])
        ->assertCanNotSeeTableRecords([$juniorHigh, $seniorHigh, $college, $other])
        ->set('activeTab', 'senior_high')
        ->assertCanSeeTableRecords([$seniorHigh])
        ->assertCanNotSeeTableRecords([$elementary, $juniorHigh, $college, $other])
        ->set('activeTab', 'college')
        ->assertCanSeeTableRecords([$college])
        ->assertCanNotSeeTableRecords([$elementary, $juniorHigh, $seniorHigh, $other]);
});

test('curriculum manager follows the current campus configured academic levels', function (): void {
    [$campus, $collegeLevel] = curriculumBuilderContext();
    $seniorHigh = EducationLevel::query()->create([
        'institution_id' => $campus->institution_id,
        'name' => 'Senior High School',
        'code' => 'SHS',
        'category' => 'high_school',
        'sequence' => 2,
    ]);
    $otherCampus = Campus::query()->create([
        'institution_id' => $campus->institution_id,
        'name' => 'Senior High Campus',
        'code' => 'SHS',
    ]);
    Program::query()->create([
        'campus_id' => $otherCampus->id,
        'education_level_id' => $seniorHigh->id,
        'name' => 'Senior High School',
        'code' => 'SHS',
    ]);
    $user = curriculumManagerUser($campus, RoleEnums::SUPER_ADMIN);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($campus);

    expect(CurriculumResource::configuredEducationLevelOptions())->toBe([
        $collegeLevel->id => 'Undergraduate',
    ])
        ->and(CurriculumResource::configuredSchoolTypeOptions())->toBe([
            'college' => 'College',
        ])
        ->and(array_keys(Livewire::test(ListCurricula::class)->instance()->getTabs()))->toBe([
            'all',
            'college',
        ]);

    expect(fn () => app(CreateCurriculumFromBuilder::class)->execute($campus, [
        'education_level_id' => $seniorHigh->id,
        'program_name' => 'Senior High School',
        'program_code' => 'SHS',
        'template_key' => 'blank',
        'name' => 'SHS Curriculum 2026',
        'code' => 'SHS-2026',
        'effective_year' => 2026,
        'status' => 'draft',
        'subjects' => [[
            'code' => 'SHS101',
            'name' => 'Senior High Foundations',
            'subject_type' => 'academic',
            'year_level' => 11,
            'term_sequence' => 1,
            'credit_units' => 3,
            'contact_hours' => 3,
            'lab_hours' => 0,
            'competency_hours' => 0,
            'is_required' => true,
            'prerequisites' => [],
        ]],
        'elective_groups' => [],
    ]))->toThrow(ValidationException::class, 'This education level is not configured for the current campus.');
});

test('campus academic scope limits curriculum levels before programs exist', function (): void {
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => 'KO']);
    $campus = Campus::query()->create([
        'institution_id' => $institution->id,
        'name' => 'College Campus',
        'code' => 'COLLEGE',
        'settings' => ['academic_scope' => 'college'],
    ]);
    $collegeLevel = EducationLevel::query()->create([
        'institution_id' => $institution->id,
        'name' => 'College',
        'code' => 'COL',
        'category' => 'college',
        'sequence' => 1,
    ]);
    EducationLevel::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Elementary',
        'code' => 'ELEM',
        'category' => 'grade_school',
        'sequence' => 2,
    ]);
    $user = curriculumManagerUser($campus, RoleEnums::SUPER_ADMIN);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($campus);

    expect(CurriculumResource::configuredEducationLevelOptions())->toBe([
        $collegeLevel->id => 'College',
    ])
        ->and(CurriculumResource::configuredSchoolTypeOptions())->toBe([
            'college' => 'College',
        ]);
});

test('school admins with curriculum permissions can access curriculum manager pages', function (): void {
    [$campus] = curriculumBuilderContext();
    $user = curriculumManagerUser($campus, RoleEnums::SCHOOL_ADMIN, withPermissions: true);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($campus);

    $this->get(CurriculumResource::getUrl('index', ['tenant' => $campus]))
        ->assertSuccessful()
        ->assertSee('Curriculum Manager');

    $this->get(CurriculumResource::getUrl('create', ['tenant' => $campus]))
        ->assertSuccessful()
        ->assertSee('Build a curriculum');
});

test('curriculum builder creates a curriculum from the curricula resource', function (): void {
    [$campus, $educationLevel, $program] = curriculumBuilderContext();
    $user = curriculumManagerUser($campus, RoleEnums::SUPER_ADMIN);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($campus);

    $component = Livewire::test(CreateCurriculum::class);

    $component
        ->fillForm([
            'education_level_id' => $educationLevel->id,
            'create_program' => true,
            'program_name' => 'Institutional Program',
            'program_code' => 'INST',
            'template_key' => 'blank',
            'name' => 'Institutional Curriculum 2027',
            'code' => 'INST-2027',
            'effective_year' => 2027,
            'status' => 'draft',
            'currency' => 'PHP',
            'tuition_per_unit' => 375,
            'laboratory_fee_per_subject' => 2000,
            'miscellaneous_fees' => [],
            'elective_groups' => [],
            'subjects' => [[
                'code' => 'INST201',
                'name' => 'Institutional Foundations 2',
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
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $curriculum = Curriculum::query()->where('code', 'INST-2027')->firstOrFail();

    $component->assertRedirect(CurriculumResource::getUrl('edit', [
        'tenant' => $campus,
        'record' => $curriculum,
    ]));
});

test('subject creation remains a normal subject catalog flow', function (): void {
    [$campus] = curriculumBuilderContext();
    $user = curriculumManagerUser($campus, RoleEnums::SUPER_ADMIN);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($campus);

    $this->get(SubjectResource::getUrl('create', ['tenant' => $campus]))
        ->assertSuccessful()
        ->assertSee('Subject identity')
        ->assertDontSee('Build a curriculum');

    Livewire::test(CreateSubject::class)
        ->fillForm([
            'institution_id' => $campus->institution_id,
            'code' => 'CAT101',
            'name' => 'Catalog Subject',
            'subject_type' => 'academic',
            'status' => 'active',
            'default_credit_units' => 3,
            'default_contact_hours' => 3,
            'default_lab_hours' => 0,
            'default_competency_hours' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Subject::query()
        ->where('institution_id', $campus->institution_id)
        ->where('code', 'CAT101')
        ->exists())->toBeTrue();
});

test('curriculum manager is scoped to the current campus tenant', function (): void {
    [$campus] = curriculumBuilderContext();
    $user = curriculumManagerUser($campus, RoleEnums::SUPER_ADMIN);
    $visible = curriculumForSchoolType($campus, 'College', 'COL', 'college', 'Visible Curriculum');
    $otherCampus = Campus::query()->create([
        'institution_id' => $campus->institution_id,
        'name' => 'Other Campus',
        'code' => 'OTHER',
    ]);
    $hidden = curriculumForSchoolType($otherCampus, 'College', 'COL', 'college', 'Hidden Curriculum');

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($campus);

    Livewire::test(ListCurricula::class)
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
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

function curriculumManagerUser(Campus $campus, RoleEnums $role, bool $withPermissions = false): User
{
    $user = User::factory()->create();
    $user->campusMemberships()->create([
        'campus_id' => $campus->id,
        'role' => $role,
        'active' => true,
        'is_default' => true,
    ]);

    if ($withPermissions) {
        $spatieRole = Role::query()->firstOrCreate([
            'campus_id' => $campus->id,
            'name' => $role->value,
            'guard_name' => 'web',
        ]);
        $permissions = collect([
            'ViewAny:Curriculum',
            'View:Curriculum',
            'Create:Curriculum',
            'Update:Curriculum',
        ])->map(fn (string $name): Permission => Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ]));

        $spatieRole->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    return $user;
}

function curriculumForSchoolType(
    Campus $campus,
    string $levelName,
    string $levelCode,
    string $category,
    string $curriculumName,
): Curriculum {
    $educationLevel = EducationLevel::query()->updateOrCreate(
        ['institution_id' => $campus->institution_id, 'code' => $levelCode],
        ['name' => $levelName, 'category' => $category, 'sequence' => 1, 'status' => 'active'],
    );
    $program = Program::query()->create([
        'campus_id' => $campus->id,
        'education_level_id' => $educationLevel->id,
        'name' => $levelName,
        'code' => $levelCode.'-PROGRAM-'.str($curriculumName)->slug()->upper(),
    ]);

    return Curriculum::query()->create([
        'program_id' => $program->id,
        'name' => $curriculumName,
        'code' => str($curriculumName)->slug('-')->upper()->toString(),
        'effective_year' => 2026,
        'status' => 'active',
        'currency' => 'PHP',
        'tuition_per_unit' => 375,
        'laboratory_fee_per_subject' => 2000,
    ]);
}
