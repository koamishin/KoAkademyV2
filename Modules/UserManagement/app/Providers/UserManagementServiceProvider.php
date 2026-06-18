<?php

declare(strict_types=1);

namespace Modules\UserManagement\Providers;

use Modules\Portal\Enums\PortalRole;
use Modules\Portal\Support\PortalNavigationRegistry;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class UserManagementServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'UserManagement';

    protected string $nameLower = 'usermanagement';

    protected array $providers = [RouteServiceProvider::class];

    public function boot(): void
    {
        parent::boot();

        app(PortalNavigationRegistry::class)
            ->add(fn ($request, $context): ?array => $context['campus'] && $context['role'] === PortalRole::Admin->value ? [
                'title' => 'Users',
                'href' => route('admin.users.index', ['campus' => $context['campus']['slug']]),
                'icon' => 'UsersRound',
                'section' => 'Operations',
            ] : null);
    }
}
