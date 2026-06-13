<?php

declare(strict_types=1);

namespace Modules\Classroom\Models;

use Illuminate\Database\Eloquent\Model;

final class ClassPost extends Model
{
    protected $fillable = ['class_offering_id', 'author_id', 'type', 'title', 'body', 'publish_at', 'published_at', 'comments_enabled'];

    protected $attributes = ['type' => 'announcement', 'comments_enabled' => true];

    protected function casts(): array
    {
        return ['publish_at' => 'datetime', 'published_at' => 'datetime', 'comments_enabled' => 'boolean'];
    }
}
