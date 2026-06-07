<?php

use App\Livewire\Notifications\Dropdown;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['locale' => 'ru']);
    $this->actingAs($this->user);
});

function makeNotifications(User $user, int $count, bool $read = false): void
{
    $rows = collect(range(1, $count))->map(fn (int $i): array => [
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\Generic',
        'notifiable_type' => $user::class,
        'notifiable_id' => $user->id,
        'data' => json_encode(['title' => "n{$i}", 'icon' => 'actions/bell.svg']),
        'read_at' => $read ? now() : null,
        'created_at' => now()->subSeconds($count - $i),
        'updated_at' => now(),
    ])->all();

    DB::table('notifications')->insert($rows);
}

it('does not load notifications until the dropdown is opened', function () {
    makeNotifications($this->user, 30);

    Livewire::test(Dropdown::class)
        ->assertViewHas('items', fn ($items) => $items->isEmpty());
});

it('loads only the first window once opened', function () {
    makeNotifications($this->user, 30);

    Livewire::test(Dropdown::class)
        ->set('openNotifications', true)
        ->assertViewHas('items', fn ($items) => $items->count() === 20)
        ->assertViewHas('hasMore', true);
});

it('loads more notifications when scrolling', function () {
    makeNotifications($this->user, 30);

    Livewire::test(Dropdown::class)
        ->set('openNotifications', true)
        ->call('loadMoreFeed')
        ->assertViewHas('items', fn ($items) => $items->count() === 30)
        ->assertViewHas('hasMore', false);
});

it('reports no more when notifications fit in one window', function () {
    makeNotifications($this->user, 5);

    Livewire::test(Dropdown::class)
        ->set('openNotifications', true)
        ->assertViewHas('items', fn ($items) => $items->count() === 5)
        ->assertViewHas('hasMore', false);
});

it('resets the window when switching tabs', function () {
    makeNotifications($this->user, 30);
    makeNotifications($this->user, 30, read: true);

    Livewire::test(Dropdown::class)
        ->set('openNotifications', true)
        ->call('loadMoreFeed')
        ->assertSet('feedPerPage', 40)
        ->call('switchTab', 'read')
        ->assertSet('feedPerPage', 20)
        ->assertViewHas('items', fn ($items) => $items->count() === 20);
});
