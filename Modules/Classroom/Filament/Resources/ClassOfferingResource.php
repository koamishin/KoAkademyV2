<?php

declare(strict_types=1);

namespace Modules\Classroom\Filament\Resources;

use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Classroom\Filament\Resources\ClassOfferingResource\Pages;
use Modules\Classroom\Models\ClassOffering;

final class ClassOfferingResource extends Resource
{
    protected static ?string $model = ClassOffering::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Classroom';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('term_id')
                ->relationship('term', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereHas('academicYear', fn (Builder $query): Builder => $query->where('institution_id', Filament::getTenant()->institution_id)))
                ->required(),
            Select::make('subject_id')
                ->relationship('subject', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('institution_id', Filament::getTenant()->institution_id))
                ->required(),
            Select::make('section_id')
                ->relationship('section', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('campus_id', Filament::getTenant()->getKey()))
                ->nullable(),
            Select::make('teacher_id')
                ->relationship('teacher', 'last_name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereHas('roles', fn (Builder $query): Builder => $query->where('campus_id', Filament::getTenant()->getKey())->where('active', true)))
                ->searchable()
                ->nullable(), TextInput::make('name')->required(), TextInput::make('code')->required(), TextInput::make('capacity')->numeric(),
            TextInput::make('online_meeting_url')->url()->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('code')->searchable(), TextColumn::make('name')->searchable(), TextColumn::make('teacher.full_name'), TextColumn::make('status')->badge(), TextColumn::make('capacity')])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListClassOfferings::route('/'), 'create' => Pages\CreateClassOffering::route('/create'), 'edit' => Pages\EditClassOffering::route('/{record}/edit')];
    }
}
