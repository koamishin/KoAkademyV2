<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects;

use App\Models\Subject;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static bool $isScopedToTenant = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Academic Setup';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('institution_id')->relationship('institution', 'name')->required(),
            TextInput::make('code')->required(), TextInput::make('name')->required(),
            Select::make('subject_type')->options(['academic' => 'Academic', 'laboratory' => 'Laboratory', 'competency' => 'TESDA Competency'])->required(),
            TextInput::make('default_credit_units')->numeric()->default(0), TextInput::make('default_contact_hours')->numeric()->default(0),
            TextInput::make('default_lab_hours')->numeric()->default(0), TextInput::make('default_competency_hours')->numeric()->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('code')->searchable(), TextColumn::make('name')->searchable()->sortable(), TextColumn::make('subject_type')->badge(), TextColumn::make('default_credit_units')])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSubjects::route('/'), 'create' => Pages\CreateSubject::route('/create'), 'edit' => Pages\EditSubject::route('/{record}/edit')];
    }
}
