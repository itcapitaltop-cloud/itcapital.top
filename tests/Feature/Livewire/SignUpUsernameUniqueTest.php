<?php

use App\Livewire\Auth\SignUp;
use App\Models\User;
use Livewire\Livewire;

it('shows a validation error when the username is already taken', function () {
    User::factory()->create(['username' => 'takennick']);

    Livewire::test(SignUp::class)
        ->set('username', 'takennick')
        ->set('email', 'new@example.com')
        ->set('firstName', 'Ivan')
        ->set('lastName', 'Ivanov')
        ->set('password', 'password1')
        ->set('passwordConfirm', 'password1')
        ->call('onSubmit')
        ->assertHasErrors(['username' => 'unique']);

    expect(User::withoutGlobalScope('notBanned')->where('email', 'new@example.com')->exists())->toBeFalse();
});

it('shows a validation error when the username belongs to a banned user', function () {
    User::factory()->create(['username' => 'bannednick', 'banned_at' => now()]);

    Livewire::test(SignUp::class)
        ->set('username', 'bannednick')
        ->set('email', 'new@example.com')
        ->set('firstName', 'Ivan')
        ->set('lastName', 'Ivanov')
        ->set('password', 'password1')
        ->set('passwordConfirm', 'password1')
        ->call('onSubmit')
        ->assertHasErrors(['username' => 'unique']);
});
