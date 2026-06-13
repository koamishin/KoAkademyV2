<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AcademicSequence extends Model
{
    protected $fillable = ['campus_id', 'key', 'next_value'];

    protected $attributes = ['next_value' => 1];
}
