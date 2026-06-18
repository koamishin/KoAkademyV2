<?php

declare(strict_types=1);

namespace Modules\Portal\Support;

use App\Models\Campus;
use App\Models\User;
use Illuminate\Support\Carbon;
use Modules\Admissions\Enums\ApplicationStatus;
use Modules\Admissions\Models\Application;
use Modules\Classroom\Models\ClassMeeting;
use Modules\Classroom\Models\ClassOffering;
use Modules\Enrollment\Enums\EnrollmentStatus;
use Modules\Enrollment\Models\Enrollment;

final class AdminDashboardData
{
    /**
     * @return array<string, mixed>
     */
    public function for(User $user, Campus $campus): array
    {
        return [
            'admin' => [
                'name' => $user->name,
                'campusName' => $campus->name,
            ],
            'stats' => [
                'applicationsInReview' => Application::query()
                    ->whereBelongsTo($campus)
                    ->whereIn('status', [ApplicationStatus::Submitted, ApplicationStatus::UnderReview, ApplicationStatus::NeedsInformation])
                    ->count(),
                'pendingEnrollments' => Enrollment::query()
                    ->whereBelongsTo($campus)
                    ->whereIn('status', [EnrollmentStatus::Draft, EnrollmentStatus::Pending, EnrollmentStatus::Waitlisted])
                    ->count(),
                'activeClasses' => ClassOffering::query()
                    ->whereBelongsTo($campus)
                    ->where('status', 'active')
                    ->count(),
                'meetingsToday' => $this->meetingsTodayCount($campus),
            ],
            'applicationQueue' => Application::query()
                ->whereBelongsTo($campus)
                ->whereIn('status', [ApplicationStatus::Submitted, ApplicationStatus::UnderReview, ApplicationStatus::NeedsInformation])
                ->with(['person:id,first_name,last_name', 'period:id,name', 'program:id,name'])
                ->latest('submitted_at')
                ->limit(6)
                ->get()
                ->map(fn (Application $application): array => [
                    'id' => $application->id,
                    'number' => $application->application_number,
                    'studentName' => $application->person?->full_name,
                    'period' => $application->period?->name,
                    'program' => $application->program?->name,
                    'status' => $application->status->value,
                    'submittedAt' => $application->submitted_at?->toISOString(),
                ])
                ->all(),
            'enrollmentQueue' => Enrollment::query()
                ->whereBelongsTo($campus)
                ->whereIn('status', [EnrollmentStatus::Draft, EnrollmentStatus::Pending, EnrollmentStatus::Waitlisted])
                ->with(['student:id,first_name,last_name', 'period:id,name', 'curriculum:id,name'])
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (Enrollment $enrollment): array => [
                    'id' => $enrollment->id,
                    'studentName' => $enrollment->student?->full_name,
                    'studentNumber' => $enrollment->student_number,
                    'period' => $enrollment->period?->name,
                    'curriculum' => $enrollment->curriculum?->name,
                    'status' => $enrollment->status->value,
                ])
                ->all(),
            'classes' => ClassOffering::query()
                ->whereBelongsTo($campus)
                ->with(['subject:id,name,code', 'teacher:id,first_name,last_name'])
                ->withCount(['members as active_students_count' => fn ($query) => $query->where('status', 'active')])
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (ClassOffering $classOffering): array => [
                    'id' => $classOffering->id,
                    'name' => $classOffering->subject?->name ?? $classOffering->name,
                    'code' => $classOffering->subject?->code ?? $classOffering->code,
                    'teacher' => $classOffering->teacher?->full_name,
                    'status' => $classOffering->status,
                    'students' => (int) $classOffering->active_students_count,
                ])
                ->all(),
        ];
    }

    private function meetingsTodayCount(Campus $campus): int
    {
        $today = Carbon::now();
        $dayOfWeek = $today->dayOfWeekIso;

        return ClassMeeting::query()
            ->whereHas('classOffering', fn ($query) => $query->whereBelongsTo($campus))
            ->where('cancelled', false)
            ->where(function ($query) use ($dayOfWeek, $today): void {
                $query->where(function ($query) use ($dayOfWeek, $today): void {
                    $query->where('day_of_week', $dayOfWeek)
                        ->where(fn ($query) => $query->whereNull('recurs_from')->orWhere('recurs_from', '<=', $today))
                        ->where(fn ($query) => $query->whereNull('recurs_until')->orWhere('recurs_until', '>=', $today));
                })->orWhere('meeting_date', $today->toDateString());
            })
            ->count();
    }
}
