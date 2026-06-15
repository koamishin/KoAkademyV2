<?php

declare(strict_types=1);

namespace Modules\Enrollment\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

final class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'academic.module:enrollment'])
            ->group(module_path('Enrollment', 'routes/web.php'));
    }
}
