<?php

declare(strict_types=1);

namespace Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Model;

final class ApplicationForm extends Model
{
    protected $fillable = ['campus_id', 'name', 'schema', 'document_requirements', 'active'];

    protected $attributes = ['active' => true];

    protected function casts(): array
    {
        return ['schema' => 'array', 'document_requirements' => 'array', 'active' => 'boolean'];
    }
}
