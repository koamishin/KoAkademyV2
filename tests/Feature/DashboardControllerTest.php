<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('requires authentication', function (): void {
    get(route('dashboard'))
        ->assertRedirect(route('login'));
});

it('renders the dashboard page for authenticated users', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('student')
            ->has('academicContext')
            ->has('enrollment')
            ->has('todaySchedule')
            ->has('upcomingAssignments')
            ->has('gradeSummary')
            ->has('recentAnnouncements')
            ->has('stats')
        );
});
