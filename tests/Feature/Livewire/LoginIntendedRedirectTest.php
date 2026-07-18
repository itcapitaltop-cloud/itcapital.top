<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Livewire\Livewire;

it('redirects back to the reviews page after login when the guest came from it', function () {
    $user = User::factory()->create(['username' => 'reviews-guest']);

    $this->withSession(['_previous.url' => route('reviews')]);

    Livewire::test(Login::class)
        ->set('login', 'reviews-guest')
        ->set('password', 'password')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('reviews'));
});

it('keeps pagination query string when redirecting back to the reviews page', function () {
    $user = User::factory()->create(['username' => 'reviews-page-guest']);

    $this->withSession(['_previous.url' => route('reviews') . '?page=2']);

    Livewire::test(Login::class)
        ->set('login', 'reviews-page-guest')
        ->set('password', 'password')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('reviews') . '?page=2');
});

it('redirects to the review form after login when a guest tried to open it directly', function () {
    $user = User::factory()->create(['username' => 'review-form-guest']);

    $this->withSession(['url.intended' => route('reviews.create')]);

    Livewire::test(Login::class)
        ->set('login', 'review-form-guest')
        ->set('password', 'password')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('reviews.create'));
});

it('redirects to the dashboard after login when the guest did not come from the reviews page', function () {
    $user = User::factory()->create(['username' => 'regular-guest']);

    $this->withSession(['_previous.url' => route('index')]);

    Livewire::test(Login::class)
        ->set('login', 'regular-guest')
        ->set('password', 'password')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));
});
