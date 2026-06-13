<?php

declare(strict_types=1);

namespace Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ApplicationDocument extends Model
{
    protected $fillable = ['application_id', 'requirement_key', 'disk', 'path', 'original_name', 'mime_type', 'size', 'status'];

    protected $hidden = ['path'];

    protected $attributes = ['disk' => 'local', 'status' => 'pending'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
