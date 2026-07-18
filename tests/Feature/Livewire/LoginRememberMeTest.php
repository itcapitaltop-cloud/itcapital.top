<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Cookie;

function queuedRecallerCookie(): ?Cookie
{
    return app('cookie')->queued(Auth::guard('web')->getRecallerName());
}

it('authenticates and queues a 30-day httponly remember cookie when remember me is checked', function () {
    $user = User::factory()->create(['username' => 'remember-user']);

    Livewire::test(Login::class)
        ->set('login', 'remember-user')
        ->set('password', 'password')
        ->set('remember', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect(Auth::check())->toBeTrue();

    $cookie = queuedRecallerCookie();

    expect($cookie)->not->toBeNull()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getExpiresTime())
        ->toBeGreaterThan(now()->addDays(30)->subMinutes(5)->getTimestamp())
        ->toBeLessThan(now()->addDays(30)->addMinutes(5)->getTimestamp());
});

it('does not queue a remember cookie when remember me is unchecked', function () {
    $user = User::factory()->create(['username' => 'forget-user']);

    Livewire::test(Login::class)
        ->set('login', 'forget-user')
        ->set('password', 'password')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect(Auth::check())->toBeTrue()
        ->and(queuedRecallerCookie())->toBeNull();
});
