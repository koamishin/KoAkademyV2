<?php

declare(strict_types=1);

namespace Modules\Admissions\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Policies\ApplicationPolicy;
use Modules\Portal\Enums\PortalRole;
use Modules\Portal\Support\PortalNavigationRegistry;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class AdmissionsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Admissions';

    protected string $nameLower = 'admissions';

    protected array $providers = [RouteServiceProvider::class];

    public function boot(): void
    {
        Gate::policy(Application::class, ApplicationPolicy::class);

        app(PortalNavigationRegistry::class)
            ->add(fn ($request, $context): ?array => $context['campus'] && in_array('admissions', $context['enabledModules'], true) && in_array($context['role'], [PortalRole::Applicant->value, PortalRole::Student->value], true) ? [
                'title' => 'Applications',
                'href' => route('applications.index', ['campus' => $context['campus']['slug']]),
                'icon' => 'ClipboardList',
                'section' => 'Academics',
            ] : null)
            ->add(fn ($request, $context): ?array => $context['campus'] && in_array('admissions', $context['enabledModules'], true) && $context['role'] === PortalRole::Admin->value ? [
                'title' => 'Applications Queue',
                'href' => route('admin.applications.index', ['campus' => $context['campus']['slug']]),
                'icon' => 'ClipboardCheck',
                'section' => 'Operations',
            ] : null);
    }
}
