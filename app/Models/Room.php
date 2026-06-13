<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Room extends Model
{
    protected $fillable = ['campus_id', 'name', 'code', 'capacity', 'room_type', 'status'];

    protected $attributes = ['room_type' => 'classroom', 'status' => 'active'];
}
