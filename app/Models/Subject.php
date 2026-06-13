<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Subject extends Model
{
    protected $fillable = [
        'institution_id', 'name', 'code', 'description', 'subject_type', 'default_credit_units',
        'default_contact_hours', 'default_lab_hours', 'default_competency_hours', 'status',
    ];

    protected $attributes = ['subject_type' => 'academic', 'status' => 'active'];

    protected function casts(): array
    {
        return [
            'default_credit_units' => 'decimal:2',
            'default_contact_hours' => 'decimal:2',
            'default_lab_hours' => 'decimal:2',
            'default_competency_hours' => 'decimal:2',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
