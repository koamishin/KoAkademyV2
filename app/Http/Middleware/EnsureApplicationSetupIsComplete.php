<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Filament\Pages\Setup\ApplicationSetup;
use App\Filament\Pages\Setup\SetupRequired;
use App\Models\Campus;
use App\Settings\ApplicationSetupSettings;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureApplicationSetupIsComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $setup = app(ApplicationSetupSettings::class);
        $campus = $request->user()?->assignedCampus();

        if (! $campus instanceof Campus) {
            return $next($request);
        }

        if ($setup->isComplete()) {
            if ($request->routeIs(ApplicationSetup::getRouteName(), SetupRequired::getRouteName())) {
                return redirect()->to(Filament::getUrl($campus));
            }

            return $next($request);
        }

        if ($request->routeIs('filament.admin.auth.logout', 'livewire.update')) {
            return $next($request);
        }

        $isSuperAdministrator = $request->user()->isSuperAdministrator($campus);
        $allowedRoute = $isSuperAdministrator
            ? ApplicationSetup::getRouteName()
            : SetupRequired::getRouteName();

        if ($request->routeIs($allowedRoute)) {
            return $next($request);
        }

        $destination = $isSuperAdministrator
            ? ApplicationSetup::getUrl(tenant: $campus)
            : SetupRequired::getUrl(tenant: $campus);

        return redirect()->to($destination);
    }
}
