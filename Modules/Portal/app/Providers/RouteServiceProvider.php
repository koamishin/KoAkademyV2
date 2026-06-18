<?php

declare(strict_types=1);

namespace Modules\Portal\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

final class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->group(module_path('Portal', 'routes/web.php'));
    }
}
