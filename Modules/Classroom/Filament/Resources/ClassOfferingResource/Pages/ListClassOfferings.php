<?php

namespace Modules\Classroom\Filament\Resources\ClassOfferingResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Classroom\Filament\Resources\ClassOfferingResource;

final class ListClassOfferings extends ListRecords
{
    protected static string $resource = ClassOfferingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
