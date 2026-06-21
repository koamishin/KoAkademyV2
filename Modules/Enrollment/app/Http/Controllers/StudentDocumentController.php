<?php

declare(strict_types=1);

namespace Modules\Enrollment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Modules\Enrollment\Http\Requests\StoreStudentDocumentRequest;
use Modules\Enrollment\Http\Requests\UpdateStudentDocumentRequest;
use Modules\Enrollment\Models\StudentDocument;
use Modules\Enrollment\Support\AdminStudentAuthorizer;

final class StudentDocumentController extends Controller
{
    public function store(
        StoreStudentDocumentRequest $request,
        Campus $campus,
        Person $student,
        AdminStudentAuthorizer $authorizer,
    ): RedirectResponse {
        $authorizer->abortUnlessCanManage($request->user(), $campus);
        $authorizer->abortUnlessStudentBelongsToCampus($student, $campus);

        $validated = $request->validated();
        $file = $request->file('file');
        $path = $file->store("student-documents/{$campus->id}/{$student->id}", 'local');

        StudentDocument::query()->create([
            'campus_id' => $campus->id,
            'student_id' => $student->id,
            'document_type' => $validated['document_type'],
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'size' => $file->getSize(),
            'issued_on' => $validated['issued_on'] ?? null,
            'expires_on' => $validated['expires_on'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('status', 'Student document uploaded.');
    }

    public function update(
        UpdateStudentDocumentRequest $request,
        Campus $campus,
        Person $student,
        StudentDocument $document,
        AdminStudentAuthorizer $authorizer,
    ): RedirectResponse {
        $authorizer->abortUnlessCanManage($request->user(), $campus);
        $authorizer->abortUnlessStudentBelongsToCampus($student, $campus);
        abort_unless($document->student_id === $student->id && (int) $document->campus_id === (int) $campus->id, 404);

        $validated = $request->validated();

        $document->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $document->notes,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Student document reviewed.');
    }
}
