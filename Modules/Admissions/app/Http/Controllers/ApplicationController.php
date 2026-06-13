<?php

declare(strict_types=1);

namespace Modules\Admissions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Enums\ApplicationStatus;
use Modules\Admissions\Http\Requests\StoreApplicationRequest;
use Modules\Admissions\Models\AdmissionPeriod;
use Modules\Admissions\Models\Application;

final class ApplicationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admissions/Index', [
            'applications' => Application::query()
                ->whereHas('person', fn ($query) => $query->where('user_id', request()->user()->id))
                ->with(['period:id,name', 'program:id,name'])
                ->latest()
                ->get(),
            'periods' => AdmissionPeriod::query()
                ->where('active', true)
                ->where('opens_at', '<=', now())
                ->where('closes_at', '>=', now())
                ->latest()
                ->get(),
        ]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $request->validated();
            $person = Person::query()->firstOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'first_name' => $data['first_name'],
                    'middle_name' => $data['middle_name'] ?? null,
                    'last_name' => $data['last_name'],
                    'birth_date' => $data['birth_date'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'email' => $request->user()->email,
                ],
            );

            Application::query()->create([
                'person_id' => $person->id,
                'admission_period_id' => $data['admission_period_id'],
                'program_id' => $data['program_id'] ?? null,
                'answers' => $data['answers'] ?? [],
                'status' => ApplicationStatus::Submitted,
                'submitted_at' => now(),
            ]);
        });

        return to_route('applications.index')->with('success', 'Application submitted.');
    }
}
