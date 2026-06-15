<?php

declare(strict_types=1);

namespace App\Filament\Pages\Setup;

use App\Academic\AcademicModuleRegistry;
use App\Academic\ModuleDefinition;
use App\Actions\Setup\CompleteApplicationSetup;
use App\Models\Campus;
use App\Models\User;
use App\Settings\ApplicationSetupSettings;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * @property-read Schema $form
 */
final class ApplicationSetup extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected static ?string $title = 'Set up your institution';

    protected static ?string $slug = 'application-setup';

    protected string $view = 'filament.pages.setup.application-setup';

    protected Width|string|null $maxContentWidth = Width::SevenExtraLarge;

    public ?array $data = [];

    public int $startStep = 1;

    public function mount(
        ApplicationSetupSettings $applicationSetupSettings,
        AcademicModuleRegistry $academicModuleRegistry,
    ): void {
        abort_unless(self::canAccess(), 403);

        $this->startStep = min(max($applicationSetupSettings->current_step, 1), 7);
        $this->form->fill([
            ...$this->defaultData($academicModuleRegistry),
            ...$applicationSetupSettings->draft,
        ]);
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();
        $campus = $tenant instanceof Campus ? $tenant : null;

        return $user instanceof User
            && $user->isSuperAdministrator($campus)
            && ! app(ApplicationSetupSettings::class)->isComplete();
    }

    public function hasLogo(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getLayoutData(): array
    {
        return [
            'hasTopbar' => false,
            'maxContentWidth' => $this->getMaxContentWidth(),
            'maxWidth' => $this->getMaxContentWidth(),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Wizard::make([
                        $this->institutionStep(),
                        $this->campusStep(),
                        $this->educationProfileStep(),
                        $this->academicCalendarStep(),
                        $this->operationsStep(),
                        $this->securityStep(),
                        $this->reviewStep(),
                    ])
                        ->startOnStep($this->startStep)
                        ->persistStepInQueryString('setup-step')
                        ->nextAction(fn (Action $action): Action => $action->label('Save and continue'))
                        ->previousAction(fn (Action $action): Action => $action->label('Back'))
                        ->submitAction(new HtmlString(Blade::render(<<<'BLADE'
                            <x-filament::button type="submit" size="lg" icon="heroicon-m-check-circle">
                                Complete institution setup
                            </x-filament::button>
                        BLADE))),
                ])->livewireSubmitHandler('complete'),
            ])
            ->statePath('data');
    }

    public function complete(CompleteApplicationSetup $completeApplicationSetup): void
    {
        $administrator = Filament::auth()->user();

        if (! $administrator instanceof User) {
            abort(403);
        }

        $campus = $completeApplicationSetup->execute($administrator, $this->form->getState());

        Notification::make()
            ->success()
            ->title('Your institution is ready')
            ->body('The initial campus, academic calendar, modules, and access defaults are configured.')
            ->send();

        $this->redirect(Filament::getUrl($campus), navigate: true);
    }

    private function institutionStep(): Step
    {
        return Step::make('Institution')
            ->description('Identity, branding, and contact details')
            ->icon(Heroicon::OutlinedBuildingOffice2)
            ->afterValidation(fn () => $this->saveProgress(2))
            ->schema([
                TextInput::make('institution_name')
                    ->label('Institution name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, Get $get): void {
                        if (blank($get('institution_code'))) {
                            $set('institution_code', Str::of($state ?? '')->initials()->upper()->limit(10, '')->toString());
                        }
                    }),
                TextInput::make('institution_code')
                    ->required()
                    ->maxLength(50)
                    ->alphaDash(),
                Textarea::make('site_description')
                    ->label('Institution description')
                    ->required()
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull(),
                Select::make('locale')
                    ->options([
                        'en' => 'English',
                        'fil' => 'Filipino',
                    ])
                    ->required()
                    ->native(false),
                Select::make('timezone')
                    ->options($this->timezoneOptions())
                    ->searchable()
                    ->required()
                    ->native(false),
                TextInput::make('contact_email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('support_phone')
                    ->tel()
                    ->maxLength(30),
                TextInput::make('support_url')
                    ->url()
                    ->maxLength(500)
                    ->columnSpanFull(),
                FileUpload::make('site_logo_path')
                    ->label('Institution logo')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('branding')
                    ->visibility('public')
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'])
                    ->maxSize(2048),
                FileUpload::make('site_favicon_path')
                    ->label('Favicon')
                    ->image()
                    ->disk('public')
                    ->directory('branding')
                    ->visibility('public')
                    ->acceptedFileTypes(['image/x-icon', 'image/png', 'image/svg+xml', 'image/jpeg'])
                    ->maxSize(512),
            ])
            ->columns(2);
    }

    private function campusStep(): Step
    {
        return Step::make('Initial campus')
            ->description('Configure the first operational location')
            ->icon(Heroicon::OutlinedMapPin)
            ->afterValidation(fn () => $this->saveProgress(3))
            ->schema([
                TextInput::make('campus_name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, Get $get): void {
                        if (blank($get('campus_slug'))) {
                            $set('campus_slug', Str::slug($state ?? ''));
                        }
                    }),
                TextInput::make('campus_code')
                    ->required()
                    ->maxLength(50)
                    ->alphaDash(),
                TextInput::make('campus_slug')
                    ->required()
                    ->maxLength(255)
                    ->alphaDash(),
                Select::make('campus_timezone')
                    ->options($this->timezoneOptions())
                    ->searchable()
                    ->required()
                    ->native(false),
                Textarea::make('campus_address')
                    ->rows(3)
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    private function educationProfileStep(): Step
    {
        return Step::make('Education profile')
            ->description('Select every level your institution offers')
            ->icon(Heroicon::OutlinedAcademicCap)
            ->afterValidation(fn () => $this->saveProgress(4))
            ->schema([
                CheckboxList::make('education_profiles')
                    ->label('Education levels')
                    ->options([
                        'grade_school' => 'Grade School',
                        'high_school' => 'High School',
                        'college' => 'College and Graduate School',
                        'tesda' => 'Technical-Vocational (TESDA)',
                    ])
                    ->descriptions([
                        'grade_school' => 'Kindergarten through Grade 6',
                        'high_school' => 'Junior and senior high school, Grades 7 through 12',
                        'college' => 'Undergraduate and graduate programs',
                        'tesda' => 'Competency-based technical and vocational programs',
                    ])
                    ->required()
                    ->minItems(1)
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    private function academicCalendarStep(): Step
    {
        return Step::make('Academic calendar')
            ->description('Create the first academic year and its terms')
            ->icon(Heroicon::OutlinedCalendarDays)
            ->afterValidation(function (CompleteApplicationSetup $completeApplicationSetup): void {
                $completeApplicationSetup->validateCalendarConfiguration($this->rawState());
                $this->saveProgress(5);
            })
            ->schema([
                TextInput::make('academic_year_name')
                    ->label('Academic year')
                    ->placeholder('2026-2027')
                    ->required()
                    ->maxLength(100),
                Select::make('term_template')
                    ->label('Term structure')
                    ->options([
                        'semesters' => 'Two semesters',
                        'trimesters' => 'Three trimesters',
                        'quarters' => 'Four quarters',
                        'custom' => 'Custom terms',
                    ])
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn (Set $set, Get $get, ?string $state) => $this->applyTermTemplate($set, $get, $state)),
                DatePicker::make('academic_year_starts_on')
                    ->label('Academic year starts')
                    ->required()
                    ->native(false)
                    ->live(onBlur: true),
                DatePicker::make('academic_year_ends_on')
                    ->label('Academic year ends')
                    ->required()
                    ->after('academic_year_starts_on')
                    ->native(false)
                    ->live(onBlur: true),
                Repeater::make('terms')
                    ->label('Terms')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('code')
                            ->required()
                            ->maxLength(30),
                        DatePicker::make('starts_on')
                            ->required()
                            ->native(false),
                        DatePicker::make('ends_on')
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->reorderable()
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    private function operationsStep(): Step
    {
        $academicModuleRegistry = app(AcademicModuleRegistry::class);

        return Step::make('Operations')
            ->description('Numbering, schedules, and enabled modules')
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->afterValidation(fn () => $this->saveProgress(6))
            ->schema([
                Select::make('schedule_increment')
                    ->options([
                        5 => '5 minutes',
                        10 => '10 minutes',
                        15 => '15 minutes',
                        30 => '30 minutes',
                        60 => '60 minutes',
                    ])
                    ->required()
                    ->native(false),
                CheckboxList::make('working_days')
                    ->options([
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                        7 => 'Sunday',
                    ])
                    ->required()
                    ->minItems(1)
                    ->columns(4)
                    ->columnSpanFull(),
                TextInput::make('student_number_format')
                    ->required()
                    ->maxLength(100),
                TextInput::make('application_number_format')
                    ->required()
                    ->maxLength(100),
                ...$academicModuleRegistry->all()
                    ->map(fn (ModuleDefinition $moduleDefinition): Toggle => Toggle::make("modules.{$moduleDefinition->key()}")
                        ->label($moduleDefinition->name())
                        ->helperText($moduleDefinition->description()))
                    ->values()
                    ->all(),
            ])
            ->columns(2);
    }

    private function securityStep(): Step
    {
        return Step::make('Access and security')
            ->description('Set the default authentication policy')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->afterValidation(fn () => $this->saveProgress(7))
            ->schema([
                Toggle::make('registration_enabled')
                    ->label('Enable public registration'),
                Toggle::make('email_verification_required')
                    ->label('Require email verification'),
                Toggle::make('two_factor_authentication_enabled')
                    ->label('Enable two-factor authentication'),
                Toggle::make('password_reset_enabled')
                    ->label('Enable password reset'),
                TextInput::make('password_min_length')
                    ->numeric()
                    ->required()
                    ->minValue(6)
                    ->maxValue(128),
                Toggle::make('password_requires_uppercase')
                    ->label('Require uppercase letters'),
                Toggle::make('password_requires_lowercase')
                    ->label('Require lowercase letters'),
                Toggle::make('password_requires_numbers')
                    ->label('Require numbers'),
                Toggle::make('password_requires_symbols')
                    ->label('Require symbols'),
                TextInput::make('session_lifetime')
                    ->label('Session lifetime (minutes)')
                    ->numeric()
                    ->required()
                    ->minValue(5)
                    ->maxValue(1440),
                Toggle::make('single_session')
                    ->label('Limit users to one active session'),
                TextInput::make('login_rate_limit')
                    ->label('Maximum login attempts')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(100),
                TextInput::make('login_rate_limit_decay')
                    ->label('Login lockout duration (seconds)')
                    ->numeric()
                    ->required()
                    ->minValue(30)
                    ->maxValue(3600),
                Placeholder::make('default_role')
                    ->label('Default role for new accounts')
                    ->content('Applicant')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    private function reviewStep(): Step
    {
        return Step::make('Review')
            ->description('Confirm the institution configuration')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->schema([
                Placeholder::make('review_institution')
                    ->label('Institution')
                    ->content(fn (Get $get): string => implode(' · ', array_filter([
                        $get('institution_name'),
                        $get('institution_code'),
                        $get('timezone'),
                    ]))),
                Placeholder::make('review_campus')
                    ->label('Initial campus')
                    ->content(fn (Get $get): string => implode(' · ', array_filter([
                        $get('campus_name'),
                        $get('campus_code'),
                        $get('campus_timezone'),
                    ]))),
                Placeholder::make('review_profiles')
                    ->label('Education profile')
                    ->content(fn (Get $get): string => collect($get('education_profiles') ?? [])
                        ->map(fn (string $profile): string => Str::headline($profile))
                        ->join(', ')),
                Placeholder::make('review_calendar')
                    ->label('Academic calendar')
                    ->content(fn (Get $get): string => sprintf(
                        '%s · %s to %s · %d term(s)',
                        $get('academic_year_name') ?? '',
                        $get('academic_year_starts_on') ?? '',
                        $get('academic_year_ends_on') ?? '',
                        count($get('terms') ?? []),
                    )),
                Placeholder::make('review_modules')
                    ->label('Enabled modules')
                    ->content(fn (Get $get): string => collect($get('modules') ?? [])
                        ->filter()
                        ->keys()
                        ->map(fn (string $module): string => Str::headline($module))
                        ->join(', ')),
                Placeholder::make('review_notice')
                    ->label('Ready to finish')
                    ->content('Completing setup creates the academic foundation and unlocks the rest of the admin panel.')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    private function saveProgress(int $nextStep): void
    {
        $applicationSetupSettings = app(ApplicationSetupSettings::class);
        $applicationSetupSettings->status = 'in_progress';
        $applicationSetupSettings->current_step = $nextStep;
        $applicationSetupSettings->draft = $this->rawState();
        $applicationSetupSettings->save();
    }

    private function applyTermTemplate(Set $set, Get $get, ?string $template): void
    {
        if ($template === 'custom') {
            if (blank($get('terms'))) {
                $set('terms', [[
                    'name' => 'Term 1',
                    'code' => 'T1',
                    'starts_on' => $get('academic_year_starts_on'),
                    'ends_on' => $get('academic_year_ends_on'),
                ]]);
            }

            return;
        }

        if (blank($get('academic_year_starts_on')) || blank($get('academic_year_ends_on'))) {
            return;
        }

        $set('terms', $this->buildTerms(
            $template ?? '',
            (string) $get('academic_year_starts_on'),
            (string) $get('academic_year_ends_on'),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultData(AcademicModuleRegistry $academicModuleRegistry): array
    {
        $academicYearStartsOn = now()->startOfMonth()->toDateString();
        $academicYearEndsOn = now()->startOfMonth()->addYear()->subDay()->toDateString();
        $year = now()->year;

        return [
            'institution_name' => '',
            'institution_code' => '',
            'locale' => 'en',
            'timezone' => 'Asia/Manila',
            'site_description' => '',
            'contact_email' => null,
            'support_phone' => null,
            'support_url' => null,
            'site_logo_path' => null,
            'site_favicon_path' => null,
            'campus_name' => 'Main Campus',
            'campus_code' => 'MAIN',
            'campus_slug' => 'main-campus',
            'campus_address' => null,
            'campus_timezone' => 'Asia/Manila',
            'education_profiles' => [],
            'academic_year_name' => "{$year}-".($year + 1),
            'academic_year_starts_on' => $academicYearStartsOn,
            'academic_year_ends_on' => $academicYearEndsOn,
            'term_template' => 'semesters',
            'terms' => $this->buildTerms('semesters', $academicYearStartsOn, $academicYearEndsOn),
            'working_days' => [1, 2, 3, 4, 5],
            'schedule_increment' => 15,
            'student_number_format' => '{year}-{sequence:6}',
            'application_number_format' => 'APP-{year}-{sequence:6}',
            'modules' => $academicModuleRegistry->all()
                ->mapWithKeys(fn (ModuleDefinition $moduleDefinition): array => [
                    $moduleDefinition->key() => $moduleDefinition->isEnabledByDefault(),
                ])
                ->all(),
            'registration_enabled' => true,
            'email_verification_required' => true,
            'two_factor_authentication_enabled' => true,
            'password_reset_enabled' => true,
            'password_min_length' => 8,
            'password_requires_uppercase' => true,
            'password_requires_lowercase' => true,
            'password_requires_numbers' => true,
            'password_requires_symbols' => false,
            'session_lifetime' => 120,
            'single_session' => false,
            'login_rate_limit' => 5,
            'login_rate_limit_decay' => 60,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function timezoneOptions(): array
    {
        return Arr::mapWithKeys(
            \DateTimeZone::listIdentifiers(),
            fn (string $timezone): array => [$timezone => str_replace('_', ' ', $timezone)],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function rawState(): array
    {
        $state = $this->form->getRawState();

        return $state instanceof Arrayable ? $state->toArray() : $state;
    }

    /**
     * @return list<array{name: string, code: string, starts_on: string, ends_on: string}>
     */
    private function buildTerms(string $template, string $startsOnValue, string $endsOnValue): array
    {
        $labels = match ($template) {
            'semesters' => ['First Semester', 'Second Semester'],
            'trimesters' => ['First Trimester', 'Second Trimester', 'Third Trimester'],
            'quarters' => ['First Quarter', 'Second Quarter', 'Third Quarter', 'Fourth Quarter'],
            default => [],
        };

        if ($labels === []) {
            return [];
        }

        $startsOn = CarbonImmutable::parse($startsOnValue)->startOfDay();
        $endsOn = CarbonImmutable::parse($endsOnValue)->startOfDay();
        $count = count($labels);
        $totalDays = $startsOn->diffInDays($endsOn) + 1;
        $daysPerTerm = max((int) floor($totalDays / $count), 1);
        $termStart = $startsOn;
        $terms = [];

        foreach ($labels as $index => $label) {
            $termEnd = $index === ($count - 1)
                ? $endsOn
                : $termStart->addDays($daysPerTerm - 1);

            $terms[] = [
                'name' => $label,
                'code' => 'T'.($index + 1),
                'starts_on' => $termStart->toDateString(),
                'ends_on' => $termEnd->toDateString(),
            ];

            $termStart = $termEnd->addDay();
        }

        return $terms;
    }
}
