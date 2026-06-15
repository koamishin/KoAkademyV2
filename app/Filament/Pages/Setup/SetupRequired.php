<?php

declare(strict_types=1);

namespace App\Filament\Pages\Setup;

use App\Models\Campus;
use App\Models\User;
use App\Settings\ApplicationSetupSettings;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

final class SetupRequired extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected static ?string $title = 'Institution setup required';

    protected static ?string $slug = 'setup-required';

    protected string $view = 'filament.pages.setup.setup-required';

    protected Width|string|null $maxContentWidth = Width::TwoExtraLarge;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();
        $campus = $tenant instanceof Campus ? $tenant : null;

        return $user instanceof User
            && ! $user->isSuperAdministrator($campus)
            && ! app(ApplicationSetupSettings::class)->isComplete();
    }

    public function hasLogo(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getLayoutData(): array
    {
        return [
            'hasTopbar' => false,
            'maxContentWidth' => $this->getMaxContentWidth(),
            'maxWidth' => $this->getMaxContentWidth(),
        ];
    }
}
