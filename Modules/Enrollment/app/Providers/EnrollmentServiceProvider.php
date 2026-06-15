<?php

declare(strict_types=1);

namespace Modules\Enrollment\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Enrollment\Models\Enrollment;
use Modules\Enrollment\Policies\EnrollmentPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class EnrollmentServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Enrollment';

    protected string $nameLower = 'enrollment';

    protected array $providers = [RouteServiceProvider::class];

    public function boot(): void
    {
        Gate::policy(Enrollment::class, EnrollmentPolicy::class);
    }
}
