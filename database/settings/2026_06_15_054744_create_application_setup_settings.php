<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $hasExistingInstallation = Schema::hasTable('institutions')
            && Schema::hasTable('campuses')
            && DB::table('institutions')->exists()
            && DB::table('campuses')->exists();

        $this->migrator->add('application_setup.setup_version', 1);
        $this->migrator->add('application_setup.status', $hasExistingInstallation ? 'completed' : 'pending');
        $this->migrator->add('application_setup.current_step', $hasExistingInstallation ? 7 : 1);
        $this->migrator->add('application_setup.draft', []);
        $this->migrator->add('application_setup.completed_at', $hasExistingInstallation ? now()->toISOString() : null);
        $this->migrator->add('application_setup.completed_by_user_id', null);
    }
};
