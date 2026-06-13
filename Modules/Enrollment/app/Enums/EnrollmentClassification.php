<?php

declare(strict_types=1);

namespace Modules\Enrollment\Enums;

enum EnrollmentClassification: string
{
    case NewStudent = 'new';
    case Continuing = 'continuing';
    case Transferee = 'transferee';
    case Returning = 'returning';
    case CrossEnrolled = 'cross_enrolled';
}
