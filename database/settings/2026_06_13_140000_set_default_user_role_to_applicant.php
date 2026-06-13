<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->update('application_features.default_user_role', fn (): string => 'applicant');
        $this->migrator->update('system.default_user_role', fn (): string => 'applicant');
    }
};
