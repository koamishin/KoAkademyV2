<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleEnums;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admissions\Models\Application;
use Modules\Classroom\Models\ClassOffering;
use Modules\Enrollment\Models\Enrollment;

final class Campus extends Model
{
    protected $fillable = ['institution_id', 'name', 'code', 'slug', 'address', 'timezone', 'status', 'settings'];

    protected $attributes = ['timezone' => 'Asia/Manila', 'status' => 'active'];

    protected static function booted(): void
    {
        self::creating(function (self $campus): void {
            $campus->slug ??= static::uniqueSlug($campus->code ?: $campus->name);
        });

        self::created(function (self $campus): void {
            $modelHasRolesTable = config('permission.table_names.model_has_roles');
            $rolesTable = config('permission.table_names.roles');

            $superAdminUserIds = DB::table($modelHasRolesTable)
                ->join($rolesTable, "{$rolesTable}.id", '=', "{$modelHasRolesTable}.role_id")
                ->where("{$modelHasRolesTable}.model_type", User::class)
                ->where("{$rolesTable}.name", RoleEnums::SUPER_ADMIN->value)
                ->distinct()
                ->pluck("{$modelHasRolesTable}.model_id");

            User::query()
                ->whereKey($superAdminUserIds)
                ->each(fn (User $user) => $user->campusMemberships()->firstOrCreate(
                    ['campus_id' => $campus->getKey()],
                    ['role' => RoleEnums::SUPER_ADMIN],
                ));
        });
    }

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function classOfferings(): HasMany
    {
        return $this->hasMany(ClassOffering::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(CampusMembership::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'active', 'is_default'])
            ->withTimestamps();
    }

    private static function uniqueSlug(string $value): string
    {
        $baseSlug = Str::slug($value);
        $slug = $baseSlug;
        $sequence = 2;

        while (self::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$sequence}";
            $sequence++;
        }

        return $slug;
    }
}
