<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Settings\Pages;

use App\Actions\Academic\ApplyAcademicPreset;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\AcademicSetting;
use App\Models\Institution;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class AcademicConfigurationPage extends Page
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Academic Configuration';

    protected string $view = 'filament.clusters.settings.pages.academic-configuration-page';

    public ?array $data = [];

    public function mount(): void
    {
        $values = AcademicSetting::query()->whereNull('campus_id')->pluck('value', 'key');
        $this->form->fill([
            'institution_id' => Institution::query()->value('id'), 'preset' => null,
            'schedule_increment' => $values->get('schedule_increment', [15])[0] ?? 15,
            'working_days' => $values->get('working_days', [1, 2, 3, 4, 5]),
            'student_number_format' => $values->get('student_number_format', ['{year}-{sequence:6}'])[0] ?? '{year}-{sequence:6}',
            'application_number_format' => $values->get('application_number_format', ['APP-{year}-{sequence:6}'])[0] ?? 'APP-{year}-{sequence:6}',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([Form::make([
            Section::make('Starter structure')->schema([
                Select::make('institution_id')->options(Institution::query()->pluck('name', 'id'))->required()->searchable(),
                Select::make('preset')->options(['grade_school' => 'Grade School', 'high_school' => 'High School', 'college' => 'College', 'tesda' => 'TESDA'])->helperText('Adds editable education levels without locking the institution to a school type.'),
            ])->columns(2),
            Section::make('Calendar and numbering')->schema([
                Select::make('schedule_increment')->options([5 => '5 minutes', 10 => '10 minutes', 15 => '15 minutes', 30 => '30 minutes', 60 => '60 minutes'])->required(),
                CheckboxList::make('working_days')->options([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'])->columns(4)->required(),
                TextInput::make('student_number_format')->required(), TextInput::make('application_number_format')->required(),
            ])->columns(2),
        ])->livewireSubmitHandler('save')->footer([Actions::make([Action::make('save')->submit('save')])])])->statePath('data');
    }

    public function save(ApplyAcademicPreset $applyAcademicPreset): void
    {
        $data = $this->form->getState();
        if (filled($data['preset'])) {
            $applyAcademicPreset->execute(Institution::query()->findOrFail($data['institution_id']), $data['preset']);
        }
        foreach (['schedule_increment', 'working_days', 'student_number_format', 'application_number_format'] as $key) {
            AcademicSetting::query()->updateOrCreate(['campus_id' => null, 'key' => $key], ['value' => (array) $data[$key]]);
        }
        Notification::make()->success()->title('Academic configuration saved')->send();
    }
}
