<?php

declare(strict_types=1);

use App\Enums\ReviewStatusEnum;
use App\Models\Review;
use App\Models\User;
use App\MoonShine\Resources\ReviewResource;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\MoonShineRequest;

/**
 * Builds the async request MoonShine sends when the row button is clicked.
 */
function reviewApproveRequest(int|string $reviewId): MoonShineRequest
{
    return MoonShineRequest::create('/admin/review-approve', 'POST', [
        'resourceItem' => $reviewId,
    ]);
}

it('publishes a pending review straight from the index table', function (): void {
    $admin = User::factory()->create();
    $review = Review::factory()->create();
    $this->actingAs($admin);

    expect($review->status)->toBe(ReviewStatusEnum::Pending);

    (new ReviewResource())->approve(reviewApproveRequest($review->id));

    expect($review->refresh()->status)->toBe(ReviewStatusEnum::Approved);
});

it('reloads the list after publishing so the row shows the new status', function (): void {
    $admin = User::factory()->create();
    $review = Review::factory()->create();
    $this->actingAs($admin);

    $referer = 'https://itcapital.test/admin/resource/review-resource/index-page?page=2';
    $request = reviewApproveRequest($review->id);
    $request->headers->set('referer', $referer);
    app()->instance('request', $request);

    $response = (new ReviewResource())->approve($request);

    $payload = json_decode((string) $response->getContent(), true);

    expect($payload['redirect'])->toBe($referer);
});

it('leaves an already published review untouched', function (): void {
    $admin = User::factory()->create();
    $review = Review::factory()->create(['status' => ReviewStatusEnum::Approved]);
    $this->actingAs($admin);

    $updatedAt = $review->updated_at;

    $this->travel(1)->minutes();

    (new ReviewResource())->approve(reviewApproveRequest($review->id));

    expect($review->refresh()->status)->toBe(ReviewStatusEnum::Approved)
        ->and($review->updated_at->eq($updatedAt))->toBeTrue();
});

it('returns a json response when the review no longer exists', function (): void {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $response = (new ReviewResource())->approve(reviewApproveRequest(999999));

    expect($response)->toBeInstanceOf(MoonShineJsonResponse::class);
});

it('shows the quick publish button only for reviews that are not published yet', function (): void {
    $pending = Review::factory()->create();
    $approved = Review::factory()->create(['status' => ReviewStatusEnum::Approved]);

    $buttons = (new ReviewResource())->indexButtons();

    expect($buttons)->toHaveCount(1)
        ->and($buttons[0])->toBeInstanceOf(ActionButton::class)
        ->and($buttons[0]->isSee($pending))->toBeTrue()
        ->and($buttons[0]->isSee($approved))->toBeFalse();
});
