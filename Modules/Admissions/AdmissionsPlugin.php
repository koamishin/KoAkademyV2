<?php

declare(strict_types=1);

namespace Modules\Admissions;

use App\Academic\AcademicModuleRegistry;
use Filament\Contracts\Plugin;
use Filament\Panel;

final class AdmissionsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'admissions';
    }

    public function register(Panel $panel): void
    {
        if (app(AcademicModuleRegistry::class)->enabled('admissions')) {
            $panel->discoverResources(__DIR__.'/Filament/Resources', 'Modules\\Admissions\\Filament\\Resources');
        }
    }

    public function boot(Panel $panel): void {}
}
