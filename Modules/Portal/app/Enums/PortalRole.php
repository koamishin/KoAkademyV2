<?php

declare(strict_types=1);

namespace Modules\Portal\Enums;

enum PortalRole: string
{
    case Admin = 'admin';
    case Faculty = 'faculty';
    case Student = 'student';
    case Applicant = 'applicant';
    case Guardian = 'guardian';
    case Unknown = 'unknown';
}
