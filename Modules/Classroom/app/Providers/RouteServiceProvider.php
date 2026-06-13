<?php

declare(strict_types=1);

namespace Modules\Classroom\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

final class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'academic.module:classroom'])->group(module_path('Classroom', 'routes/web.php'));
    }
}
