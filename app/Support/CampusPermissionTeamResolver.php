<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

final class CampusPermissionTeamResolver implements PermissionsTeamResolver
{
    private int|string|null $campusId = 0;

    public function getPermissionsTeamId(): int|string|null
    {
        return $this->campusId;
    }

    public function setPermissionsTeamId(int|string|Model|null $id): void
    {
        $this->campusId = $id instanceof Model ? $id->getKey() : ($id ?? 0);
    }
}
