<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Campus extends Model
{
    protected $fillable = ['institution_id', 'name', 'code', 'address', 'timezone', 'status', 'settings'];

    protected $attributes = ['timezone' => 'Asia/Manila', 'status' => 'active'];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }
}
