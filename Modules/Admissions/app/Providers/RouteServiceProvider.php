<?php

declare(strict_types=1);

namespace Modules\Admissions\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

final class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'academic.module:admissions'])
            ->group(module_path('Admissions', 'routes/web.php'));
    }
}
