<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Institution extends Model
{
    protected $fillable = ['name', 'code', 'timezone', 'locale', 'status', 'settings'];

    protected $attributes = ['timezone' => 'Asia/Manila', 'locale' => 'en', 'status' => 'active'];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function campuses(): HasMany
    {
        return $this->hasMany(Campus::class);
    }
}
