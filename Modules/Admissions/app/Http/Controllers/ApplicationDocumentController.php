<?php

declare(strict_types=1);

namespace Modules\Admissions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\CurrentCampus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Modules\Admissions\Http\Requests\StoreApplicationDocumentRequest;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\ApplicationDocument;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ApplicationDocumentController extends Controller
{
    public function store(StoreApplicationDocumentRequest $request, Application $application): RedirectResponse
    {
        abort_unless($application->campus_id === app(CurrentCampus::class)->id(), 404);
        abort_unless($application->person->user_id === $request->user()->id, 403);

        $file = $request->file('document');
        $path = $file->store("applications/{$application->public_id}", 'local');

        $application->documents()->create([
            'requirement_key' => $request->validated('requirement_key'),
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function __invoke(ApplicationDocument $applicationDocument): StreamedResponse
    {
        $application = $applicationDocument->application()->with('person.user')->firstOrFail();
        abort_unless($application->campus_id === app(CurrentCampus::class)->id(), 404);
        $user = request()->user();

        abort_unless(
            $application->person->user_id === $user->id
            || $user->hasAnyRole(['super_admin', 'school_admin', 'admissions_officer']),
            403,
        );

        return Storage::disk($applicationDocument->disk)
            ->download($applicationDocument->path, $applicationDocument->original_name);
    }
}
