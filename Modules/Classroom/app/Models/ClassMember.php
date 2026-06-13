<?php

declare(strict_types=1);

namespace Modules\Classroom\Models;

use Illuminate\Database\Eloquent\Model;

final class ClassMember extends Model
{
    protected $fillable = ['class_offering_id', 'person_id', 'role', 'status'];

    protected $attributes = ['role' => 'student', 'status' => 'active'];
}
