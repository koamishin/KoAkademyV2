<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campuses;

use App\Models\Campus;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class CampusResource extends Resource
{
    protected static ?string $model = Campus::class;

    protected static bool $isScopedToTenant = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Academic Setup';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('institution_id')->relationship('institution', 'name')->required()->preload(),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('code')->required()->maxLength(30),
            TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('timezone')->required()->default('Asia/Manila'),
            Textarea::make('address')->columnSpanFull(),
            Select::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive', 'archived' => 'Archived'])->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(), TextColumn::make('code')->searchable(), TextColumn::make('institution.name'), TextColumn::make('status')->badge(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCampuses::route('/'), 'create' => Pages\CreateCampus::route('/create'), 'edit' => Pages\EditCampus::route('/{record}/edit')];
    }
}
