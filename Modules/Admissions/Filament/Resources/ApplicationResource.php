<?php

declare(strict_types=1);

namespace Modules\Admissions\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Admissions\Actions\AcceptApplication;
use Modules\Admissions\Enums\ApplicationStatus;
use Modules\Admissions\Filament\Resources\ApplicationResource\Pages;
use Modules\Admissions\Models\Application;

final class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Admissions';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')->options(collect(ApplicationStatus::cases())->mapWithKeys(fn ($s) => [$s->value => str($s->value)->headline()])->all())->required(),
            Textarea::make('decision_notes')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('application_number')->searchable(), TextColumn::make('person.full_name')->label('Applicant')->searchable(['first_name', 'last_name']), TextColumn::make('period.name'), TextColumn::make('program.name'), TextColumn::make('status')->badge(), TextColumn::make('submitted_at')->dateTime()->sortable(),
        ])->recordActions([
            Action::make('accept')->color('success')->requiresConfirmation()->visible(fn (Application $record) => $record->status !== ApplicationStatus::Accepted)->action(fn (Application $record, AcceptApplication $accept) => $accept->execute($record, auth()->user())),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListApplications::route('/'), 'edit' => Pages\EditApplication::route('/{record}/edit')];
    }
}
