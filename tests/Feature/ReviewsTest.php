<?php

use App\Enums\ReviewStatusEnum;
use App\Livewire\Reviews\Index;
use App\Models\Review;
use App\Models\User;
use Livewire\Livewire;

test('reviews page is accessible to guests', function () {
    $this->get(route('reviews'))->assertOk();
});

test('guest sees login prompt instead of review form', function () {
    Livewire::test(Index::class)
        ->assertSee(__('reviews_form_guest_prompt'));
});

test('authenticated user can submit a review', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('rating', 4)
        ->set('body', 'Отличная инвестиционная платформа, рекомендую!')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(Review::where('user_id', $user->id)->exists())->toBeTrue();

    $review = Review::where('user_id', $user->id)->first();
    expect($review->rating)->toBe(4)
        ->and($review->status)->toBe(ReviewStatusEnum::Pending);
});

test('review body validation requires minimum 10 characters', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('rating', 5)
        ->set('body', 'Кратко')
        ->call('submit')
        ->assertHasErrors(['body']);
});

test('only approved reviews are shown on reviews page', function () {
    $user = User::factory()->create();

    $approved = Review::factory()->create(['status' => ReviewStatusEnum::Approved, 'user_id' => $user->id]);
    $pending = Review::factory()->create(['status' => ReviewStatusEnum::Pending, 'user_id' => $user->id]);

    Livewire::test(Index::class)
        ->assertSee($approved->body)
        ->assertDontSee($pending->body);
});

test('main page rating link points to reviews route', function () {
    $this->get(route('index'))
        ->assertOk()
        ->assertSee(route('reviews'));
});
