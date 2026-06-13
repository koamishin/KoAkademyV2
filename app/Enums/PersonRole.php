<?php

declare(strict_types=1);

namespace App\Enums;

enum PersonRole: string
{
    case Applicant = 'applicant';
    case Student = 'student';
    case Guardian = 'guardian';
    case Employee = 'employee';
}
