<?php

declare(strict_types=1);

namespace Modules\Classroom\Models;

use App\Models\Room;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ClassMeeting extends Model
{
    protected $fillable = ['class_offering_id', 'room_id', 'day_of_week', 'meeting_date', 'starts_at', 'ends_at', 'recurs_from', 'recurs_until', 'online_meeting_url', 'cancelled'];

    protected $attributes = ['cancelled' => false];

    protected function casts(): array
    {
        return ['meeting_date' => 'date', 'recurs_from' => 'date', 'recurs_until' => 'date', 'cancelled' => 'boolean'];
    }

    public function classOffering(): BelongsTo
    {
        return $this->belongsTo(ClassOffering::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
