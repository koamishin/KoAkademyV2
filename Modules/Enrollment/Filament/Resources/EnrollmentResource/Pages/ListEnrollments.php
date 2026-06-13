<?php

namespace Modules\Enrollment\Filament\Resources\EnrollmentResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Enrollment\Filament\Resources\EnrollmentResource;

final class ListEnrollments extends ListRecords
{
    protected static string $resource = EnrollmentResource::class;
}
