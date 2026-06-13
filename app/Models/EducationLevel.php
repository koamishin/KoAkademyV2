<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class EducationLevel extends Model
{
    protected $fillable = ['institution_id', 'name', 'code', 'category', 'sequence', 'features', 'status'];

    protected $attributes = ['sequence' => 1, 'status' => 'active'];

    protected function casts(): array
    {
        return ['features' => 'array'];
    }
}
