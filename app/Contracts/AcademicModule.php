<?php

declare(strict_types=1);

namespace App\Contracts;

interface AcademicModule
{
    public function key(): string;

    public function name(): string;

    public function description(): string;

    /**
     * @return list<string>
     */
    public function dependencies(): array;

    /**
     * @return list<string>
     */
    public function featureKeys(): array;
}
