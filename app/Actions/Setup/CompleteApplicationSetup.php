<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Academic\AcademicModuleRegistry;
use App\Actions\Academic\ApplyAcademicPreset;
use App\Enums\RoleEnums;
use App\Models\AcademicSetting;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\Term;
use App\Models\User;
use App\Settings\ApplicationDetailsSettings;
use App\Settings\ApplicationFeaturesSettings;
use App\Settings\ApplicationSecuritySettings;
use App\Settings\ApplicationSetupSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as LaravelValidator;

final readonly class CompleteApplicationSetup
{
    public function __construct(
        private ApplyAcademicPreset $applyAcademicPreset,
        private AcademicModuleRegistry $academicModuleRegistry,
        private ApplicationDetailsSettings $applicationDetailsSettings,
        private ApplicationFeaturesSettings $applicationFeaturesSettings,
        private ApplicationSecuritySettings $applicationSecuritySettings,
        private ApplicationSetupSettings $applicationSetupSettings,
    ) {}

    /**
     * @param  array<string, mixed>  $configuration
     */
    public function execute(User $user, array $configuration): Campus
    {
        $configuration = $this->validateConfiguration($configuration);

        return DB::transaction(function () use ($user, $configuration): Campus {
            $institution = $this->configureInstitution($configuration);
            $campus = $this->configureCampus($institution, $configuration);

            $this->configureBranding($configuration);
            $this->configureEducationLevels($institution, $configuration);
            $this->configureAcademicCalendar($institution, $configuration);
            $this->configureOperations($configuration);
            $this->configureFeaturesAndSecurity($configuration);

            $user->campusMemberships()->updateOrCreate(
                ['campus_id' => $campus->getKey()],
                [
                    'role' => RoleEnums::SUPER_ADMIN,
                    'active' => true,
                    'is_default' => true,
                ],
            );

            $this->applicationSetupSettings->setup_version = 1;
            $this->applicationSetupSettings->status = 'completed';
            $this->applicationSetupSettings->current_step = 7;
            $this->applicationSetupSettings->draft = $configuration;
            $this->applicationSetupSettings->completed_at = now()->toISOString();
            $this->applicationSetupSettings->completed_by_user_id = $user->getKey();
            $this->applicationSetupSettings->save();

            return $campus;
        }, attempts: 3);
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return array<string, mixed>
     */
    public function validateConfiguration(array $configuration): array
    {
        $validator = Validator::make($configuration, [
            'institution_name' => ['required', 'string', 'max:255'],
            'institution_code' => ['required', 'string', 'max:50'],
            'locale' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'timezone:all'],
            'site_description' => ['required', 'string', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'support_url' => ['nullable', 'url', 'max:500'],
            'site_logo_path' => ['nullable', 'string'],
            'site_favicon_path' => ['nullable', 'string'],
            'campus_name' => ['required', 'string', 'max:255'],
            'campus_code' => ['required', 'string', 'max:50'],
            'campus_slug' => ['required', 'string', 'max:255'],
            'campus_address' => ['nullable', 'string', 'max:1000'],
            'campus_timezone' => ['required', 'timezone:all'],
            'education_profiles' => ['required', 'array', 'min:1'],
            'education_profiles.*' => ['required', 'string', 'in:grade_school,high_school,college,tesda', 'distinct:strict'],
            'academic_year_name' => ['required', 'string', 'max:100'],
            'academic_year_starts_on' => ['required', 'date'],
            'academic_year_ends_on' => ['required', 'date', 'after:academic_year_starts_on'],
            'term_template' => ['required', 'string', 'in:semesters,trimesters,quarters,custom'],
            'terms' => ['required', 'array', 'min:1'],
            'terms.*.name' => ['required', 'string', 'max:100'],
            'terms.*.code' => ['required', 'string', 'max:30', 'distinct:ignore_case'],
            'terms.*.starts_on' => ['required', 'date'],
            'terms.*.ends_on' => ['required', 'date'],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['integer', 'between:1,7', 'distinct:strict'],
            'schedule_increment' => ['required', 'integer', 'in:5,10,15,30,60'],
            'student_number_format' => ['required', 'string', 'max:100'],
            'application_number_format' => ['required', 'string', 'max:100'],
            'modules' => ['required', 'array'],
            'modules.*' => ['boolean'],
            'registration_enabled' => ['required', 'boolean'],
            'email_verification_required' => ['required', 'boolean'],
            'two_factor_authentication_enabled' => ['required', 'boolean'],
            'password_reset_enabled' => ['required', 'boolean'],
            'password_min_length' => ['required', 'integer', 'between:6,128'],
            'password_requires_uppercase' => ['required', 'boolean'],
            'password_requires_lowercase' => ['required', 'boolean'],
            'password_requires_numbers' => ['required', 'boolean'],
            'password_requires_symbols' => ['required', 'boolean'],
            'session_lifetime' => ['required', 'integer', 'between:5,1440'],
            'single_session' => ['required', 'boolean'],
            'login_rate_limit' => ['required', 'integer', 'between:1,100'],
            'login_rate_limit_decay' => ['required', 'integer', 'between:30,3600'],
        ]);

        $validator->after(fn (\Illuminate\Validation\Validator $validator) => $this->validateCalendar($configuration, $validator));

        return $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    public function validateCalendarConfiguration(array $configuration): void
    {
        $validator = Validator::make($configuration, [
            'academic_year_name' => ['required', 'string', 'max:100'],
            'academic_year_starts_on' => ['required', 'date'],
            'academic_year_ends_on' => ['required', 'date', 'after:academic_year_starts_on'],
            'term_template' => ['required', 'string', 'in:semesters,trimesters,quarters,custom'],
            'terms' => ['required', 'array', 'min:1'],
            'terms.*.name' => ['required', 'string', 'max:100'],
            'terms.*.code' => ['required', 'string', 'max:30', 'distinct:ignore_case'],
            'terms.*.starts_on' => ['required', 'date'],
            'terms.*.ends_on' => ['required', 'date'],
        ]);

        $validator->after(fn (\Illuminate\Validation\Validator $validator) => $this->validateCalendar($configuration, $validator));
        $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    private function configureInstitution(array $configuration): Institution
    {
        $institution = Institution::query()->oldest('id')->first() ?? new Institution;
        $institution->fill([
            'name' => $configuration['institution_name'],
            'code' => mb_strtoupper((string) $configuration['institution_code']),
            'timezone' => $configuration['timezone'],
            'locale' => $configuration['locale'],
            'status' => 'active',
            'settings' => [
                ...($institution->settings ?? []),
                'description' => $configuration['site_description'],
                'contact_email' => $configuration['contact_email'] ?? null,
                'support_phone' => $configuration['support_phone'] ?? null,
                'support_url' => $configuration['support_url'] ?? null,
            ],
        ])->save();

        return $institution;
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    private function configureCampus(Institution $institution, array $configuration): Campus
    {
        $campus = Campus::query()
            ->whereBelongsTo($institution)
            ->oldest('id')
            ->first() ?? new Campus;
        $campus->fill([
            'institution_id' => $institution->getKey(),
            'name' => $configuration['campus_name'],
            'code' => mb_strtoupper((string) $configuration['campus_code']),
            'slug' => $configuration['campus_slug'],
            'address' => $configuration['campus_address'] ?? null,
            'timezone' => $configuration['campus_timezone'],
            'status' => 'active',
        ])->save();

        return $campus;
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    private function configureBranding(array $configuration): void
    {
        $this->applicationDetailsSettings->site_name = $configuration['institution_name'];
        $this->applicationDetailsSettings->site_description = $configuration['site_description'];
        $this->applicationDetailsSettings->site_logo_path = $configuration['site_logo_path'] ?? null;
        $this->applicationDetailsSettings->site_favicon_path = $configuration['site_favicon_path'] ?? null;
        $this->applicationDetailsSettings->timezone = $configuration['timezone'];
        $this->applicationDetailsSettings->date_format = 'Y-m-d';
        $this->applicationDetailsSettings->time_format = 'H:i';
        $this->applicationDetailsSettings->contact_email = $configuration['contact_email'] ?? null;
        $this->applicationDetailsSettings->support_phone = $configuration['support_phone'] ?? null;
        $this->applicationDetailsSettings->support_url = $configuration['support_url'] ?? null;
        $this->applicationDetailsSettings->save();
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    private function configureEducationLevels(Institution $institution, array $configuration): void
    {
        foreach ($configuration['education_profiles'] as $profile) {
            $this->applyAcademicPreset->execute($institution, $profile);
        }
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    private function configureAcademicCalendar(Institution $institution, array $configuration): void
    {
        AcademicYear::query()
            ->whereBelongsTo($institution)
            ->update(['is_current' => false]);

        $academicYear = AcademicYear::query()->updateOrCreate(
            [
                'institution_id' => $institution->getKey(),
                'name' => $configuration['academic_year_name'],
            ],
            [
                'starts_on' => $configuration['academic_year_starts_on'],
                'ends_on' => $configuration['academic_year_ends_on'],
                'status' => 'active',
                'is_current' => true,
            ],
        );

        foreach (array_values($configuration['terms']) as $index => $termData) {
            Term::query()->updateOrCreate(
                [
                    'academic_year_id' => $academicYear->getKey(),
                    'code' => mb_strtoupper((string) $termData['code']),
                ],
                [
                    'name' => $termData['name'],
                    'sequence' => $index + 1,
                    'starts_on' => $termData['starts_on'],
                    'ends_on' => $termData['ends_on'],
                    'status' => 'active',
                ],
            );
        }

        $academicYear->terms()
            ->whereNotIn('code', collect($configuration['terms'])
                ->pluck('code')
                ->map(fn (string $code): string => mb_strtoupper($code)))
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    private function configureOperations(array $configuration): void
    {
        foreach ([
            'schedule_increment',
            'working_days',
            'student_number_format',
            'application_number_format',
        ] as $key) {
            AcademicSetting::query()->updateOrCreate(
                ['campus_id' => null, 'key' => $key],
                ['value' => (array) $configuration[$key]],
            );
        }

        foreach ($configuration['modules'] as $module => $enabled) {
            $this->academicModuleRegistry->setEnabled($module, (bool) $enabled);
        }
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    private function configureFeaturesAndSecurity(array $configuration): void
    {
        $this->applicationFeaturesSettings->registration_enabled = (bool) $configuration['registration_enabled'];
        $this->applicationFeaturesSettings->email_verification_required = (bool) $configuration['email_verification_required'];
        $this->applicationFeaturesSettings->two_factor_authentication_enabled = (bool) $configuration['two_factor_authentication_enabled'];
        $this->applicationFeaturesSettings->password_reset_enabled = (bool) $configuration['password_reset_enabled'];
        $this->applicationFeaturesSettings->default_user_role = RoleEnums::APPLICANT->value;
        $this->applicationFeaturesSettings->save();

        $this->applicationSecuritySettings->password_min_length = (int) $configuration['password_min_length'];
        $this->applicationSecuritySettings->password_requires_uppercase = (bool) $configuration['password_requires_uppercase'];
        $this->applicationSecuritySettings->password_requires_lowercase = (bool) $configuration['password_requires_lowercase'];
        $this->applicationSecuritySettings->password_requires_numbers = (bool) $configuration['password_requires_numbers'];
        $this->applicationSecuritySettings->password_requires_symbols = (bool) $configuration['password_requires_symbols'];
        $this->applicationSecuritySettings->session_lifetime = (int) $configuration['session_lifetime'];
        $this->applicationSecuritySettings->single_session = (bool) $configuration['single_session'];
        $this->applicationSecuritySettings->login_rate_limit = (int) $configuration['login_rate_limit'];
        $this->applicationSecuritySettings->login_rate_limit_decay = (int) $configuration['login_rate_limit_decay'];
        $this->applicationSecuritySettings->save();
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    private function validateCalendar(array $configuration, LaravelValidator $laravelValidator): void
    {
        if (
            blank($configuration['academic_year_starts_on'] ?? null)
            || blank($configuration['academic_year_ends_on'] ?? null)
            || ! is_array($configuration['terms'] ?? null)
        ) {
            return;
        }

        try {
            $yearStartsOn = CarbonImmutable::parse($configuration['academic_year_starts_on'])->startOfDay();
            $yearEndsOn = CarbonImmutable::parse($configuration['academic_year_ends_on'])->startOfDay();
        } catch (\Throwable) {
            return;
        }

        $previousEnd = null;

        foreach (array_values($configuration['terms']) as $index => $term) {
            try {
                $startsOn = CarbonImmutable::parse($term['starts_on'] ?? null)->startOfDay();
                $endsOn = CarbonImmutable::parse($term['ends_on'] ?? null)->startOfDay();
            } catch (\Throwable) {
                continue;
            }

            if ($endsOn->lessThan($startsOn)) {
                $laravelValidator->errors()->add("terms.{$index}.ends_on", 'The term end date must be on or after its start date.');
            }

            if ($startsOn->lessThan($yearStartsOn) || $endsOn->greaterThan($yearEndsOn)) {
                $laravelValidator->errors()->add("terms.{$index}.starts_on", 'Every term must fall within the academic year.');
            }

            if ($previousEnd instanceof CarbonImmutable && $startsOn->lessThanOrEqualTo($previousEnd)) {
                $laravelValidator->errors()->add("terms.{$index}.starts_on", 'Academic terms may not overlap.');
            }

            $previousEnd = $endsOn;
        }
    }
}
