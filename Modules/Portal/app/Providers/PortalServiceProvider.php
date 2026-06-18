<?php

declare(strict_types=1);

namespace Modules\Portal\Providers;

use Modules\Portal\Support\PortalNavigationRegistry;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class PortalServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Portal';

    protected string $nameLower = 'portal';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->singleton(PortalNavigationRegistry::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->registerNavigation();
    }

    private function registerNavigation(): void
    {
        app(PortalNavigationRegistry::class)
            ->add(fn ($request, $context): ?array => $context['campus'] ? [
                'title' => 'Dashboard',
                'href' => route('campus.dashboard', ['campus' => $context['campus']['slug']]),
                'icon' => 'LayoutGrid',
                'section' => 'Platform',
            ] : null)
            ->add(fn ($request, $context): ?array => $context['campus'] ? [
                'title' => 'Notifications',
                'href' => route('notifications.index', ['campus' => $context['campus']['slug']]),
                'icon' => 'Bell',
                'section' => 'Platform',
            ] : null)
            ->add(fn (): array => [
                'title' => 'Profile',
                'href' => route('profile.edit'),
                'icon' => 'UserCog',
                'section' => 'Account',
            ])
            ->add(fn (): array => [
                'title' => 'Security',
                'href' => route('security.edit'),
                'icon' => 'ShieldCheck',
                'section' => 'Account',
            ])
            ->add(fn ($request, $context): ?array => $context['canAccessAdminPortal'] ? [
                'title' => 'Advanced Admin',
                'href' => $context['campus'] ? url('/admin/campus/'.$context['campus']['slug']) : url('/admin'),
                'icon' => 'ExternalLink',
                'section' => 'Admin',
                'external' => true,
            ] : null);
    }
}
