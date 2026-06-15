<?php

declare(strict_types=1);

namespace Modules\Enrollment\Models;

use App\Models\Campus;
use App\Models\Term;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EnrollmentPeriod extends Model
{
    protected $fillable = ['campus_id', 'term_id', 'name', 'opens_at', 'closes_at', 'active', 'policies'];

    protected $attributes = ['active' => true];

    protected function casts(): array
    {
        return ['opens_at' => 'datetime', 'closes_at' => 'datetime', 'active' => 'boolean', 'policies' => 'array'];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}
