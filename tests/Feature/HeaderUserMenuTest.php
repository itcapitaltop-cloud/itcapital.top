<?php

use App\Models\User;

it('shows the username instead of the full name in the header user menu', function () {
    $user = User::factory()->create([
        'username' => 'cool_nickname',
        'first_name' => 'Ivan',
        'last_name' => 'Petrov',
    ]);

    $this->actingAs($user);

    $html = \Illuminate\Support\Facades\Blade::render('<x-ui.user-menu />');

    expect($html)
        ->toContain('cool_nickname')
        ->not->toContain('Ivan')
        ->not->toContain('Petrov');
});
