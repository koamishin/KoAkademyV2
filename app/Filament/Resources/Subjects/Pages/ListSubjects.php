<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects\Pages;

use App\Filament\Resources\Curricula\CurriculumResource;
use App\Filament\Resources\Subjects\SubjectResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListSubjects extends ListRecords
{
    protected static string $resource = SubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageCurricula')
                ->label('Manage curricula')
                ->icon('heroicon-o-rectangle-stack')
                ->color('gray')
                ->url(CurriculumResource::getUrl('index', ['tenant' => filament()->getTenant()])),
            CreateAction::make()
                ->label('Build curriculum')
                ->icon('heroicon-o-sparkles'),
        ];
    }
}
