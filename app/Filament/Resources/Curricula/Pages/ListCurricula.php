<?php

declare(strict_types=1);

namespace App\Filament\Resources\Curricula\Pages;

use App\Filament\Resources\Curricula\CurriculumResource;
use App\Filament\Resources\Subjects\SubjectResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

final class ListCurricula extends ListRecords
{
    protected static string $resource = CurriculumResource::class;

    protected static ?string $title = 'Curriculum Manager';

    public function getSubheading(): string
    {
        return 'Build, review, and refine the curriculum structures used for enrollment.';
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All'),
        ];

        foreach (CurriculumResource::configuredSchoolTypeOptions() as $type => $label) {
            $tabs[$type] = Tab::make($label)
                ->modifyQueryUsing(fn (Builder $query): Builder => CurriculumResource::applySchoolTypeScope($query, $type));
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Build curriculum')
                ->icon('heroicon-o-sparkles'),
            Action::make('subjectCatalog')
                ->label('Subject catalog')
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->url(SubjectResource::getUrl('index', ['tenant' => Filament::getTenant()])),
        ];
    }
}
