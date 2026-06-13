<?php

namespace Modules\Admissions\Filament\Resources\ApplicationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Admissions\Filament\Resources\ApplicationResource;

final class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;
}
