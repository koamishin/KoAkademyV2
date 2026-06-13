<?php

declare(strict_types=1);

namespace Modules\Enrollment;

use App\Academic\AcademicModuleRegistry;
use Filament\Contracts\Plugin;
use Filament\Panel;

final class EnrollmentPlugin implements Plugin
{
    public static function make(): static
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'enrollment';
    }

    public function register(Panel $panel): void
    {
        if (app(AcademicModuleRegistry::class)->enabled('enrollment')) {
            $panel->discoverResources(__DIR__.'/Filament/Resources', 'Modules\\Enrollment\\Filament\\Resources');
        }
    }

    public function boot(Panel $panel): void {}
}
