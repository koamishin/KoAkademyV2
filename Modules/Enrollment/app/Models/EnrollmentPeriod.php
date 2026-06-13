<?php

declare(strict_types=1);

namespace Modules\Enrollment\Models;

use Illuminate\Database\Eloquent\Model;

final class EnrollmentPeriod extends Model
{
    protected $fillable = ['campus_id', 'term_id', 'name', 'opens_at', 'closes_at', 'active', 'policies'];

    protected $attributes = ['active' => true];

    protected function casts(): array
    {
        return ['opens_at' => 'datetime', 'closes_at' => 'datetime', 'active' => 'boolean', 'policies' => 'array'];
    }
}
