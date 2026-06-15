<?php

use App\Actions\Setup\CompleteApplicationSetup;
use App\Enums\RoleEnums;
use App\Filament\Pages\Setup\ApplicationSetup;
use App\Filament\Pages\Setup\SetupRequired;
use App\Models\AcademicModuleSetting;
use App\Models\AcademicSetting;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\EducationLevel;
use App\Models\Institution;
use App\Models\Term;
use App\Models\User;
use App\Settings\ApplicationDetailsSettings;
use App\Settings\ApplicationFeaturesSettings;
use App\Settings\ApplicationSecuritySettings;
use App\Settings\ApplicationSetupSettings;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function createSetupAdministrator(RoleEnums $roleEnums = RoleEnums::SUPER_ADMIN): array
{
    $institution = Institution::query()->create([
        'name' => 'Ko Academy',
        'code' => 'KO',
    ]);
    $campus = Campus::query()->create([
        'institution_id' => $institution->getKey(),
        'name' => 'Main Campus',
        'code' => 'MAIN',
    ]);
    $user = User::factory()->create();
    $user->campusMemberships()->create([
        'campus_id' => $campus->getKey(),
        'role' => $roleEnums,
        'active' => true,
        'is_default' => true,
    ]);

    return ['institution' => $institution, 'campus' => $campus, 'user' => $user];
}

function markApplicationSetupPending(int $step = 1, array $draft = []): void
{
    $applicationSetupSettings = app(ApplicationSetupSettings::class);
    $applicationSetupSettings->status = $step === 1 ? 'pending' : 'in_progress';
    $applicationSetupSettings->current_step = $step;
    $applicationSetupSettings->draft = $draft;
    $applicationSetupSettings->completed_at = null;
    $applicationSetupSettings->completed_by_user_id = null;
    $applicationSetupSettings->save();
}

function validApplicationSetupData(): array
{
    return [
        'institution_name' => 'North Valley Institute',
        'institution_code' => 'NVI',
        'locale' => 'en',
        'timezone' => 'Asia/Manila',
        'site_description' => 'A learner-centered academic institution.',
        'contact_email' => 'hello@nvi.example',
        'support_phone' => '+63 2 555 0100',
        'support_url' => 'https://nvi.example/support',
        'site_logo_path' => null,
        'site_favicon_path' => null,
        'campus_name' => 'Central Campus',
        'campus_code' => 'CENTRAL',
        'campus_slug' => 'central-campus',
        'campus_address' => 'Manila, Philippines',
        'campus_timezone' => 'Asia/Manila',
        'education_profiles' => ['grade_school', 'college'],
        'academic_year_name' => '2026-2027',
        'academic_year_starts_on' => '2026-06-01',
        'academic_year_ends_on' => '2027-03-31',
        'term_template' => 'semesters',
        'terms' => [
            [
                'name' => 'First Semester',
                'code' => 'S1',
                'starts_on' => '2026-06-01',
                'ends_on' => '2026-10-31',
            ],
            [
                'name' => 'Second Semester',
                'code' => 'S2',
                'starts_on' => '2026-11-01',
                'ends_on' => '2027-03-31',
            ],
        ],
        'working_days' => [1, 2, 3, 4, 5],
        'schedule_increment' => 15,
        'student_number_format' => 'NVI-{year}-{sequence:5}',
        'application_number_format' => 'APP-{year}-{sequence:5}',
        'modules' => [
            'admissions' => true,
            'enrollment' => false,
            'classroom' => true,
            'notifications' => true,
        ],
        'registration_enabled' => true,
        'email_verification_required' => true,
        'two_factor_authentication_enabled' => true,
        'password_reset_enabled' => true,
        'password_min_length' => 10,
        'password_requires_uppercase' => true,
        'password_requires_lowercase' => true,
        'password_requires_numbers' => true,
        'password_requires_symbols' => true,
        'session_lifetime' => 90,
        'single_session' => false,
        'login_rate_limit' => 5,
        'login_rate_limit_decay' => 60,
    ];
}

test('setup settings resolve to safe defaults before their database rows exist', function (): void {
    DB::table('settings')
        ->where('group', ApplicationSetupSettings::group())
        ->delete();

    app()->forgetInstance(ApplicationSetupSettings::class);

    $applicationSetupSettings = app(ApplicationSetupSettings::class);

    expect($applicationSetupSettings->setup_version)->toBe(1)
        ->and($applicationSetupSettings->status)->toBe('pending')
        ->and($applicationSetupSettings->current_step)->toBe(1)
        ->and($applicationSetupSettings->draft)->toBe([])
        ->and($applicationSetupSettings->completed_at)->toBeNull()
        ->and($applicationSetupSettings->completed_by_user_id)->toBeNull();
});

test('pending setup forces super administrators into the onboarding wizard', function (): void {
    ['campus' => $campus, 'user' => $user] = createSetupAdministrator();
    markApplicationSetupPending();

    $this->actingAs($user)
        ->get(Dashboard::getUrl(panel: 'admin', tenant: $campus))
        ->assertRedirect(ApplicationSetup::getUrl(panel: 'admin', tenant: $campus));

    $this->get(ApplicationSetup::getUrl(panel: 'admin', tenant: $campus))
        ->assertSuccessful()
        ->assertSee('Build the foundation once')
        ->assertSee('fi-simple-layout', escape: false)
        ->assertDontSee('fi-sidebar', escape: false)
        ->assertDontSee('fi-topbar', escape: false);
});

test('the first super administrator bootstraps a placeholder and enters setup', function (): void {
    $user = User::factory()->create();
    $role = Role::query()->firstOrCreate([
        'campus_id' => null,
        'name' => RoleEnums::SUPER_ADMIN->value,
        'guard_name' => 'web',
    ]);
    $user->assignRole($role);
    markApplicationSetupPending();

    $response = $this->actingAs($user)->get('/admin');
    $campus = Campus::query()->sole();

    $response->assertRedirect(ApplicationSetup::getUrl(panel: 'admin', tenant: $campus));

    expect(Institution::query()->count())->toBe(1)
        ->and($user->fresh()->assignedCampus()?->is($campus))->toBeTrue();
});

test('pending setup sends other administrators to the read only setup page', function (): void {
    ['campus' => $campus, 'user' => $user] = createSetupAdministrator(RoleEnums::SCHOOL_ADMIN);
    markApplicationSetupPending();

    $this->actingAs($user)
        ->get(Dashboard::getUrl(panel: 'admin', tenant: $campus))
        ->assertRedirect(SetupRequired::getUrl(panel: 'admin', tenant: $campus));

    $this->get(SetupRequired::getUrl(panel: 'admin', tenant: $campus))
        ->assertSuccessful()
        ->assertSee('Your administrator is finishing setup')
        ->assertSee('fi-simple-layout', escape: false)
        ->assertDontSee('fi-sidebar', escape: false)
        ->assertDontSee('fi-topbar', escape: false);
});

test('completed installations can access the normal Filament dashboard', function (): void {
    ['campus' => $campus, 'user' => $user] = createSetupAdministrator();

    $this->actingAs($user)
        ->get(Dashboard::getUrl(panel: 'admin', tenant: $campus))
        ->assertSuccessful();
});

test('wizard progress is saved after a validated step', function (): void {
    ['campus' => $campus, 'user' => $user] = createSetupAdministrator();
    markApplicationSetupPending();

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($campus);
    Filament::bootCurrentPanel();

    Livewire::test(ApplicationSetup::class)
        ->fillForm([
            'institution_name' => 'North Valley Institute',
            'institution_code' => 'NVI',
            'locale' => 'en',
            'timezone' => 'Asia/Manila',
            'site_description' => 'A learner-centered academic institution.',
            'contact_email' => 'hello@nvi.example',
        ])
        ->goToNextWizardStep()
        ->assertHasNoFormErrors();

    $applicationSetupSettings = app(ApplicationSetupSettings::class);
    $applicationSetupSettings->refresh();

    expect($applicationSetupSettings->status)->toBe('in_progress')
        ->and($applicationSetupSettings->current_step)->toBe(2)
        ->and($applicationSetupSettings->draft['institution_name'])->toBe('North Valley Institute');
});

test('completion configures the placeholder institution and operational foundation idempotently', function (): void {
    ['institution' => $placeholderInstitution, 'campus' => $placeholderCampus, 'user' => $user] = createSetupAdministrator();
    markApplicationSetupPending();

    $completeApplicationSetup = app(CompleteApplicationSetup::class);
    $campus = $completeApplicationSetup->execute($user, validApplicationSetupData());
    $completeApplicationSetup->execute($user, validApplicationSetupData());

    expect(Institution::query()->count())->toBe(1)
        ->and(Campus::query()->count())->toBe(1)
        ->and($placeholderInstitution->fresh()->name)->toBe('North Valley Institute')
        ->and($placeholderCampus->fresh()->name)->toBe('Central Campus')
        ->and($campus->slug)->toBe('central-campus')
        ->and(EducationLevel::query()->count())->toBe(9)
        ->and(AcademicYear::query()->count())->toBe(1)
        ->and(Term::query()->count())->toBe(2)
        ->and(AcademicYear::query()->sole()->is_current)->toBeTrue()
        ->and(AcademicModuleSetting::query()->where('module', 'enrollment')->value('enabled'))->toBeTrue()
        ->and(AcademicSetting::query()->where('key', 'student_number_format')->value('value'))->toBe([
            'NVI-{year}-{sequence:5}',
        ])
        ->and($user->fresh()->assignedCampus()?->is($campus))->toBeTrue();

    $applicationSetupSettings = app(ApplicationSetupSettings::class);
    $applicationSetupSettings->refresh();
    $applicationDetailsSettings = app(ApplicationDetailsSettings::class);
    $applicationDetailsSettings->refresh();
    $applicationFeaturesSettings = app(ApplicationFeaturesSettings::class);
    $applicationFeaturesSettings->refresh();
    $applicationSecuritySettings = app(ApplicationSecuritySettings::class);
    $applicationSecuritySettings->refresh();

    expect($applicationSetupSettings->isComplete())->toBeTrue()
        ->and($applicationSetupSettings->completed_by_user_id)->toBe($user->getKey())
        ->and($applicationDetailsSettings->site_name)->toBe('North Valley Institute')
        ->and($applicationFeaturesSettings->default_user_role)->toBe('applicant')
        ->and($applicationSecuritySettings->password_min_length)->toBe(10)
        ->and($applicationSecuritySettings->password_requires_symbols)->toBeTrue();
});

test('completion normalizes numeric form state before saving typed security settings', function (): void {
    ['user' => $user] = createSetupAdministrator();
    markApplicationSetupPending();

    $configuration = validApplicationSetupData();
    $configuration['password_min_length'] = 10.0;
    $configuration['session_lifetime'] = 90.0;
    $configuration['login_rate_limit'] = 5.0;
    $configuration['login_rate_limit_decay'] = 60.0;

    app(CompleteApplicationSetup::class)->execute($user, $configuration);

    $applicationSecuritySettings = app(ApplicationSecuritySettings::class);
    $applicationSecuritySettings->refresh();

    expect($applicationSecuritySettings->password_min_length)->toBe(10)
        ->and($applicationSecuritySettings->session_lifetime)->toBe(90)
        ->and($applicationSecuritySettings->login_rate_limit)->toBe(5)
        ->and($applicationSecuritySettings->login_rate_limit_decay)->toBe(60);
});

test('calendar validation rejects duplicate codes overlaps and out of range terms', function (array $terms): void {
    $configuration = validApplicationSetupData();
    $configuration['terms'] = $terms;

    expect(fn () => app(CompleteApplicationSetup::class)->validateCalendarConfiguration($configuration))
        ->toThrow(ValidationException::class);
})->with([
    'duplicate codes' => [[
        ['name' => 'First', 'code' => 'S1', 'starts_on' => '2026-06-01', 'ends_on' => '2026-10-31'],
        ['name' => 'Second', 'code' => 's1', 'starts_on' => '2026-11-01', 'ends_on' => '2027-03-31'],
    ]],
    'overlapping terms' => [[
        ['name' => 'First', 'code' => 'S1', 'starts_on' => '2026-06-01', 'ends_on' => '2026-11-15'],
        ['name' => 'Second', 'code' => 'S2', 'starts_on' => '2026-11-01', 'ends_on' => '2027-03-31'],
    ]],
    'term outside academic year' => [[
        ['name' => 'First', 'code' => 'S1', 'starts_on' => '2026-05-01', 'ends_on' => '2026-10-31'],
        ['name' => 'Second', 'code' => 'S2', 'starts_on' => '2026-11-01', 'ends_on' => '2027-03-31'],
    ]],
]);

test('completed setup pages redirect back to the normal panel', function (): void {
    ['campus' => $campus, 'user' => $user] = createSetupAdministrator();

    $this->actingAs($user)
        ->get(ApplicationSetup::getUrl(panel: 'admin', tenant: $campus))
        ->assertRedirect(Filament::getPanel('admin')->getUrl($campus));
});
