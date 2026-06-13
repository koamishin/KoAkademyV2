<?php

declare(strict_types=1);

namespace Modules\Classroom\Actions;

use Modules\Classroom\Models\Assignment;
use Modules\Classroom\Models\ClassPost;

final class PublishClasswork
{
    public function publishPost(ClassPost $post): ClassPost
    {
        $post->update(['published_at' => now(), 'publish_at' => null]);

        return $post->refresh();
    }

    public function publishAssignment(Assignment $assignment): Assignment
    {
        $assignment->update(['status' => 'published', 'published_at' => now(), 'publish_at' => null]);

        return $assignment->refresh();
    }
}
