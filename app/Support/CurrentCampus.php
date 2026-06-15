<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Campus;

final class CurrentCampus
{
    private ?Campus $campus = null;

    public function set(Campus $campus): void
    {
        $this->campus = $campus;
    }

    public function get(): ?Campus
    {
        return $this->campus;
    }

    public function id(): ?int
    {
        return $this->campus?->getKey();
    }

    public function forget(): void
    {
        $this->campus = null;
    }
}
