<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Enrollment\Models\Enrollment;

final class Person extends Model
{
    protected $fillable = [
        'user_id', 'first_name', 'middle_name', 'last_name', 'suffix', 'birth_date',
        'sex', 'email', 'phone', 'address', 'status', 'metadata',
    ];

    protected $attributes = ['status' => 'active'];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'metadata' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(PersonRoleAssignment::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'guardian_student', 'guardian_id', 'student_id')
            ->withPivot(['relationship', 'is_primary', 'has_portal_access'])
            ->withTimestamps();
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'guardian_student', 'student_id', 'guardian_id')
            ->withPivot(['relationship', 'is_primary', 'has_portal_access'])
            ->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return collect([$this->first_name, $this->middle_name, $this->last_name, $this->suffix])
            ->filter()
            ->implode(' ');
    }
}
