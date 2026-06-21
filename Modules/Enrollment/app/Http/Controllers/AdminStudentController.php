<?php

declare(strict_types=1);

namespace Modules\Enrollment\Http\Controllers;

use App\Enums\PersonRole;
use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Person;
use App\Models\PersonRoleAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Enrollment\Http\Requests\StoreStudentRequest;
use Modules\Enrollment\Http\Requests\UpdateStudentRequest;
use Modules\Enrollment\Models\StudentDocument;
use Modules\Enrollment\Models\StudentProfile;
use Modules\Enrollment\Support\AdminStudentAuthorizer;
use Modules\Enrollment\Support\StudentRecordData;

final class AdminStudentController extends Controller
{
    public function index(Request $request, Campus $campus, AdminStudentAuthorizer $authorizer, StudentRecordData $studentRecordData): Response
    {
        $authorizer->abortUnlessCanManage($request->user(), $campus);

        return Inertia::render('enrollment/StudentRecords', $studentRecordData->index($request, $campus));
    }

    public function store(StoreStudentRequest $request, Campus $campus): RedirectResponse
    {
        $validated = $request->validated();

        $student = DB::transaction(function () use ($campus, $validated): Person {
            $student = Person::query()->create($this->personAttributes($validated));

            PersonRoleAssignment::query()->updateOrCreate(
                ['person_id' => $student->id, 'campus_id' => $campus->id, 'role' => PersonRole::Student],
                ['reference_number' => $validated['student_number'] ?? null, 'active' => true],
            );

            $this->syncGuardians($student, $validated['guardians'] ?? []);
            $this->syncProfile($student, $campus, $validated['profile'] ?? [], $validated['metadata'] ?? []);
            $this->storeDocuments($student, $campus, $validated['documents'] ?? []);

            return $student;
        });

        return to_route('admin.students.show', ['campus' => $campus, 'student' => $student])
            ->with('status', 'Student record created.');
    }

    public function show(Request $request, Campus $campus, Person $student, AdminStudentAuthorizer $authorizer, StudentRecordData $studentRecordData): Response
    {
        $authorizer->abortUnlessCanManage($request->user(), $campus);
        $authorizer->abortUnlessStudentBelongsToCampus($student, $campus);

        return Inertia::render('enrollment/StudentProfile', $studentRecordData->show($student, $campus));
    }

    public function update(UpdateStudentRequest $request, Campus $campus, Person $student, AdminStudentAuthorizer $authorizer): RedirectResponse
    {
        $authorizer->abortUnlessCanManage($request->user(), $campus);
        $authorizer->abortUnlessStudentBelongsToCampus($student, $campus);
        $validated = $request->validated();

        DB::transaction(function () use ($campus, $student, $validated): void {
            $student->update($this->personAttributes($validated));

            PersonRoleAssignment::query()->updateOrCreate(
                ['person_id' => $student->id, 'campus_id' => $campus->id, 'role' => PersonRole::Student],
                ['reference_number' => $validated['student_number'] ?? null, 'active' => true],
            );

            $this->syncGuardians($student, $validated['guardians'] ?? []);
            $this->syncProfile($student, $campus, $validated['profile'] ?? [], $validated['metadata'] ?? []);
            $this->storeDocuments($student, $campus, $validated['documents'] ?? []);
        });

        return back()->with('status', 'Student record updated.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function personAttributes(array $validated): array
    {
        return [
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'suffix' => $validated['suffix'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'sex' => $validated['sex'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'],
            'metadata' => array_filter($validated['metadata'] ?? [], fn (mixed $value): bool => filled($value)),
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  array<string, mixed>  $metadata
     */
    private function syncProfile(Person $student, Campus $campus, array $profile, array $metadata): void
    {
        if ($profile === [] && $metadata === []) {
            return;
        }

        $profile['learner_reference_number'] ??= $metadata['learner_reference_number'] ?? null;
        $profile['previous_school_name'] ??= $metadata['previous_school'] ?? null;
        $profile['emergency_contact_phone'] ??= $metadata['emergency_contact'] ?? null;

        StudentProfile::query()->updateOrCreate(
            ['person_id' => $student->id],
            array_merge(
                ['campus_id' => $campus->id],
                $this->removeBlankValues($profile),
            ),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $documents
     */
    private function storeDocuments(Person $student, Campus $campus, array $documents): void
    {
        foreach ($documents as $documentData) {
            if (! ($documentData['file'] ?? null) instanceof UploadedFile) {
                continue;
            }

            $file = $documentData['file'];
            $path = $file->store("student-documents/{$campus->id}/{$student->id}", 'local');

            StudentDocument::query()->create([
                'campus_id' => $campus->id,
                'student_id' => $student->id,
                'document_type' => $documentData['document_type'],
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
                'size' => $file->getSize(),
                'issued_on' => $documentData['issued_on'] ?? null,
                'expires_on' => $documentData['expires_on'] ?? null,
                'notes' => $documentData['notes'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function removeBlankValues(array $values): array
    {
        $filtered = [];

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $nested = $this->removeBlankValues($value);

                if ($nested !== []) {
                    $filtered[$key] = $nested;
                }

                continue;
            }

            if ($value !== null && $value !== '') {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * @param  list<array<string, mixed>>  $guardians
     */
    private function syncGuardians(Person $student, array $guardians): void
    {
        $sync = [];

        foreach ($guardians as $guardianData) {
            $guardian = filled($guardianData['id'] ?? null)
                ? Person::query()->findOrFail($guardianData['id'])
                : Person::query()->create([
                    'first_name' => $guardianData['first_name'],
                    'last_name' => $guardianData['last_name'],
                    'email' => $guardianData['email'] ?? null,
                    'phone' => $guardianData['phone'] ?? null,
                    'status' => 'active',
                ]);

            if (filled($guardianData['id'] ?? null)) {
                $guardian->update([
                    'first_name' => $guardianData['first_name'] ?? $guardian->first_name,
                    'last_name' => $guardianData['last_name'] ?? $guardian->last_name,
                    'email' => $guardianData['email'] ?? $guardian->email,
                    'phone' => $guardianData['phone'] ?? $guardian->phone,
                ]);
            }

            $sync[$guardian->id] = [
                'relationship' => $guardianData['relationship'],
                'is_primary' => (bool) ($guardianData['is_primary'] ?? false),
                'has_portal_access' => (bool) ($guardianData['has_portal_access'] ?? true),
            ];
        }

        $student->guardians()->sync($sync);
    }
}
