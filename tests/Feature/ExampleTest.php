<?php

test('home renders the welcome page', function (): void {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->has('canRegister'));
});
