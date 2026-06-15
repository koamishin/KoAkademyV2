<?php

declare(strict_types=1);

namespace App\Filament\Resources\Curricula\Pages;

use App\Filament\Resources\Curricula\CurriculumResource;
use App\Filament\Resources\Subjects\SubjectResource;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

final class ListCurricula extends ListRecords
{
    protected static string $resource = CurriculumResource::class;

    protected static ?string $title = 'Curricula';

    public function getSubheading(): ?string
    {
        return 'Review and refine the official or custom structures used for enrollment.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToSubjects')
                ->label('Back to subjects')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(SubjectResource::getUrl('index', ['tenant' => Filament::getTenant()])),
            Action::make('buildCurriculum')
                ->label('Build curriculum')
                ->icon('heroicon-o-sparkles')
                ->url(SubjectResource::getUrl('create', ['tenant' => Filament::getTenant()])),
        ];
    }
}
