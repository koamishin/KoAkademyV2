<?php

declare(strict_types=1);

namespace Modules\Portal\Support;

use App\Models\Campus;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Classroom\Models\Assignment;
use Modules\Classroom\Models\ClassMeeting;
use Modules\Classroom\Models\ClassOffering;
use Modules\Classroom\Models\ClassPost;

final class FacultyDashboardData
{
    /**
     * @return array<string, mixed>
     */
    public function for(User $user, Campus $campus): array
    {
        $person = $user->person;

        if (! $person instanceof Person) {
            return [
                'faculty' => null,
                'stats' => ['classes' => 0, 'students' => 0, 'pendingGrading' => 0, 'publishedAssignments' => 0],
                'todaySchedule' => [],
                'classes' => [],
                'recentActivity' => [],
            ];
        }

        $classOfferingIds = ClassOffering::query()
            ->whereBelongsTo($campus)
            ->where('teacher_id', $person->getKey())
            ->pluck('id');

        $pendingGrading = Assignment::query()
            ->whereIn('class_offering_id', $classOfferingIds)
            ->whereHas('submissions', fn ($query) => $query->where('status', 'submitted'))
            ->count();

        return [
            'faculty' => [
                'fullName' => $person->full_name,
                'firstName' => $person->first_name,
            ],
            'stats' => [
                'classes' => $classOfferingIds->count(),
                'students' => (int) ClassOffering::query()
                    ->whereKey($classOfferingIds)
                    ->withCount(['members as active_students_count' => fn ($query) => $query->where('status', 'active')])
                    ->get()
                    ->sum('active_students_count'),
                'pendingGrading' => $pendingGrading,
                'publishedAssignments' => Assignment::query()
                    ->whereIn('class_offering_id', $classOfferingIds)
                    ->where('status', 'published')
                    ->count(),
            ],
            'todaySchedule' => $this->todaySchedule($classOfferingIds),
            'classes' => ClassOffering::query()
                ->whereKey($classOfferingIds)
                ->with(['subject:id,name,code', 'section:id,name,code'])
                ->withCount(['members as active_students_count' => fn ($query) => $query->where('status', 'active')])
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (ClassOffering $classOffering): array => [
                    'id' => $classOffering->id,
                    'name' => $classOffering->subject?->name ?? $classOffering->name,
                    'code' => $classOffering->subject?->code ?? $classOffering->code,
                    'section' => $classOffering->section?->name,
                    'status' => $classOffering->status,
                    'students' => (int) $classOffering->active_students_count,
                ])
                ->all(),
            'recentActivity' => ClassPost::query()
                ->whereIn('class_offering_id', $classOfferingIds)
                ->with(['classOffering:id,name,subject_id', 'classOffering.subject:id,name,code'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (ClassPost $classPost): array => [
                    'id' => $classPost->id,
                    'title' => $classPost->title ?: str($classPost->body)->limit(48)->toString(),
                    'type' => $classPost->type,
                    'className' => $classPost->classOffering?->subject?->name ?? $classPost->classOffering?->name,
                    'createdAt' => $classPost->created_at?->toISOString(),
                ])
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, int>  $classOfferingIds
     * @return list<array<string, mixed>>
     */
    private function todaySchedule($classOfferingIds): array
    {
        if ($classOfferingIds->isEmpty()) {
            return [];
        }

        $today = Carbon::now();
        $dayOfWeek = $today->dayOfWeekIso;

        return ClassMeeting::query()
            ->whereIn('class_offering_id', $classOfferingIds)
            ->where('cancelled', false)
            ->where(function ($query) use ($dayOfWeek, $today): void {
                $query->where(function ($query) use ($dayOfWeek, $today): void {
                    $query->where('day_of_week', $dayOfWeek)
                        ->where(fn ($query) => $query->whereNull('recurs_from')->orWhere('recurs_from', '<=', $today))
                        ->where(fn ($query) => $query->whereNull('recurs_until')->orWhere('recurs_until', '>=', $today));
                })->orWhere('meeting_date', $today->toDateString());
            })
            ->with(['classOffering:id,name,code,subject_id', 'classOffering.subject:id,name,code', 'room:id,name'])
            ->orderBy('starts_at')
            ->get()
            ->map(fn (ClassMeeting $classMeeting): array => [
                'id' => $classMeeting->id,
                'className' => $classMeeting->classOffering?->subject?->name ?? $classMeeting->classOffering?->name,
                'classCode' => $classMeeting->classOffering?->subject?->code ?? $classMeeting->classOffering?->code,
                'startsAt' => $classMeeting->starts_at,
                'endsAt' => $classMeeting->ends_at,
                'roomName' => $classMeeting->room?->name,
            ])
            ->all();
    }
}
