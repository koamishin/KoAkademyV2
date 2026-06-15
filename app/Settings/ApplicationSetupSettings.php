<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class ApplicationSetupSettings extends Settings
{
    public int $setup_version = 1;

    public string $status = 'pending';

    public int $current_step = 1;

    public array $draft = [];

    public ?string $completed_at = null;

    public ?int $completed_by_user_id = null;

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    public static function group(): string
    {
        return 'application_setup';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'setup_version' => 1,
            'status' => 'pending',
            'current_step' => 1,
            'draft' => [],
            'completed_at' => null,
            'completed_by_user_id' => null,
        ];
    }
}
