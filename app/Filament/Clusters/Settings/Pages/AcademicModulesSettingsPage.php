<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Settings\Pages;

use App\Academic\AcademicModuleRegistry;
use App\Academic\ModuleDefinition;
use App\Filament\Clusters\Settings\SettingsCluster;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class AcademicModulesSettingsPage extends Page
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $navigationLabel = 'Academic Modules';

    protected static ?string $title = 'Academic Modules';

    protected string $view = 'filament.clusters.settings.pages.academic-modules-settings-page';

    public ?array $data = [];

    public function mount(AcademicModuleRegistry $modules): void
    {
        $this->form->fill($modules->all()->mapWithKeys(
            fn (ModuleDefinition $module): array => [$module->key() => $modules->enabled($module->key())],
        )->all());
    }

    public function form(Schema $schema): Schema
    {
        $modules = app(AcademicModuleRegistry::class);

        return $schema
            ->components([
                Form::make([
                    Section::make('Enabled capabilities')
                        ->description('Dependencies are enabled automatically. Disabling a dependency also disables modules that require it.')
                        ->schema($modules->all()->map(
                            fn (ModuleDefinition $module): Toggle => Toggle::make($module->key())
                                ->label($module->name())
                                ->helperText($module->description()),
                        )->values()->all())
                        ->columns(2),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')->submit('save'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(AcademicModuleRegistry $modules): void
    {
        foreach ($this->form->getState() as $module => $enabled) {
            $modules->setEnabled($module, (bool) $enabled);
        }

        Notification::make()->success()->title('Academic modules updated')->send();
    }
}
