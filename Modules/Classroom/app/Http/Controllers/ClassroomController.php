<?php

declare(strict_types=1);

namespace Modules\Classroom\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classroom\Models\ClassOffering;

final class ClassroomController extends Controller
{
    public function index(Campus $campus): Response
    {
        $person = request()->user()->person;

        return Inertia::render('classroom/Index', ['classes' => ClassOffering::query()->whereBelongsTo($campus)->where(fn ($q) => $q->where('teacher_id', $person?->id)->orWhereHas('members', fn ($m) => $m->where('person_id', $person?->id)->where('status', 'active')))->with(['teacher:id,first_name,last_name'])->latest()->get()]);
    }

    public function show(Campus $campus, ClassOffering $classOffering): Response
    {
        $person = request()->user()->person;
        abort_unless($classOffering->campus_id === $campus->getKey(), 404);
        abort_unless($classOffering->teacher_id === $person?->id || $classOffering->members()->where('person_id', $person?->id)->exists(), 403);

        return Inertia::render('classroom/Show', ['classroom' => $classOffering->load(['posts' => fn ($q) => $q->where(fn ($p) => $p->whereNull('publish_at')->orWhere('publish_at', '<=', now()))->latest(), 'assignments' => fn ($q) => $q->where('status', 'published')->latest(), 'meetings'])]);
    }
}
