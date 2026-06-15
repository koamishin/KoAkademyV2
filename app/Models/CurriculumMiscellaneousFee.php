<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CurriculumMiscellaneousFee extends Model
{
    protected $fillable = [
        'curriculum_id', 'code', 'name', 'description', 'amount', 'is_active', 'position',
    ];

    protected $attributes = ['is_active' => true, 'position' => 1];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }
}
