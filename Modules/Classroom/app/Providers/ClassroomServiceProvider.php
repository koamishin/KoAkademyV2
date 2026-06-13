<?php

declare(strict_types=1);

namespace Modules\Classroom\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Modules\Classroom\Listeners\SyncApprovedEnrollmentRoster;
use Modules\Classroom\Models\ClassOffering;
use Modules\Classroom\Policies\ClassOfferingPolicy;
use Modules\Enrollment\Events\EnrollmentApproved;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class ClassroomServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Classroom';

    protected string $nameLower = 'classroom';

    protected array $providers = [RouteServiceProvider::class];

    public function boot(): void
    {
        Gate::policy(ClassOffering::class, ClassOfferingPolicy::class);
        Event::listen(EnrollmentApproved::class, SyncApprovedEnrollmentRoster::class);
    }
}
