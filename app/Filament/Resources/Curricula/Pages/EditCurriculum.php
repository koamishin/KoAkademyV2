<?php

declare(strict_types=1);

namespace App\Filament\Resources\Curricula\Pages;

use App\Filament\Resources\Curricula\CurriculumResource;
use App\Filament\Resources\Subjects\SubjectResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

final class EditCurriculum extends EditRecord
{
    protected static string $resource = CurriculumResource::class;

    public function getSubheading(): ?string
    {
        return "This is your institution's editable copy; its official source definition remains unchanged.";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('subjects')
                ->label('Subject catalog')
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->url(SubjectResource::getUrl('index', ['tenant' => Filament::getTenant()])),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['is_customized'] = true;

        return $data;
    }
}
