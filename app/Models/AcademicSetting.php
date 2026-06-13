<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AcademicSetting extends Model
{
    protected $fillable = ['campus_id', 'key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }
}
