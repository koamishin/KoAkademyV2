<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Section extends Model
{
    protected $fillable = ['campus_id', 'program_id', 'term_id', 'name', 'code', 'year_level', 'capacity', 'status'];

    protected $attributes = ['status' => 'active'];
}
