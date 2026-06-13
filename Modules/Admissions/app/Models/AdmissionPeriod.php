<?php

declare(strict_types=1);

namespace Modules\Admissions\Models;

use App\Models\Campus;
use App\Models\Term;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AdmissionPeriod extends Model
{
    protected $fillable = ['campus_id', 'term_id', 'application_form_id', 'name', 'opens_at', 'closes_at', 'capacity', 'active'];

    protected $attributes = ['active' => true];

    protected function casts(): array
    {
        return ['opens_at' => 'datetime', 'closes_at' => 'datetime', 'active' => 'boolean'];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function applicationForm(): BelongsTo
    {
        return $this->belongsTo(ApplicationForm::class);
    }
}
