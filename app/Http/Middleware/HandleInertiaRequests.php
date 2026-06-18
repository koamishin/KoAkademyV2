<?php

namespace App\Http\Middleware;

use App\Academic\AcademicModuleRegistry;
use App\Enums\SocialLoginProvider;
use App\Features\FeatureRegistry;
use App\Models\Campus;
use App\Settings\ApplicationFeaturesSettings;
use App\Settings\SocialLoginSettings;
use App\Support\CurrentCampus;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Modules\Portal\Enums\PortalRole;
use Modules\Portal\Support\PortalNavigationRegistry;
use Modules\Portal\Support\PortalRoleResolver;

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
        $currentCampus = $this->resolveCurrentCampus($request);
        $portalRole = $user ? app(PortalRoleResolver::class)->resolve($user, $currentCampus)->value : null;
        $portalHome = $currentCampus instanceof Campus
            ? route('campus.dashboard', ['campus' => $currentCampus])
            : route('dashboard');
        $canAccessAdminPortal = $user
            ? app(PortalRoleResolver::class)->canAccessAdminPortal($user, $currentCampus)
            : false;
        $enabledAcademicModules = app(AcademicModuleRegistry::class)->enabledKeys();
        $portalCampus = $currentCampus instanceof Campus ? [
            'id' => $currentCampus->getKey(),
            'name' => $currentCampus->name,
            'code' => $currentCampus->code,
            'slug' => $currentCampus->slug,
        ] : null;
        $campusMembership = $user && $currentCampus instanceof Campus
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
            'portal' => [
                'role' => $portalRole ?? PortalRole::Unknown->value,
                'home' => $portalHome,
                'navigation' => app(PortalNavigationRegistry::class)->forRequest($request, [
                    'role' => $portalRole ?? PortalRole::Unknown->value,
                    'campus' => $portalCampus,
                    'canAccessAdminPortal' => $canAccessAdminPortal,
                    'enabledModules' => $enabledAcademicModules,
                ]),
                'canAccessAdminPortal' => $canAccessAdminPortal,
            ],
            'currentCampus' => $portalCampus,
            'academic' => [
                'enabledModules' => $enabledAcademicModules,
                'person' => $request->user()?->person,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'authLayout' => app(ApplicationFeaturesSettings::class)->auth_layout,
            'settingsFeatures' => $settingsFeatures,
            'socialProviders' => $socialProviders,
        ];
    }

    private function resolveCurrentCampus(Request $request): ?Campus
    {
        $currentCampus = $request->attributes->get('currentCampus') ?? app(CurrentCampus::class)->get();

        if ($currentCampus instanceof Campus) {
            return $currentCampus;
        }

        $routeCampus = $request->route('campus');

        if ($routeCampus instanceof Campus) {
            return $routeCampus;
        }

        return is_string($routeCampus)
            ? Campus::query()->where('slug', $routeCampus)->first()
            : null;
    }
}
