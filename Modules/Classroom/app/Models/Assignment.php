<?php

declare(strict_types=1);

namespace Modules\Classroom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Assignment extends Model
{
    protected $fillable = ['class_offering_id', 'author_id', 'title', 'instructions', 'points', 'due_at', 'publish_at', 'published_at', 'status'];

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return ['points' => 'decimal:2', 'due_at' => 'datetime', 'publish_at' => 'datetime', 'published_at' => 'datetime'];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function classOffering(): BelongsTo
    {
        return $this->belongsTo(ClassOffering::class);
    }
}
