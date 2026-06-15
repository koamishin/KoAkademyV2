<?php

declare(strict_types=1);

namespace Modules\Admissions\Actions;

use App\Enums\PersonRole;
use App\Enums\RoleEnums;
use App\Models\PersonRoleAssignment;
use App\Models\User;
use App\Notifications\AcademicStatusNotification;
use Illuminate\Support\Facades\DB;
use Modules\Admissions\Enums\ApplicationStatus;
use Modules\Admissions\Events\ApplicationAccepted;
use Modules\Admissions\Models\Application;

final class AcceptApplication
{
    public function execute(Application $application, User $actor, ?string $notes = null): Application
    {
        return DB::transaction(function () use ($application, $actor, $notes): Application {
            $application->refresh();
            $from = $application->status;

            if ($from === ApplicationStatus::Accepted) {
                return $application;
            }

            $application->update([
                'status' => ApplicationStatus::Accepted,
                'decided_by' => $actor->id,
                'decided_at' => now(),
                'decision_notes' => $notes,
            ]);

            PersonRoleAssignment::query()->updateOrCreate(
                ['person_id' => $application->person_id, 'campus_id' => $application->period->campus_id, 'role' => PersonRole::Student],
                ['active' => true],
            );

            if ($application->person->user) {
                $application->person->user->campusMemberships()
                    ->where('campus_id', '!=', $application->campus_id)
                    ->whereNotIn('role', RoleEnums::administrativeValues())
                    ->update(['active' => false, 'is_default' => false]);

                $application->person->user->campusMemberships()->updateOrCreate(
                    ['campus_id' => $application->campus_id],
                    [
                        'role' => RoleEnums::STUDENT,
                        'active' => true,
                        'is_default' => true,
                    ],
                );
            }

            $application->histories()->create([
                'from_status' => $from->value,
                'to_status' => ApplicationStatus::Accepted->value,
                'changed_by' => $actor->id,
                'notes' => $notes,
            ]);

            ApplicationAccepted::dispatch($application);
            $application->person->user?->notify((new AcademicStatusNotification([
                'type' => 'application.accepted',
                'title' => 'Application accepted',
                'message' => "Application {$application->application_number} has been accepted.",
                'url' => route('applications.index', ['campus' => $application->campus]),
            ]))->afterCommit());

            return $application->refresh();
        });
    }
}
