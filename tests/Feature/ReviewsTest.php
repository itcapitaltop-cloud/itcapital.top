<?php

use App\Enums\ReviewStatusEnum;
use App\Livewire\Reviews\Create;
use App\Livewire\Reviews\Index;
use App\Models\Review;
use App\Models\User;
use Livewire\Livewire;

test('reviews page is accessible to guests', function () {
    $this->get(route('reviews'))->assertOk();
});

test('reviews create page redirects guests to login', function () {
    $this->get(route('reviews.create'))->assertRedirect(route('login'));
});

test('authenticated user can submit a review', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('rating', 4)
        ->set('body', 'Отличная инвестиционная платформа, рекомендую!')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(Review::where('user_id', $user->id)->exists())->toBeTrue();

    $review = Review::where('user_id', $user->id)->first();
    expect($review->rating)->toBe(4)
        ->and($review->status)->toBe(ReviewStatusEnum::Pending);
});

test('create page redirects to reviews when user already left a review', function () {
    $user = User::factory()->create();
    Review::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(Create::class)
        ->assertRedirect(route('reviews'));
});

test('leave review button is hidden when user already left a review', function () {
    $user = User::factory()->create();
    Review::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertDontSee(route('reviews.create'));

    Livewire::actingAs($user)
        ->test(\App\Livewire\Account\Layout\Sidebar::class)
        ->assertDontSee(route('reviews.create'));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('reviews.create'));
});

test('review body validation requires minimum 10 characters', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('rating', 5)
        ->set('body', 'Кратко')
        ->call('submit')
        ->assertHasErrors(['body']);
});

test('only approved reviews are shown on reviews page', function () {
    $approved = Review::factory()->create(['status' => ReviewStatusEnum::Approved]);
    $pending = Review::factory()->create(['status' => ReviewStatusEnum::Pending]);

    Livewire::test(Index::class)
        ->assertSee($approved->body)
        ->assertDontSee($pending->body);
});

test('reviews pagination uses branded dark styles', function () {
    Review::factory()->count(11)->create([
        'status' => ReviewStatusEnum::Approved,
    ]);

    Livewire::test(Index::class)
        ->assertSeeHtml('aria-label="Pagination Navigation"')
        ->assertSeeHtml('text-[#B4FF59] bg-[#17162D]')
        ->assertDontSeeHtml('bg-white border border-gray-300')
        ->assertDontSee(__('Showing'))
        ->assertSee(__('pagination_previous'))
        ->assertSee(__('pagination_next'));
});

test('reviews pagination collapses page numbers when there are many pages', function () {
    Review::factory()->count(115)->create([
        'status' => ReviewStatusEnum::Approved,
    ]);

    Livewire::test(Index::class)
        ->assertSeeHtml('>...</span>')
        ->assertSeeHtml('gotoPage(12,')
        ->assertDontSeeHtml('gotoPage(7,');
});

test('account sidebar shows leave review button', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(\App\Livewire\Account\Layout\Sidebar::class)
        ->assertSee(__('reviews_sidebar_button'))
        ->assertSee(route('reviews.create'));
});

test('account mobile menu shows leave review button', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('reviews_sidebar_button'));
});

test('reviews page shows back to homepage button', function () {
    Livewire::test(Index::class)
        ->assertSee(__('home_page'))
        ->assertSee(route('index'));
});

test('main page rating link points to reviews route', function () {
    $this->get(route('index'))
        ->assertOk()
        ->assertSee(route('reviews'));
});

test('reviews count is pluralized correctly in russian', function (int $count, string $expected) {
    app()->setLocale('ru');

    expect(trans_choice('reviews_page_based_on', $count, ['count' => $count]))
        ->toBe($expected);
})->with([
    [1, 'На основании 1 отзыва'],
    [11, 'На основании 11 отзывов'],
    [18, 'На основании 18 отзывов'],
    [20, 'На основании 20 отзывов'],
    [21, 'На основании 21 отзыва'],
    [25, 'На основании 25 отзывов'],
    [111, 'На основании 111 отзывов'],
]);
