<?php

declare(strict_types=1);

namespace Modules\Classroom\Listeners;

use Modules\Classroom\Actions\SyncEnrollmentRoster;
use Modules\Enrollment\Events\EnrollmentApproved;

final readonly class SyncApprovedEnrollmentRoster
{
    public function __construct(private SyncEnrollmentRoster $syncRoster) {}

    public function handle(EnrollmentApproved $event): void
    {
        $this->syncRoster->execute($event->enrollment);
    }
}
