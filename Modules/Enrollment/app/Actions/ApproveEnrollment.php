<?php

declare(strict_types=1);

namespace Modules\Enrollment\Actions;

use App\Models\User;
use App\Notifications\AcademicStatusNotification;
use Illuminate\Support\Facades\DB;
use Modules\Enrollment\Enums\EnrollmentStatus;
use Modules\Enrollment\Events\EnrollmentApproved;
use Modules\Enrollment\Models\Enrollment;

final class ApproveEnrollment
{
    public function __construct(private readonly GenerateStudentNumber $generateStudentNumber) {}

    public function execute(Enrollment $enrollment, User $actor): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $actor): Enrollment {
            $enrollment->refresh();
            if ($enrollment->status === EnrollmentStatus::Approved) {
                return $enrollment;
            }
            $capacity = $enrollment->section?->capacity;
            if ($capacity !== null && Enrollment::query()->where('section_id', $enrollment->section_id)->where('status', EnrollmentStatus::Approved)->whereKeyNot($enrollment)->count() >= $capacity) {
                $enrollment->update(['status' => EnrollmentStatus::Waitlisted]);

                return $enrollment->refresh();
            }
            $from = $enrollment->status;
            $enrollment->update([
                'status' => EnrollmentStatus::Approved,
                'student_number' => $enrollment->student_number ?? $this->generateStudentNumber->execute($enrollment),
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);
            DB::table('enrollment_status_histories')->insert(['enrollment_id' => $enrollment->id, 'from_status' => $from->value, 'to_status' => EnrollmentStatus::Approved->value, 'changed_by' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);
            EnrollmentApproved::dispatch($enrollment);
            $enrollment->student->user?->notify((new AcademicStatusNotification([
                'type' => 'enrollment.approved', 'title' => 'Enrollment approved', 'message' => 'Your enrollment has been approved.', 'url' => route('dashboard'),
            ]))->afterCommit());

            return $enrollment->refresh();
        });
    }
}
