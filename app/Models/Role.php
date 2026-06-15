<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

final class Role extends SpatieRole
{
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, config('permission.column_names.team_foreign_key'));
    }

    public function team(): BelongsTo
    {
        return $this->campus();
    }
}
