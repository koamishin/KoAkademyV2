<?php

namespace App\Http\Middleware;

use App\Academic\AcademicModuleRegistry;
use App\Enums\SocialLoginProvider;
use App\Features\FeatureRegistry;
use App\Settings\ApplicationFeaturesSettings;
use App\Settings\SocialLoginSettings;
use App\Support\CurrentCampus;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        FeatureRegistry::initialize();

        $user = $request->user();
        $currentCampus = app(CurrentCampus::class)->get();
        $campusMembership = $user && $currentCampus
            ? $user->campusMemberships()->where('campus_id', $currentCampus->getKey())->first()
            : null;
        $settingsFeatures = [];

        if ($user) {
            $settingsFeatures = [
                'profile' => FeatureRegistry::isFeatureAvailableForUser($user, 'settings_profile'),
                'security' => FeatureRegistry::isFeatureAvailableForUser($user, 'settings_mfa_app') || FeatureRegistry::isFeatureAvailableForUser($user, 'settings_mfa_email'),
                'password' => FeatureRegistry::isFeatureAvailableForUser($user, 'settings_password'),
                'appearance' => FeatureRegistry::isFeatureAvailableForUser($user, 'settings_appearance'),
                'passkeys' => FeatureRegistry::isFeatureAvailableForUser($user, 'settings_passkeys'),
            ];
        }

        $socialLoginSettings = app(SocialLoginSettings::class);

        $socialProviders = collect(SocialLoginProvider::cases())
            ->filter(fn (SocialLoginProvider $socialLoginProvider): bool => $socialLoginSettings->isProviderEnabled($socialLoginProvider))
            ->map(fn (SocialLoginProvider $socialLoginProvider): array => [
                'slug' => $socialLoginProvider->value,
                'label' => $socialLoginProvider->label(),
                'url' => route('auth.social.redirect', ['provider' => $socialLoginProvider->value]),
            ])
            ->values()
            ->all();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'impersonating' => app('impersonate')->isImpersonating(),
                'roles' => $request->user()?->getRoleNames()->values()->all() ?? [],
                'campusRole' => $campusMembership?->role->value,
            ],
            'currentCampus' => $currentCampus ? [
                'id' => $currentCampus->getKey(),
                'name' => $currentCampus->name,
                'code' => $currentCampus->code,
                'slug' => $currentCampus->slug,
            ] : null,
            'academic' => [
                'enabledModules' => app(AcademicModuleRegistry::class)->enabledKeys(),
                'person' => $request->user()?->person,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'authLayout' => app(ApplicationFeaturesSettings::class)->auth_layout,
            'settingsFeatures' => $settingsFeatures,
            'socialProviders' => $socialProviders,
        ];
    }
}
