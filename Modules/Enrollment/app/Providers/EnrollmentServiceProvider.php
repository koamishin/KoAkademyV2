<?php

declare(strict_types=1);

namespace Modules\Enrollment\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Enrollment\Models\Enrollment;
use Modules\Enrollment\Policies\EnrollmentPolicy;
use Modules\Portal\Enums\PortalRole;
use Modules\Portal\Support\PortalNavigationRegistry;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class EnrollmentServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Enrollment';

    protected string $nameLower = 'enrollment';

    protected array $providers = [RouteServiceProvider::class];

    public function boot(): void
    {
        Gate::policy(Enrollment::class, EnrollmentPolicy::class);

        app(PortalNavigationRegistry::class)
            ->add(fn ($request, $context): ?array => $context['campus'] && in_array('enrollment', $context['enabledModules'], true) && $context['role'] === PortalRole::Student->value ? [
                'title' => 'Academic History',
                'href' => route('academic-history.show', ['campus' => $context['campus']['slug']]),
                'icon' => 'History',
                'section' => 'Academics',
            ] : null)
            ->add(fn ($request, $context): ?array => $context['campus'] && in_array('enrollment', $context['enabledModules'], true) && $context['role'] === PortalRole::Admin->value ? [
                'title' => 'Enrollment Queue',
                'href' => route('admin.enrollments.index', ['campus' => $context['campus']['slug']]),
                'icon' => 'ListChecks',
                'section' => 'Operations',
            ] : null);
    }
}
