<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classroom\Models\Assignment;
use Modules\Classroom\Models\ClassMeeting;
use Modules\Classroom\Models\ClassMember;
use Modules\Classroom\Models\ClassPost;
use Modules\Enrollment\Models\Enrollment;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $person = $request->user()->person;

        return Inertia::render('Dashboard', $this->buildProps($person));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProps(?Person $person): array
    {
        if (! $person) {
            return $this->emptyProps();
        }

        $personId = $person->id;
        $classOfferingIds = ClassMember::query()
            ->where('person_id', $personId)
            ->where('status', 'active')
            ->pluck('class_offering_id');

        $academicContext = $this->getAcademicContext();
        $enrollment = $this->getEnrollment($personId, $academicContext);

        return [
            'student' => $this->getStudentInfo($person),
            'academicContext' => $academicContext,
            'enrollment' => $enrollment,
            'todaySchedule' => $this->getTodaySchedule($classOfferingIds),
            'upcomingAssignments' => $this->getUpcomingAssignments($classOfferingIds, $personId),
            'gradeSummary' => $this->getGradeSummary($classOfferingIds, $personId),
            'recentAnnouncements' => $this->getRecentAnnouncements($classOfferingIds),
            'stats' => $this->getStats($classOfferingIds, $personId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyProps(): array
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
    private function getStudentInfo(Person $person): array
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
    private function getAcademicContext(): ?array
    {
        $academicYear = AcademicYear::query()
            ->where('is_current', true)
            ->with(['terms' => fn ($q) => $q->orderBy('sequence')])
            ->first();

        if (! $academicYear) {
            return null;
        }

        $currentTerm = $academicYear->terms
            ->first(fn ($term) => $term->status === 'active')
            ?? $academicYear->terms->first();

        return [
            'academicYearName' => $academicYear->name,
            'termName' => $currentTerm?->name,
            'termId' => $currentTerm?->id,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getEnrollment(int $personId, ?array $academicContext): ?array
    {
        if (! $academicContext || ! $academicContext['termId']) {
            return null;
        }

        $enrollment = Enrollment::query()
            ->where('student_id', $personId)
            ->whereHas('period', fn ($q) => $q->where('term_id', $academicContext['termId']))
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
    private function getTodaySchedule($classOfferingIds): array
    {
        if ($classOfferingIds->isEmpty()) {
            return [];
        }

        $today = Carbon::now();
        $dayOfWeek = $today->dayOfWeekIso;

        return ClassMeeting::query()
            ->whereIn('class_offering_id', $classOfferingIds)
            ->where('cancelled', false)
            ->where(function ($q) use ($dayOfWeek, $today): void {
                $q->where(function ($q) use ($dayOfWeek, $today): void {
                    $q->where('day_of_week', $dayOfWeek)
                        ->where(fn ($q) => $q->whereNull('recurs_from')->orWhere('recurs_from', '<=', $today))
                        ->where(fn ($q) => $q->whereNull('recurs_until')->orWhere('recurs_until', '>=', $today));
                })->orWhere('meeting_date', $today->toDateString());
            })
            ->with(['classOffering:id,name,code,subject_id', 'classOffering.subject:id,name,code'])
            ->orderBy('starts_at')
            ->get()
            ->map(fn (ClassMeeting $meeting): array => [
                'id' => $meeting->id,
                'subjectName' => $meeting->classOffering?->subject?->name ?? $meeting->classOffering?->name,
                'subjectCode' => $meeting->classOffering?->subject?->code ?? $meeting->classOffering?->code,
                'startsAt' => $meeting->starts_at,
                'endsAt' => $meeting->ends_at,
                'roomName' => $meeting->room_id ? $meeting->loadMissing('room:id,name')->room?->name : null,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, int>  $classOfferingIds
     * @return list<array<string, mixed>>
     */
    private function getUpcomingAssignments($classOfferingIds, int $personId): array
    {
        if ($classOfferingIds->isEmpty()) {
            return [];
        }

        return Assignment::query()
            ->whereIn('class_offering_id', $classOfferingIds)
            ->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('due_at')->orWhere('due_at', '>=', Carbon::now()->subDay()))
            ->with([
                'classOffering:id,name,subject_id',
                'classOffering.subject:id,name,code',
                'submissions' => fn ($q) => $q->where('student_id', $personId)->select(['id', 'assignment_id', 'status', 'score']),
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
    private function getGradeSummary($classOfferingIds, int $personId): array
    {
        if ($classOfferingIds->isEmpty()) {
            return [];
        }

        return Assignment::query()
            ->whereIn('assignments.class_offering_id', $classOfferingIds)
            ->where('assignments.status', 'published')
            ->join('submissions', function ($join) use ($personId): void {
                $join->on('submissions.assignment_id', '=', 'assignments.id')
                    ->where('submissions.student_id', '=', $personId)
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
    private function getRecentAnnouncements($classOfferingIds): array
    {
        if ($classOfferingIds->isEmpty()) {
            return [];
        }

        return ClassPost::query()
            ->whereIn('class_offering_id', $classOfferingIds)
            ->where('type', 'announcement')
            ->where(fn ($q) => $q->whereNull('publish_at')->orWhere('publish_at', '<=', Carbon::now()))
            ->with(['classOffering:id,name,subject_id', 'classOffering.subject:id,name,code'])
            ->latest('published_at')
            ->limit(5)
            ->get()
            ->map(fn (ClassPost $post): array => [
                'id' => $post->id,
                'title' => $post->title,
                'body' => str($post->body)->limit(120)->toString(),
                'subjectName' => $post->classOffering?->subject?->name ?? $post->classOffering?->name,
                'subjectCode' => $post->classOffering?->subject?->code,
                'publishedAt' => ($post->published_at ?? $post->created_at)->toISOString(),
                'classOfferingId' => $post->class_offering_id,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, int>  $classOfferingIds
     * @return array<string, int>
     */
    private function getStats($classOfferingIds, int $personId): array
    {
        $pendingAssignments = 0;
        $totalUnits = 0;

        if ($classOfferingIds->isNotEmpty()) {
            $pendingAssignments = Assignment::query()
                ->whereIn('class_offering_id', $classOfferingIds)
                ->where('status', 'published')
                ->where('due_at', '>=', Carbon::now())
                ->whereDoesntHave('submissions', fn ($q) => $q->where('student_id', $personId)->whereIn('status', ['submitted', 'graded']))
                ->count();
        }

        return [
            'totalClasses' => $classOfferingIds->count(),
            'totalUnits' => $totalUnits,
            'pendingAssignments' => $pendingAssignments,
            'unreadAnnouncements' => $classOfferingIds->isNotEmpty()
                ? ClassPost::query()
                    ->whereIn('class_offering_id', $classOfferingIds)
                    ->where('type', 'announcement')
                    ->where(fn ($q) => $q->whereNull('publish_at')->orWhere('publish_at', '<=', Carbon::now()))
                    ->where('created_at', '>=', Carbon::now()->subWeek())
                    ->count()
                : 0,
        ];
    }

    private function loadRoom(ClassMeeting $meeting): ?string
    {
        if ($meeting->room_id) {
            $meeting->loadMissing('room:id,name');

            return $meeting->room?->name;
        }

        return null;
    }
}
