<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PersonRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PersonRoleAssignment extends Model
{
    protected $table = 'person_roles';

    protected $fillable = ['person_id', 'campus_id', 'role', 'reference_number', 'active'];

    protected $attributes = ['active' => true];

    protected function casts(): array
    {
        return ['role' => PersonRole::class, 'active' => 'boolean'];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }
}
