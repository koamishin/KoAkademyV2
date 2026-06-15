<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleEnums;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        static::creating(function (self $campus): void {
            $campus->slug ??= static::uniqueSlug($campus->code ?: $campus->name);
        });

        static::created(function (self $campus): void {
            User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', RoleEnums::SUPER_ADMIN->value))
                ->each(fn (User $user) => $user->campusMemberships()->create([
                    'campus_id' => $campus->getKey(),
                    'role' => RoleEnums::SUPER_ADMIN,
                ]));
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

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$sequence}";
            $sequence++;
        }

        return $slug;
    }
}
