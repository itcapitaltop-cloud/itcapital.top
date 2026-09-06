# Implementation Plan: Quick-Publish Button for Admin Reviews

Branch: dev (fast mode — no branch created)
Created: 2026-09-06

## Settings
- Testing: yes
- Logging: verbose
- Docs: no

## Scope
Add a one-click "publish" button to each row of the MoonShine admin reviews list
(`Отзывы`), so a moderator can approve a review without opening its edit form.

- Icon-only button with a checkmark (`heroicons.check`), green (`success()`).
- Placed before the standard detail/edit/delete row buttons.
- Visible only for reviews that are not already `approved`.
- Sets `status` to `ReviewStatusEnum::Approved`, shows a toast, and refreshes the
  index table asynchronously (no full page reload) so several reviews can be
  approved in a row.
- Out of scope (confirmed with the user): no admin-journal / `BusinessActivity`
  entry — editing a review status through the normal form does not log today
  either, so the quick button stays consistent with existing behavior.

## Research Context

Verified against the installed `moonshine/moonshine 2.24.0` source:

- `ResourceWithButtons::getIndexItemButtons()` returns
  `[...getIndexButtons(), detail, edit, delete, massDelete]`, and
  `getIndexButtons()` falls back to `buttons()` only when `indexButtons()` is
  empty. `ReviewResource` currently defines neither, so adding `indexButtons()`
  prepends our button **without** removing the standard row buttons.
- `ReviewIndexPage` only calls `parent::mainLayer()`, so the resource-level
  `indexButtons()` hook is the correct place — no page changes are needed.
- `MoonShineRouter::asyncMethodClosure()` automatically injects
  `resourceItem => $item->getKey()` into the async URL for row buttons, so no
  manual `params` closure is required.
- `AsyncController::method()` dispatches `$resource->{$method}($request)`.
- `IterableComponent::getButtons()` calls `->fillItem($this->castData($data))`
  then `->onlyVisible()`, and `ModelCast` hydrates to the model — so
  `canSee(fn (Review $item) => ...)` receives a real `Review` instance.
- `ActionButton` has no `tooltip()` method in v2.24; use
  `customAttributes(['title' => ...])` for the hover hint.
- **Testability caveat:** `MoonShineRequest::getItemID()` reads the *global*
  `request('resourceItem')`, not the injected request instance. Take
  `MoonShineRequest $request` as a parameter and read
  `$request->get('resourceItem')` (the `VerifyingUserResource::confirmEmail`
  pattern) so the method can be called directly from a Pest test, as
  `tests/Feature/AdminBeneficiaryTest.php` does.

Reference implementations already in the repo:
- `app/MoonShine/Resources/VerifyingUserResource.php` — `ActionButton->method()`
  + `MoonShineJsonResponse::make()->toast(...)` status-change actions.
- `app/MoonShine/Resources/PackageDefinitionResource.php:95` — logging style and
  `MoonShineJsonResponse` return shape.

## Tasks

### Phase 1: Admin Action

- [x] Task 1: Add the `approve()` action method to `ReviewResource`.

  Deliverable: `public function approve(MoonShineRequest $request): MoonShineJsonResponse`
  on `App\MoonShine\Resources\ReviewResource`.

  Expected behavior:
  - Resolve the review via `Review::query()->find($request->get('resourceItem'))`.
  - If not found → return `MoonShineJsonResponse::make()->toast('Отзыв не найден', ToastType::ERROR)`.
  - If already `ReviewStatusEnum::Approved` → return a neutral toast
    (`'Отзыв уже опубликован'`, `ToastType::INFO`) and do **not** write to the DB.
    This makes the action idempotent even if two moderators click at once.
  - Otherwise set `status = ReviewStatusEnum::Approved`, save, and return
    `MoonShineJsonResponse::make()->toast('Отзыв опубликован', ToastType::SUCCESS)`.
  - Do **not** redirect — the button refreshes the table via a JS event (Task 2).

  LOGGING REQUIREMENTS (verbose):
  - `Log::info('[ReviewResource.approve] quick publish requested', [...])` on entry
    with `admin_id` (`auth()->id()`), `review_id`, `previous_status`.
  - `Log::warning('[ReviewResource.approve] review not found', ['admin_id' => ..., 'review_id' => ...])`
    for the missing-review branch.
  - `Log::info('[ReviewResource.approve] review published', ['admin_id' => ..., 'review_id' => ...])`
    after a successful save.
  - Follow the existing `[Class.method] message` prefix convention used across
    `app/MoonShine/` and `app/Services/`.

  Files:
  - `app/MoonShine/Resources/ReviewResource.php`

  New imports: `MoonShine\Http\Responses\MoonShineJsonResponse`,
  `MoonShine\MoonShineRequest`, `MoonShine\Enums\ToastType`,
  `Illuminate\Support\Facades\Log`.

- [x] Task 2: Add the checkmark row button via `ReviewResource::indexButtons()`.
  (depends on 1)

  Deliverable: `public function indexButtons(): array` on `ReviewResource`
  returning a single `ActionButton`.

  Expected behavior / exact shape:
  ```php
  ActionButton::make('')
      ->method(
          'approve',
          events: [AlpineJs::event(JsEvent::TABLE_UPDATED, 'index-table')],
      )
      ->icon('heroicons.check')
      ->success()
      ->customAttributes(['title' => 'Опубликовать отзыв'])
      ->canSee(fn (Review $item): bool => $item->status !== ReviewStatusEnum::Approved),
  ```
  - Empty label + icon = icon-only button (same pattern as
    `PromoCodeIndexPage::deleteButton()`).
  - `'index-table'` is the component name returned by
    `IndexPage::listComponentName()`; the event makes the table re-fetch so the
    status column and the button visibility update in place.
  - The standard detail/edit/delete buttons must still render after it — verify
    visually in the admin list.

  LOGGING REQUIREMENTS: none (declarative UI wiring only; all logging lives in
  `approve()` from Task 1).

  Files:
  - `app/MoonShine/Resources/ReviewResource.php`

  New imports: `MoonShine\ActionButtons\ActionButton`,
  `MoonShine\Support\AlpineJs`, `MoonShine\Enums\JsEvent`.

  Fallback note: if the row click (navigate-to-detail) swallows the button click
  in the browser, append
  `->onClick(fn () => 'event.stopPropagation()', 'stop')` — the pattern already
  used in `app/MoonShine/Pages/PromoCode/PromoCodeIndexPage.php`.

### Phase 2: Tests

- [x] Task 3: Cover the quick-publish action with a Pest feature test.
  (depends on 1, 2)

  Deliverable: `tests/Feature/AdminReviewQuickPublishTest.php`, created with
  `php artisan make:test --pest AdminReviewQuickPublishTest`.

  Test cases (call the resource method directly, mirroring
  `tests/Feature/AdminBeneficiaryTest.php` — no HTTP layer, no MoonshineUser
  needed):
  1. *publishes a pending review* — `Review::factory()->create()` (factory
     defaults to `Pending`), build
     `MoonShineRequest::create('/admin/review-approve', 'POST', ['resourceItem' => $review->id])`,
     call `(new ReviewResource())->approve($request)`, assert
     `$review->refresh()->status === ReviewStatusEnum::Approved`.
  2. *leaves an already-approved review untouched* — create a review with
     `status => ReviewStatusEnum::Approved`, call `approve()`, assert the status
     is still `Approved` and `updated_at` did not change (proves the no-write
     branch).
  3. *handles a missing review id* — call `approve()` with a non-existent
     `resourceItem` and assert it returns a `MoonShineJsonResponse` instead of
     throwing.
  4. *button visibility* — assert
     `(new ReviewResource())->indexButtons()` returns exactly one button, that
     `isSee($pendingReview)` is `true`, and `isSee($approvedReview)` is `false`.

  LOGGING REQUIREMENTS: none in the test itself; the assertions above implicitly
  cover the branches that emit each log line.

  Files:
  - `tests/Feature/AdminReviewQuickPublishTest.php`

  Verification commands (run both, both must pass):
  ```
  vendor/bin/pint --dirty --format agent
  php artisan test --compact --filter=AdminReviewQuickPublish
  ```
  Also re-run `php artisan test --compact --filter=Reviews` to confirm the
  existing `tests/Feature/ReviewsTest.php` suite still passes (the public
  reviews page only shows `approved` reviews, so this action feeds it directly).

## Manual Check

After implementation, open the admin `Отзывы` list and confirm:
- Pending/rejected rows show a green checkmark button; approved rows do not.
- Clicking it shows the «Отзыв опубликован» toast, the row's status flips to
  «Опубликован», and the checkmark disappears — all without a page reload.
- Detail / edit / delete buttons are still present on every row.
