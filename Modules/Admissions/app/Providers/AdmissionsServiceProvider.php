<?php

declare(strict_types=1);

namespace Modules\Admissions\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Policies\ApplicationPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class AdmissionsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Admissions';

    protected string $nameLower = 'admissions';

    protected array $providers = [RouteServiceProvider::class];

    public function boot(): void
    {
        Gate::policy(Application::class, ApplicationPolicy::class);
    }
}
