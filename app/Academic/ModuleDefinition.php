<?php

declare(strict_types=1);

namespace App\Academic;

use App\Contracts\AcademicModule;

final readonly class ModuleDefinition implements AcademicModule
{
    /**
     * @param  list<string>  $dependencies
     * @param  list<string>  $featureKeys
     */
    public function __construct(
        private string $key,
        private string $name,
        private string $description,
        private array $dependencies = [],
        private array $featureKeys = [],
        private bool $enabledByDefault = true,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function dependencies(): array
    {
        return $this->dependencies;
    }

    public function featureKeys(): array
    {
        return $this->featureKeys;
    }

    public function isEnabledByDefault(): bool
    {
        return $this->enabledByDefault;
    }
}
