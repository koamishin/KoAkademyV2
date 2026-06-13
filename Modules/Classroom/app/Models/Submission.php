<?php

declare(strict_types=1);

namespace Modules\Classroom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Submission extends Model
{
    protected $fillable = ['assignment_id', 'student_id', 'body', 'status', 'submitted_at', 'returned_at', 'score', 'feedback', 'graded_by'];

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'returned_at' => 'datetime', 'score' => 'decimal:2'];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }
}
