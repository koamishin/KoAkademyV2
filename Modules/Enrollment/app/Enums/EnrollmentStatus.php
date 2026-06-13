<?php

declare(strict_types=1);

namespace Modules\Enrollment\Enums;

enum EnrollmentStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Waitlisted = 'waitlisted';
    case Approved = 'approved';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
}
