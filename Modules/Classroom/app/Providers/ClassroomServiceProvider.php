<?php

declare(strict_types=1);

namespace Modules\Classroom\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Modules\Classroom\Listeners\SyncApprovedEnrollmentRoster;
use Modules\Classroom\Models\ClassOffering;
use Modules\Classroom\Policies\ClassOfferingPolicy;
use Modules\Enrollment\Events\EnrollmentApproved;
use Modules\Portal\Enums\PortalRole;
use Modules\Portal\Support\PortalNavigationRegistry;
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

        app(PortalNavigationRegistry::class)
            ->add(fn ($request, $context): ?array => $context['campus'] && in_array('classroom', $context['enabledModules'], true) && in_array($context['role'], [PortalRole::Student->value, PortalRole::Faculty->value], true) ? [
                'title' => 'My Classes',
                'href' => route('classroom.index', ['campus' => $context['campus']['slug']]),
                'icon' => 'GraduationCap',
                'section' => 'Academics',
            ] : null)
            ->add(fn ($request, $context): ?array => $context['campus'] && in_array('classroom', $context['enabledModules'], true) && $context['role'] === PortalRole::Admin->value ? [
                'title' => 'Classes',
                'href' => route('admin.classes.index', ['campus' => $context['campus']['slug']]),
                'icon' => 'School',
                'section' => 'Operations',
            ] : null);
    }
}
