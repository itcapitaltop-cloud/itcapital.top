<?php

use App\Livewire\Index\Main as IndexMain;
use App\Models\User;
use Livewire\Livewire;

it('passes users count rounded down to tens as clients count to index page', function () {
    User::factory()->count(23)->create();

    Livewire::test(IndexMain::class)
        ->assertViewHas('clientsCount', 20);
});

it('renders hero label with dynamic clients count', function () {
    User::factory()->count(12)->create();

    Livewire::test(IndexMain::class)
        ->assertSee(__('livewire_index_main_hero_label', ['count' => 10]));
});
