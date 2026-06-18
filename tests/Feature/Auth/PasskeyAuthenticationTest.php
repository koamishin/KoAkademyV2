<?php

declare(strict_types=1);

use Laravel\Fortify\Features;

test('passkey authentication routes are registered', function (): void {
    $relyingPartyId = config('passkeys.relying_party_id');
    $allowedOrigins = config('passkeys.allowed_origins');

    expect(Features::enabled(Features::passkeys()))->toBeTrue();

    $this->getJson(route('passkey.login-options'))
        ->assertOk()
        ->assertJsonPath('options.rpId', $relyingPartyId)
        ->assertJsonStructure([
            'options',
        ]);

    expect(route('passkey.login-options', absolute: false))->toBe('/passkeys/login/options')
        ->and(route('passkey.login', absolute: false))->toBe('/passkeys/login')
        ->and($relyingPartyId)->toBe(strtolower((string) $relyingPartyId))
        ->and($allowedOrigins)->toBe(array_map(
            fn (string $origin): string => strtolower($origin),
            $allowedOrigins,
        ));
});
