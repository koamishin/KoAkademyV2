<?php

declare(strict_types=1);

namespace Modules\Portal\Support;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Classroom\Models\Assignment;
use Modules\Classroom\Models\ClassMeeting;
use Modules\Classroom\Models\ClassMember;
use Modules\Classroom\Models\ClassPost;
use Modules\Enrollment\Models\Enrollment;

final class StudentDashboardData
{
    /**
     * @return array<string, mixed>
     */
    public function for(User $user, Campus $campus): array
    {
        $person = $user->person;

        if (! $person instanceof Person) {
            return $this->empty();
        }

        $classOfferingIds = ClassMember::query()
            ->where('person_id', $person->getKey())
            ->where('status', 'active')
            ->pluck('class_offering_id');

        $academicContext = $this->academicContext();
        $enrollment = $this->enrollment($person, $campus, $academicContext);

        return [
            'student' => $this->student($person),
            'academicContext' => $academicContext,
            'enrollment' => $enrollment,
            'todaySchedule' => $this->todaySchedule($classOfferingIds),
            'upcomingAssignments' => $this->upcomingAssignments($classOfferingIds, $person),
            'gradeSummary' => $this->gradeSummary($classOfferingIds, $person),
            'recentAnnouncements' => $this->recentAnnouncements($classOfferingIds),
            'stats' => $this->stats($classOfferingIds, $person),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function empty(): array
    {
        return [
            'student' => null,
            'academicContext' => null,
            'enrollment' => null,
            'todaySchedule' => [],
            'upcomingAssignments' => [],
            'gradeSummary' => [],
            'recentAnnouncements' => [],
            'stats' => [
                'totalClasses' => 0,
                'totalUnits' => 0,
                'pendingAssignments' => 0,
                'unreadAnnouncements' => 0,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function student(Person $person): array
    {
        $studentRole = $person->roles()
            ->where('role', 'student')
            ->where('active', true)
            ->first();

        return [
            'firstName' => $person->first_name,
            'lastName' => $person->last_name,
            'fullName' => $person->full_name,
            'studentNumber' => $studentRole?->reference_number,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function academicContext(): ?array
    {
        $academicYear = AcademicYear::query()
            ->where('is_current', true)
            ->with(['terms' => fn ($query) => $query->orderBy('sequence')])
            ->first();

        if (! $academicYear) {
            return null;
        }

        $currentTerm = $academicYear->terms
            ->first(fn ($term): bool => $term->status === 'active')
            ?? $academicYear->terms->first();

        return [
            'academicYearName' => $academicYear->name,
            'termName' => $currentTerm?->name,
            'termId' => $currentTerm?->id,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $academicContext
     * @return array<string, mixed>|null
     */
    private function enrollment(Person $person, Campus $campus, ?array $academicContext): ?array
    {
        if (! $academicContext || ! $academicContext['termId']) {
            return null;
        }

        $enrollment = Enrollment::query()
            ->whereBelongsTo($campus)
            ->where('student_id', $person->getKey())
            ->whereHas('period', fn ($query) => $query->where('term_id', $academicContext['termId']))
            ->with(['section:id,name,code', 'subjects'])
            ->latest()
            ->first();

        if (! $enrollment) {
            return null;
        }

        return [
            'status' => $enrollment->status->value,
            'sectionName' => $enrollment->section?->name,
            'subjectsCount' => $enrollment->subjects->count(),
            'studentNumber' => $enrollment->student_number,
        ];
    }

    /**
     * @param  Collection<int, int>  $classOfferingIds
     * @return list<array<string, mixed>>
     */
    private function todaySchedule(Collection $classOfferingIds): array
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
                'subjectName' => $classMeeting->classOffering?->subject?->name ?? $classMeeting->classOffering?->name,
                'subjectCode' => $classMeeting->classOffering?->subject?->code ?? $classMeeting->classOffering?->code,
                'startsAt' => $classMeeting->starts_at,
                'endsAt' => $classMeeting->ends_at,
                'roomName' => $classMeeting->room?->name,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, int>  $classOfferingIds
     * @return list<array<string, mixed>>
     */
    private function upcomingAssignments(Collection $classOfferingIds, Person $person): array
    {
        if ($classOfferingIds->isEmpty()) {
            return [];
        }

        return Assignment::query()
            ->whereIn('class_offering_id', $classOfferingIds)
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('due_at')->orWhere('due_at', '>=', Carbon::now()->subDay()))
            ->with([
                'classOffering:id,name,subject_id',
                'classOffering.subject:id,name,code',
                'submissions' => fn ($query) => $query->where('student_id', $person->getKey())->select(['id', 'assignment_id', 'status', 'score']),
            ])
            ->orderBy('due_at')
            ->limit(5)
            ->get()
            ->map(fn (Assignment $assignment): array => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'subjectName' => $assignment->classOffering?->subject?->name ?? $assignment->classOffering?->name,
                'subjectCode' => $assignment->classOffering?->subject?->code,
                'dueAt' => $assignment->due_at?->toISOString(),
                'points' => $assignment->points,
                'submissionStatus' => $assignment->submissions->first()?->status,
                'submissionScore' => $assignment->submissions->first()?->score,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, int>  $classOfferingIds
     * @return list<array<string, mixed>>
     */
    private function gradeSummary(Collection $classOfferingIds, Person $person): array
    {
        if ($classOfferingIds->isEmpty()) {
            return [];
        }

        return Assignment::query()
            ->whereIn('assignments.class_offering_id', $classOfferingIds)
            ->where('assignments.status', 'published')
            ->join('submissions', function ($join) use ($person): void {
                $join->on('submissions.assignment_id', '=', 'assignments.id')
                    ->where('submissions.student_id', '=', $person->getKey())
                    ->whereNotNull('submissions.returned_at');
            })
            ->join('class_offerings', 'class_offerings.id', '=', 'assignments.class_offering_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'class_offerings.subject_id')
            ->groupBy('assignments.class_offering_id', 'class_offerings.name', 'subjects.name', 'subjects.code')
            ->selectRaw('
                assignments.class_offering_id,
                class_offerings.name as class_name,
                subjects.name as subject_name,
                subjects.code as subject_code,
                COUNT(submissions.id) as graded_count,
                SUM(submissions.score) as total_score,
                SUM(assignments.points) as total_points
            ')
            ->get()
            ->map(fn ($row): array => [
                'classOfferingId' => $row->class_offering_id,
                'className' => $row->subject_name ?? $row->class_name,
                'subjectCode' => $row->subject_code,
                'gradedCount' => (int) $row->graded_count,
                'totalScore' => (float) $row->total_score,
                'totalPoints' => (float) $row->total_points,
                'percentage' => $row->total_points > 0 ? round(($row->total_score / $row->total_points) * 100, 1) : 0,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, int>  $classOfferingIds
     * @return list<array<string, mixed>>
     */
    private function recentAnnouncements(Collection $classOfferingIds): array
    {
        if ($classOfferingIds->isEmpty()) {
            return [];
        }

        return ClassPost::query()
            ->whereIn('class_offering_id', $classOfferingIds)
            ->where('type', 'announcement')
            ->where(fn ($query) => $query->whereNull('publish_at')->orWhere('publish_at', '<=', Carbon::now()))
            ->with(['classOffering:id,name,subject_id', 'classOffering.subject:id,name,code'])
            ->latest('published_at')
            ->limit(5)
            ->get()
            ->map(fn (ClassPost $classPost): array => [
                'id' => $classPost->id,
                'title' => $classPost->title,
                'body' => str($classPost->body)->limit(120)->toString(),
                'subjectName' => $classPost->classOffering?->subject?->name ?? $classPost->classOffering?->name,
                'subjectCode' => $classPost->classOffering?->subject?->code,
                'publishedAt' => ($classPost->published_at ?? $classPost->created_at)->toISOString(),
                'classOfferingId' => $classPost->class_offering_id,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, int>  $classOfferingIds
     * @return array<string, int>
     */
    private function stats(Collection $classOfferingIds, Person $person): array
    {
        $pendingAssignments = 0;

        if ($classOfferingIds->isNotEmpty()) {
            $pendingAssignments = Assignment::query()
                ->whereIn('class_offering_id', $classOfferingIds)
                ->where('status', 'published')
                ->where('due_at', '>=', Carbon::now())
                ->whereDoesntHave('submissions', fn ($query) => $query->where('student_id', $person->getKey())->whereIn('status', ['submitted', 'graded']))
                ->count();
        }

        return [
            'totalClasses' => $classOfferingIds->count(),
            'totalUnits' => 0,
            'pendingAssignments' => $pendingAssignments,
            'unreadAnnouncements' => $classOfferingIds->isNotEmpty()
                ? ClassPost::query()
                    ->whereIn('class_offering_id', $classOfferingIds)
                    ->where('type', 'announcement')
                    ->where(fn ($query) => $query->whereNull('publish_at')->orWhere('publish_at', '<=', Carbon::now()))
                    ->where('created_at', '>=', Carbon::now()->subWeek())
                    ->count()
                : 0,
        ];
    }
}
