<?php

declare(strict_types=1);

namespace Tests;

use App\Settings\ApplicationSetupSettings;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        if (Schema::hasTable('settings')) {
            $applicationSetupSettings = app(ApplicationSetupSettings::class);
            $applicationSetupSettings->status = 'completed';
            $applicationSetupSettings->current_step = 7;
            $applicationSetupSettings->completed_at = now()->toISOString();
            $applicationSetupSettings->save();
        }
    }
}
