<?php

declare(strict_types=1);

namespace Modules\Classroom\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classroom\Models\ClassOffering;
use Modules\Portal\Enums\PortalRole;
use Modules\Portal\Support\PortalRoleResolver;

final class ClassroomController extends Controller
{
    public function index(Campus $campus, PortalRoleResolver $portalRoleResolver): Response
    {
        $user = request()->user();
        $person = $user->person;
        $portalRole = $portalRoleResolver->resolve($user, $campus);

        $classes = ClassOffering::query()
            ->whereBelongsTo($campus)
            ->when($portalRole === PortalRole::Faculty, fn ($query) => $query->where('teacher_id', $person?->id))
            ->when($portalRole === PortalRole::Student, fn ($query) => $query->whereHas('members', fn ($memberQuery) => $memberQuery
                ->where('person_id', $person?->id)
                ->where('status', 'active')))
            ->when(! in_array($portalRole, [PortalRole::Admin, PortalRole::Faculty, PortalRole::Student], true), fn ($query) => $query->whereRaw('1 = 0'))
            ->with(['teacher:id,first_name,last_name', 'subject:id,name,code'])
            ->withCount(['members as active_students_count' => fn ($query) => $query->where('status', 'active')])
            ->latest()
            ->get()
            ->map(fn (ClassOffering $classOffering): array => [
                'id' => $classOffering->id,
                'name' => $classOffering->subject?->name ?? $classOffering->name,
                'code' => $classOffering->subject?->code ?? $classOffering->code,
                'status' => $classOffering->status,
                'students' => (int) $classOffering->active_students_count,
                'teacher' => $classOffering->teacher ? [
                    'first_name' => $classOffering->teacher->first_name,
                    'last_name' => $classOffering->teacher->last_name,
                ] : null,
            ]);

        return Inertia::render('classroom/Index', [
            'classes' => $classes,
            'portalRole' => $portalRole->value,
        ]);
    }

    public function show(Campus $campus, ClassOffering $classOffering, PortalRoleResolver $portalRoleResolver): Response
    {
        $user = request()->user();
        $person = $user->person;
        $portalRole = $portalRoleResolver->resolve($user, $campus);

        abort_unless($classOffering->campus_id === $campus->getKey(), 404);
        abort_unless(
            $portalRole === PortalRole::Admin
                || $classOffering->teacher_id === $person?->id
                || $classOffering->members()->where('person_id', $person?->id)->where('status', 'active')->exists(),
            403,
        );

        return Inertia::render('classroom/Show', ['classroom' => $classOffering->load(['posts' => fn ($q) => $q->where(fn ($p) => $p->whereNull('publish_at')->orWhere('publish_at', '<=', now()))->latest(), 'assignments' => fn ($q) => $q->where('status', 'published')->latest(), 'meetings'])]);
    }
}
