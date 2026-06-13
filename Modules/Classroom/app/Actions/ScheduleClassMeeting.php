<?php

declare(strict_types=1);

namespace Modules\Classroom\Actions;

use Illuminate\Validation\ValidationException;
use Modules\Classroom\Models\ClassMeeting;
use Modules\Classroom\Models\ClassOffering;

final class ScheduleClassMeeting
{
    public function execute(ClassOffering $offering, array $attributes): ClassMeeting
    {
        $conflictQuery = ClassMeeting::query()->where('cancelled', false);
        $conflictQuery->where(function ($query) use ($attributes, $offering): void {
            if (! empty($attributes['room_id'])) {
                $query->where('room_id', $attributes['room_id']);
            }
            if ($offering->teacher_id) {
                $method = ! empty($attributes['room_id']) ? 'orWhereHas' : 'whereHas';
                $query->{$method}('classOffering', fn ($offeringQuery) => $offeringQuery->where('teacher_id', $offering->teacher_id));
            }
        });
        $conflict = $conflictQuery
            ->where(fn ($q) => isset($attributes['meeting_date']) ? $q->whereDate('meeting_date', $attributes['meeting_date']) : $q->where('day_of_week', $attributes['day_of_week']))
            ->where('starts_at', '<', $attributes['ends_at'])->where('ends_at', '>', $attributes['starts_at'])->exists();
        if ($conflict) {
            throw ValidationException::withMessages(['starts_at' => 'The room or teacher already has a class during this time.']);
        }

        return $offering->meetings()->create($attributes);
    }
}
