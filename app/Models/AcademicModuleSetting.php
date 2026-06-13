<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AcademicModuleSetting extends Model
{
    protected $table = 'academic_module_settings';

    protected $fillable = ['module', 'enabled', 'settings'];

    protected $attributes = ['enabled' => true];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'settings' => 'array'];
    }
}
