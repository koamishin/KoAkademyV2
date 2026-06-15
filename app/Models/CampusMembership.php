<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleEnums;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class CampusMembership extends Model
{
    protected $table = 'campus_user';

    protected $fillable = ['campus_id', 'user_id', 'role', 'active', 'is_default'];

    protected $attributes = ['active' => true, 'is_default' => false];

    protected static function booted(): void
    {
        static::saved(fn (self $membership): bool => $membership->synchronizePermissionRole());
        static::deleted(fn (self $membership): bool => $membership->removePermissionRole());
    }

    protected function casts(): array
    {
        return [
            'role' => RoleEnums::class,
            'active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    private function synchronizePermissionRole(): bool
    {
        $role = Role::query()
            ->whereNull('campus_id')
            ->where('name', $this->role->value)
            ->where('guard_name', 'web')
            ->first();

        if (! $role) {
            return true;
        }

        $registrar = app(PermissionRegistrar::class);
        $previousCampusId = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId($this->campus_id);
            $user = $this->user()->firstOrFail();
            $user->unsetRelation('roles')->unsetRelation('permissions');

            if ($this->active) {
                $user->syncRoles([$role]);
            } else {
                $user->syncRoles([]);
            }
        } finally {
            $registrar->setPermissionsTeamId($previousCampusId);
        }

        return true;
    }

    private function removePermissionRole(): bool
    {
        $registrar = app(PermissionRegistrar::class);
        $previousCampusId = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId($this->campus_id);
            $user = $this->user()->first();
            $user?->unsetRelation('roles')->unsetRelation('permissions');
            $user?->syncRoles([]);
        } finally {
            $registrar->setPermissionsTeamId($previousCampusId);
        }

        return true;
    }
}
