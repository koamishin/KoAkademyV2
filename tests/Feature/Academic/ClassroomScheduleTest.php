<?php

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\Room;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Validation\ValidationException;
use Modules\Classroom\Actions\ScheduleClassMeeting;
use Modules\Classroom\Models\ClassMeeting;
use Modules\Classroom\Models\ClassOffering;

test('a room cannot be double booked', function (): void {
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => 'KO']);
    $campus = Campus::query()->create(['institution_id' => $institution->id, 'name' => 'Main', 'code' => 'MAIN']);
    $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026-2027', 'starts_on' => '2026-06-01', 'ends_on' => '2027-03-31']);
    $term = Term::query()->create(['academic_year_id' => $year->id, 'name' => 'First Term', 'code' => 'T1', 'sequence' => 1, 'starts_on' => '2026-06-01', 'ends_on' => '2026-10-31']);
    $subject = Subject::query()->create(['institution_id' => $institution->id, 'name' => 'Mathematics', 'code' => 'MATH']);
    $room = Room::query()->create(['campus_id' => $campus->id, 'name' => 'Room 101', 'code' => 'R101']);
    $existing = ClassOffering::query()->create(['campus_id' => $campus->id, 'term_id' => $term->id, 'subject_id' => $subject->id, 'name' => 'Math A', 'code' => 'MATH-A']);
    $target = ClassOffering::query()->create(['campus_id' => $campus->id, 'term_id' => $term->id, 'subject_id' => $subject->id, 'name' => 'Math B', 'code' => 'MATH-B']);
    ClassMeeting::query()->create(['class_offering_id' => $existing->id, 'room_id' => $room->id, 'day_of_week' => 1, 'starts_at' => '08:00', 'ends_at' => '09:00']);

    expect(fn () => app(ScheduleClassMeeting::class)->execute($target, ['room_id' => $room->id, 'day_of_week' => 1, 'starts_at' => '08:30', 'ends_at' => '09:30']))
        ->toThrow(ValidationException::class);
});
