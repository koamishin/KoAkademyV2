<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Curriculum extends Model
{
    protected $fillable = ['program_id', 'name', 'code', 'effective_year', 'status'];

    protected $attributes = ['status' => 'draft'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CurriculumItem::class);
    }
}
