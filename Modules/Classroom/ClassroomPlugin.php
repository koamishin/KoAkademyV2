<?php

declare(strict_types=1);

namespace Modules\Classroom;

use App\Academic\AcademicModuleRegistry;
use Filament\Contracts\Plugin;
use Filament\Panel;

final class ClassroomPlugin implements Plugin
{
    public static function make(): static
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'classroom';
    }

    public function register(Panel $panel): void
    {
        if (app(AcademicModuleRegistry::class)->enabled('classroom')) {
            $panel->discoverResources(__DIR__.'/Filament/Resources', 'Modules\\Classroom\\Filament\\Resources');
        }
    }

    public function boot(Panel $panel): void {}
}
