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

final class AdminClassOperationsController extends Controller
{
    public function __invoke(Campus $campus, PortalRoleResolver $portalRoleResolver): Response
    {
        abort_unless($portalRoleResolver->resolve(request()->user(), $campus) === PortalRole::Admin, 403);

        return Inertia::render('classroom/AdminOperations', [
            'classes' => ClassOffering::query()
                ->whereBelongsTo($campus)
                ->with(['subject:id,name,code', 'teacher:id,first_name,middle_name,last_name,suffix', 'section:id,name,code'])
                ->withCount(['members as active_students_count' => fn ($query) => $query->where('status', 'active')])
                ->latest()
                ->paginate(15)
                ->through(fn (ClassOffering $classOffering): array => [
                    'id' => $classOffering->id,
                    'name' => $classOffering->subject?->name ?? $classOffering->name,
                    'code' => $classOffering->subject?->code ?? $classOffering->code,
                    'section' => $classOffering->section?->name,
                    'teacher' => $classOffering->teacher?->full_name,
                    'status' => $classOffering->status,
                    'students' => (int) $classOffering->active_students_count,
                ]),
        ]);
    }
}
