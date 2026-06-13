<?php

declare(strict_types=1);

namespace App\Academic;

use App\Models\AcademicModuleSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class AcademicModuleRegistry
{
    /**
     * @var array<string, ModuleDefinition>
     */
    private array $modules = [];

    public function __construct()
    {
        $this->register(new ModuleDefinition(
            key: 'admissions',
            name: 'Admissions',
            description: 'Applicant forms, requirements, review, decisions, and student conversion.',
        ));
        $this->register(new ModuleDefinition(
            key: 'enrollment',
            name: 'Enrollment',
            description: 'Enrollment periods, curriculum assignment, subject loading, and approvals.',
        ));
        $this->register(new ModuleDefinition(
            key: 'classroom',
            name: 'Classroom',
            description: 'Class schedules, rosters, streams, materials, assignments, and submissions.',
            dependencies: ['enrollment'],
        ));
        $this->register(new ModuleDefinition(
            key: 'notifications',
            name: 'Academic Notifications',
            description: 'Database notifications for admissions, enrollment, and classroom events.',
        ));
    }

    public function register(ModuleDefinition $module): void
    {
        $this->modules[$module->key()] = $module;
    }

    /**
     * @return Collection<string, ModuleDefinition>
     */
    public function all(): Collection
    {
        return collect($this->modules);
    }

    public function get(string $key): ?ModuleDefinition
    {
        return $this->modules[$key] ?? null;
    }

    public function enabled(string $key): bool
    {
        $module = $this->get($key);

        if (! $module instanceof ModuleDefinition) {
            return false;
        }

        if (! Schema::hasTable('academic_module_settings')) {
            return $module->isEnabledByDefault();
        }

        return AcademicModuleSetting::query()
            ->where('module', $key)
            ->value('enabled') ?? $module->isEnabledByDefault();
    }

    /**
     * @return list<string>
     */
    public function enabledKeys(): array
    {
        return $this->all()
            ->filter(fn (ModuleDefinition $module): bool => $this->enabled($module->key()))
            ->keys()
            ->values()
            ->all();
    }

    public function setEnabled(string $key, bool $enabled): void
    {
        $module = $this->get($key);

        if (! $module instanceof ModuleDefinition) {
            return;
        }

        if ($enabled) {
            foreach ($module->dependencies() as $dependency) {
                $this->setEnabled($dependency, true);
            }
        } else {
            foreach ($this->all() as $dependent) {
                if (in_array($key, $dependent->dependencies(), true)) {
                    $this->setEnabled($dependent->key(), false);
                }
            }
        }

        AcademicModuleSetting::query()->updateOrCreate(
            ['module' => $key],
            ['enabled' => $enabled],
        );
    }
}
