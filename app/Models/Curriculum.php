<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Curriculum extends Model
{
    protected $fillable = [
        'program_id', 'name', 'code', 'effective_year', 'template_key', 'template_version',
        'template_authority', 'template_source_url', 'is_customized', 'currency',
        'tuition_per_unit', 'laboratory_fee_per_subject', 'status',
    ];

    protected $attributes = ['is_customized' => false, 'currency' => 'PHP', 'status' => 'draft'];

    protected function casts(): array
    {
        return [
            'is_customized' => 'boolean',
            'tuition_per_unit' => 'decimal:2',
            'laboratory_fee_per_subject' => 'decimal:2',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function items(): HasMany
    {
        $relation = $this->hasMany(CurriculumItem::class);
        $relation->getQuery()
            ->orderBy('year_level')
            ->orderBy('term_sequence')
            ->orderBy('position');

        return $relation;
    }

    public function electiveGroups(): HasMany
    {
        return $this->hasMany(CurriculumElectiveGroup::class);
    }

    public function miscellaneousFees(): HasMany
    {
        return $this->hasMany(CurriculumMiscellaneousFee::class)->orderBy('position');
    }
}
