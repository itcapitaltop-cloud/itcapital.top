# Implementation Plan: Unlock an Amount from the Package Body

Branch: `feature/package-reinvest-unlock` (no new branch — this work continues on top of the
unmerged reinvest-unlock feature, which touches the same files)
Created: 2026-09-09

## Feature Summary

A user presses **"Разблокировать сумму с пакета"** on an ITC package card in the cabinet,
enters an amount, and confirms.

On confirm, that amount of the **package body** (not reinvests) is *unlocked*:

- it immediately stops taking part in dividend generation (leaves the profit base),
- it is shown as a separate line under the package card together with its payout date,
- it lands on the user's MAIN balance exactly one calendar month later,
- while nothing is unlocked, the line is not rendered at all.

This is the body-side twin of the already implemented reinvest unlock
(`.ai-factory/plans/feature-package-reinvest-unlock.md`, commit `a20103a`). The mechanics,
naming and safety rules mirror that feature; only the money source differs.

The existing **instant** body withdrawal (`Packages::withdrawPackageBalance()`, available in the
card only after `work_to` has passed) keeps working unchanged, but must stop treating money that
is already pending an unlock as available.

## Settings
- Testing: yes (Pest)
- Logging: verbose (DEBUG flow logs + `spatie/laravel-activitylog` business events)
- Docs: no (warn-only)

## Roadmap Linkage
Not applicable — `.ai-factory/ROADMAP.md` does not exist in this project.

## Product Decisions (confirmed with the user)

| Question | Decision |
|---|---|
| How is the amount chosen? | **The user enters an amount** in a modal, like the existing `withdrawPackageBalance` form. Validated against the available body. |
| When may a user unlock? | **Any time on an active package.** No maturity gate, no `work_to` gate — the one-month wait *is* the lock. |
| What stops counting while the money waits? | **Only dividend accrual** (profit base) and the card's own deposit figure. Personal deposit, rank qualification, partner turnover and `user_summary` keep counting the amount until it actually lands on the balance. |
| Which package types? | **STANDARD / PRIVILEGE / VIP.** PRESENT is excluded (gift body is not the user's money); ARCHIVE and STAKING are already off the ITC Packages tab. |
| Minimum body remainder | **No restriction** (revised by the user during implementation). Any amount up to the available body may be unlocked — e.g. 25 out of a 100 body. When the payout lands and the body is below 100 ITC, `month_profit_percent` is zeroed at that moment, exactly as the existing instant withdrawal already does. The original plan blocked such unlocks instead; that decision was overridden. |
| Can a pending unlock be cancelled? | **No — irreversible for the user.** The only system-side settlement is package closing (see Task 11). |

## Domain Analysis (current behaviour — verified in code)

**What "package body" is.** There is no single column. The body is computed in five places as:

```
transaction.amount
  + partner_transfers_sum_amount      (package_partner_transfers → transactions)
  + reinvest_to_body_sum_amount       (reinvest_to_package_bodies)
  - balance_withdraws_sum_amount      (package_balance_withdraws → transactions)
```

Call sites: `ItcPackage::getTotalAmountAttribute()` (`app/Models/ItcPackage.php:246`),
`AdminController::createItcPackagesProfits()` (`app/Http/Controllers/AdminController.php:88-94`),
`Livewire\Account\Dashboard\Index` (`app/Livewire/Account/Dashboard/Index.php:43-52`),
`Livewire\Account\Partners\Partners` (`app/Livewire/Account/Partners/Partners.php:652`),
`SummaryMetricsService` (`app/Services/Admin/SummaryMetricsService.php:224-232`) and the card blade
(`resources/views/components/account/itc/package.blade.php:222-231`).

**How money leaves the body today.** `Packages::withdrawPackageBalance()`
(`app/Livewire/Account/Itc/Packages.php:186`) creates a `WITHDRAW_PACKAGE_TO_BALANCE`
transaction (prefix `WPB-`) plus a `package_balance_withdraws` row. The
`PackageBalanceWithdrawObserver` writes the `PackageAmountWithdrawnToBalance` business activity.
That single row is what every consumer above subtracts.

**The dividend base.** `AdminController::createItcPackagesProfits()` builds
`transaction.amount + active_reinvest_profits + partner_transfers + reinvest_to_body − balance_withdraws`.
It is the only place ITC dividends are generated (staking uses a separate service).
→ **Leaving the profit base = being subtracted from that sum.**

**The 100 ITC rule.** `withdrawPackageBalance()` sets `month_profit_percent = 0` when the body
drops below 100 after the withdrawal (`app/Livewire/Account/Itc/Packages.php:213-222`). Because
`month_profit_percent` is not restored anywhere for a non-expired package, this is effectively
irreversible for the user — hence the "block instead of zero" decision above.

## Design Decisions

1. **A new table `package_body_unlocks`**, not columns on `itc_packages`. The body has no row of
   its own, unlocks are partial, and a user may unlock several times — this needs its own ledger.
   Reinvests could use two nullable columns only because each reinvest row *is* one amount.

2. **The payout writes a normal `package_balance_withdraws` row.** This is the key simplification:
   while an unlock is pending it is subtracted from the profit base via a new
   `pending_body_unlocks` sum; the moment it is paid, `balance_withdraws_sum_amount` picks it up and
   the pending sum drops to zero. No gap, no double subtraction, and every existing consumer
   (dashboard, partner stats, admin summary, card, `max_package_sum`) becomes correct for free
   after payout, exactly as if the user had used the instant withdrawal.

3. **Exactly one calendar month** = `Carbon::addMonthNoOverflow()` — same as the reinvest unlock.
   31 Jan → 28/29 Feb, not 3 Mar.

4. **One source of truth for "available body"**, a new
   `App\Services\Package\PackageBodyBalanceResolver`. Three consumers must agree:
   the new unlock validator, the existing `max_package_sum` validator (Task 9) and the card.
   Duplicating the formula a sixth and seventh time is how the pending amount gets double-spent.

5. **Idempotent payout** via `payout_transaction_uuid` (nullable + unique) plus `lockForUpdate()`
   and a `whereNull` guard, mirroring `PackageReinvestRepository::withdraw()`.

6. **Card deposit figure subtracts pending unlocks**, and the dashboard "Сумма пакетов" must
   subtract them too (Task 12). This is required by the standing project rule that /account stats
   reconcile with the ITC Packages tab. It is a *display* change only — ranks, partner turnover and
   `user_summary` are untouched, per the product decision.

## Discovered Issues (found while planning — read before implementing)

- **[security, adjacent] `withdrawPackageBalance()` has no ownership check.**
  `app/Livewire/Account/Itc/Packages.php:186` validates the amount against the *package* and then
  credits `Auth::id()`. Nothing verifies that the package belongs to the caller, so any
  authenticated user can drain another user's package body onto their own balance by calling the
  Livewire method with a foreign uuid. The blade only hides the button. Task 10 fixes this because
  the new feature shares the same validator; if you decide to split it out, do it in its own commit
  and tell the user — it is a live production issue, not a refactor.

- **[correctness, pre-existing, out of scope] `continuePackageWork()` may double-credit unlocked
  reinvests.** `app/Livewire/Account/Itc/Packages.php:310` collects reinvests with
  `whereDoesntHave('withdraw')`, which *includes* reinvests unlocked but not yet paid, folds their
  amount into the body via `ReinvestToPackageBody`, and deletes them — while
  `packages:payout-unlocked-reinvests` will still pay the same rows. Introduced by the reinvest
  unlock feature. Not this plan's scope; report it so it gets its own ticket.

- **[cosmetic, accepted during /aif-verify] Stale `month_profit_percent` after an instant
  withdrawal beside a pending unlock.** The `< 100` zeroing check in
  `Packages::withdrawPackageBalance()` (`app/Livewire/Account/Itc/Packages.php:270`) still uses the
  old body formula, which ignores pending unlocks. Reproduced: body 1000, pending unlock 950,
  instant withdrawal of 50 leaves the effective body at 0 while the card still reads 8.2 %.
  **No financial impact** — the dividend base already subtracts the pending unlock, so the
  generated profit is 0.00; `packages:payout-unlocked-body-amounts` zeroes the rate when the
  unlock lands. Accepted as-is: fixing it would zero the rate a month earlier than the instant
  withdrawal does today.

- **[correctness, pre-existing] `ItcPackageRepository::closePackage()` pays out
  `transaction->amount` only**, ignoring `partner_transfers`, `reinvest_to_body` and
  `balance_withdraws`. Task 11 does *not* fix that; it only makes sure our pending unlocks cannot be
  paid twice when a package is closed.

## Commit Plan

- **Commit 1** (tasks 1-4): `feat: add package body unlock ledger`
- **Commit 2** (tasks 5-7): `feat: unlock package body amount and pay it out after one month`
- **Commit 3** (tasks 8-10): `feat: add package body unlock to the package card`
- **Commit 4** (tasks 11-13): `feat: settle body unlocks on close and reconcile stats`
- **Commit 5** (tasks 14-19): `test: cover package body unlock and delayed payout`

## Tasks

### Phase 1: Data model

- [x] **Task 1: Migration — create `package_body_unlocks`**
  - `php artisan make:migration create_package_body_unlocks_table --no-interaction`
  - Columns (follow `2025_07_27_160039_create_package_balance_withdraws_table.php` for FK style and
    `2024_07_17_130428_create_package_profit_reinvests_table.php` for the money column):
    - `id()`
    - `string('uuid')->unique()` — business id, `'PBU-' . Str::random(10)`
    - `string('package_uuid')` + FK → `itc_packages.uuid`, `cascadeOnDelete()`
    - `decimal('amount', 16, 8)` — same precision as `package_profit_reinvests.amount`; never float
    - `timestamp('unlocked_at')` (not null)
    - `timestamp('payout_at')` (not null)
    - `string('payout_transaction_uuid')->nullable()->unique('pbu_payout_transaction_uuid_unique')`
    - `timestamp('cancelled_at')->nullable()` — system settlement on package close (Task 11)
    - `timestamps()`
    - `index(['payout_at'], 'pbu_payout_at_index')` — the payout job filters on it
  - **No FK on `payout_transaction_uuid`.** `package_balance_withdraws.uuid` already carries a
    cascading FK to `transactions`; adding a second cascading FK here would silently delete unlock
    history, and `nullOnDelete()` would re-open a paid unlock for a second payout. The unique index
    is what protects the invariant. Write this rationale as a comment in the migration.
  - Real `down()` that drops the table.
  - Files: `database/migrations/<ts>_create_package_body_unlocks_table.php`
  - Logging: none (schema only).

- [x] **Task 2: `PackageBodyUnlock` model**
  - `php artisan make:model PackageBodyUnlock --no-interaction`
  - `$fillable`: `uuid`, `package_uuid`, `amount`, `unlocked_at`, `payout_at`,
    `payout_transaction_uuid`, `cancelled_at`.
  - `casts()` method (not the `$casts` property — follow `PackageProfitReinvest`):
    `unlocked_at`, `payout_at`, `cancelled_at` → `datetime`. **Do not** cast `amount`; the project
    passes money around as strings into `BigDecimal`.
  - `#[Scope]` methods in the project style (`app/Models/PackageProfitReinvest.php:70`):
    - `pending(Builder $q)` → `whereNull('payout_transaction_uuid')->whereNull('cancelled_at')`
    - `duePayout(Builder $q)` → `pending()->where('payout_at', '<=', now())`
  - Relations: `package(): BelongsTo` (`package_uuid` → `itc_packages.uuid`),
    `payoutTransaction(): BelongsTo` (`payout_transaction_uuid` → `transactions.uuid`).
  - Full `@property` PHPDoc block, matching `PackageProfitReinvest`.
  - No soft deletes: a financial ledger row is either pending, paid, or cancelled.
  - Files: `app/Models/PackageBodyUnlock.php`
  - Logging: none (model only).
  - Depends on: 1.

- [x] **Task 3: `ItcPackage` — relations and aggregate sums**
  - Add next to `unlockableReinvestProfits()` (`app/Models/ItcPackage.php:152`):
    - `bodyUnlocks(): HasMany` — all rows
    - `pendingBodyUnlocks(): HasMany` — `->pending()`
  - In `scopeUserPackagesWithFinancials()` (`app/Models/ItcPackage.php:290`) add, keeping every
    existing `withSum` untouched:
    - `withSum(['pendingBodyUnlocks' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')`
    - `withMin('pendingBodyUnlocks', 'payout_at')` — nearest payout date for the card line
  - Update the `@property-read` PHPDoc block with
    `pending_body_unlocks_sum_amount` and `pending_body_unlocks_min_payout_at`.
  - **Do not** change `getTotalAmountAttribute()` — it is used by admin/export paths that must keep
    showing the full body. The card computes its own figure (Task 8).
  - Files: `app/Models/ItcPackage.php`
  - Logging: none (model only).
  - Depends on: 1, 2.

- [x] **Task 4: `PackageBodyUnlockFactory` with states**
  - `php artisan make:factory PackageBodyUnlockFactory --model=PackageBodyUnlock --no-interaction`
  - Follow `database/factories/PackageProfitReinvestFactory.php` exactly for style.
  - Default: `uuid` = `'PBU-' . Str::random(10)`, `amount` = `'100.00'`,
    `unlocked_at` = `now()`, `payout_at` = `now()->addMonthNoOverflow()`.
  - States: `duePayout()` (`unlocked_at` a month ago, `payout_at` = `now()->subMinute()`),
    `paid(string $transactionUuid)`, `cancelled()`.
  - Files: `database/factories/PackageBodyUnlockFactory.php`
  - Logging: none (test support).
  - Depends on: 1, 2.

<!-- Commit checkpoint: tasks 1-4 -->

### Phase 2: Business logic

- [x] **Task 5: `PackageBodyBalanceResolver` — the single body formula**
  - `php artisan make:class Services/Package/PackageBodyBalanceResolver --no-interaction`
  - `final readonly class`, no constructor dependencies.
  - `public function availableToUnlock(ItcPackage $package): BigDecimal`
    - Loads the sums itself when they are not already eager-loaded, so it is safe to call from a
      Livewire action, a validator and a blade view. Use `ItcPackage::query()->where('uuid', ...)`
      with the four `withSum` clauses rather than lazy relation calls (N+1 in the card loop).
    - `transaction.amount + partner_transfers + reinvest_to_body − balance_withdraws − pending_body_unlocks`
    - All arithmetic through `BigDecimal` — never float (project financial rule).
    - Clamp at zero: return `BigDecimal::zero()` if the formula goes negative, and log a warning,
      because a negative body means data drift that must be visible.
  - `public function isUnlockable(ItcPackage $package): bool` — type is one of
    STANDARD / PRIVILEGE / VIP **and** `availableToUnlock()` is positive. One place decides who gets
    the button, so the blade and the server agree.
  - ~~`violatesMinimumRemainder()`~~ — dropped with the minimum-remainder rule. Replaced by
    `public function isBelowMinimumRemainder(BigDecimal $body): bool`, used by the payout command
    to decide when to zero `month_profit_percent`; the constant stays as
    `public const MIN_BODY_REMAINDER = '100'`.
  - Logging: `Log::debug('[PackageBodyBalanceResolver.availableToUnlock]', ['package_uuid', 'body', 'pending_unlocks', 'available'])`;
    `Log::warning(...)` on the negative-body clamp.
  - Files: `app/Services/Package/PackageBodyBalanceResolver.php`
  - Depends on: 3.

- [x] **Task 6: `UnlockPackageBodyAmountAction`**
  - `php artisan make:class Actions/Packages/UnlockPackageBodyAmountAction --no-interaction`
  - `final readonly class`, constructor-promoted `BusinessActivityLogger` and
    `PackageBodyBalanceResolver`. Model it on
    `app/Actions/Packages/UnlockMaturedReinvestsAction.php` — same shape, same guard order.
  - `execute(ItcPackage $package, string $amount, ?User $causer = null): UnlockPackageBodyResult`
  - Body, all inside `DB::transaction()`:
    1. `ItcPackage::query()->where('uuid', $package->uuid)->with('transaction')->lockForUpdate()->firstOrFail()`.
    2. Lock the pending unlocks too:
       `PackageBodyUnlock::query()->where('package_uuid', ...)->pending()->lockForUpdate()->get()`.
       Without this, two concurrent unlocks each see the same "available" figure.
    3. **Re-validate inside the lock** (the Livewire validator in Task 8 is UX, this is the
       invariant): reject a non-positive amount, an amount above `availableToUnlock()` and a package
       type outside STANDARD/PRIVILEGE/VIP. (The minimum-remainder rejection was dropped — see
       Product Decisions.) Throw
       `App\Exceptions\Domain\InvalidAmountException` with the matching translated message — the
       Livewire `exception()` hook (`app/Livewire/Account/Itc/Packages.php:945`) already renders it
       as an error toast.
    4. Insert one `PackageBodyUnlock` with `unlocked_at = Carbon::now()` and
       `payout_at = $unlockedAt->copy()->addMonthNoOverflow()`.
  - Return `app/Dto/Packages/UnlockPackageBodyResult.php`
    (`readonly`, `string $uuid`, `BigDecimal $amount`, `CarbonInterface $payoutAt`,
    `BigDecimal $remainingBody`), mirroring `UnlockMaturedReinvestsResult`.
  - Business activity: new `ActivityEventTypeEnum::PackageBodyUnlocked` (Task 7), feeds
    `[Packages, UserDetailUser]`, properties `amount`, `package_uuid`, `payout_at`,
    `remaining_body`, `unlock_uuid`; `logName: 'packages'`,
    `context: auth()->check() ? 'account' : 'system'`.
  - Logging (verbose): DEBUG on entry with `package_uuid` / `user_id` / requested amount, DEBUG with
    the resolved available figure, WARNING on each rejection branch (include which rule failed),
    INFO on success with `unlock_uuid`, `amount`, `payout_at`, `remaining_body`.
  - Files: `app/Actions/Packages/UnlockPackageBodyAmountAction.php`,
    `app/Dto/Packages/UnlockPackageBodyResult.php`
  - Depends on: 2, 5.

- [x] **Task 7: Activity event type and translations**
  - Add `case PackageBodyUnlocked = 'package_body_unlocked';` to
    `app/Enums/Activity/ActivityEventTypeEnum.php` (next to `PackageReinvestUnlocked`).
  - Add the `match` arm in `ActivityManager::resolveBusinessEvent()`
    (`app/ActivityLog/ActivityManager.php:104`), reusing the existing `$payoutAt`/`formatDate()`
    helper added by the reinvest feature.
  - Add `package_body_unlocked` to `lang/{ru,en,zh}/activity/feed.php`, e.g. ru:
    `'С пакета :uuid разблокирована сумма :amount ITC, выплата :date'`.
  - Register the event wherever `PackageReinvestUnlocked` is registered for filtering —
    check `app/Services/ActivityLog/ActivityFeedService.php:1` (the reinvest commit touched it) and
    add the new case in the same place.
  - Cabinet strings in `lang/{ru,en,zh}.json`:
    - `components_account_itc_package_unlock_body_action` — ru "Разблокировать сумму с пакета"
    - `components_account_itc_package_unlock_body_title` — modal heading
    - `components_account_itc_package_unlock_body_amount_label` — input label
    - `components_account_itc_package_unlock_body_available` — ":amount ITC доступно"
    - `components_account_itc_package_unlock_body_note` —
      "Сумма сразу перестанет приносить дивиденды, а деньги поступят на основной баланс :date. Отменить действие нельзя."
    - `components_account_itc_package_unlocked_body_label` — ru "Разблокировано с пакета"
    - `components_account_itc_package_unlocked_body_payout_at` — ":amount ITC на баланс :date"
    - `livewire_itc_packages_body_unlock_success` —
      ":amount ITC разблокировано, деньги поступят на баланс :date"
    - `livewire_itc_packages_body_unlock_not_available` — nothing available to unlock
    - `livewire_itc_packages_body_unlock_too_large` — amount above the available body
    - `livewire_itc_packages_body_unlock_wrong_type` — package type not eligible
  - Keep key ordering next to the reinvest-unlock keys added by commit `a20103a`, and keep all
    three locale files in sync (zh may reuse the English text if no translation is supplied).
  - Files: `app/Enums/Activity/ActivityEventTypeEnum.php`, `app/ActivityLog/ActivityManager.php`,
    `app/Services/ActivityLog/ActivityFeedService.php`, `lang/{ru,en,zh}.json`,
    `lang/{ru,en,zh}/activity/feed.php`
  - Depends on: 6.

<!-- Commit checkpoint: tasks 5-7 -->

### Phase 3: Payout and cabinet UI

- [x] **Task 8: `PayoutUnlockedBodyAmountsCommand`**
  - `php artisan make:command Packages/PayoutUnlockedBodyAmountsCommand --no-interaction`
  - Signature `packages:payout-unlocked-body-amounts {--uuid=} {--dry-run}`, mirroring
    `app/Console/Commands/Packages/PayoutUnlockedReinvestsCommand.php` — copy its structure,
    including the `chunkById` comment about not ordering by `payout_at`, the `--dry-run` branch and
    `isSerializationFailure()` (SQLSTATE `40001` is a retry, not an error).
  - Per row, inside `DB::transaction()` with `SET TRANSACTION ISOLATION LEVEL SERIALIZABLE`
    when `DB::transactionLevel() === 0` (same reason as
    `PackageReinvestRepository::withdraw()`: PostgreSQL only accepts it as the first statement):
    1. `PackageBodyUnlock::query()->where('uuid', ...)->lockForUpdate()->firstOrFail()`.
    2. Early-return with a WARNING when `payout_transaction_uuid` is already set or
       `cancelled_at` is not null.
    3. `TransactionRepositoryContract::commonStore()` with
       `trxType: TrxTypeEnum::WITHDRAW_PACKAGE_TO_BALANCE`, `balanceType: BalanceTypeEnum::MAIN`,
       `prefix: 'WPB-'`, `acceptedAt: now()` — identical to the instant withdrawal, so the user's
       transaction history reads the same.
    4. `PackageBalanceWithdraw::query()->create(['uuid' => $trx->uuid, 'package_uuid' => ...])` —
       this is what makes every existing consumer subtract the money permanently, and it fires
       `PackageBalanceWithdrawObserver` for the activity log.
    5. Set `payout_transaction_uuid` on the unlock row.
    6. Re-read the body via `PackageBodyBalanceResolver`; when it is now **below 100 ITC**, set
       `month_profit_percent = 0` on the package — the same threshold, comparison and moment as the
       instant withdrawal (`app/Livewire/Account/Itc/Packages.php:213`). Since unlocks are no longer
       restricted by a minimum remainder, the body can land anywhere between 0 and the threshold.
  - `--uuid` processes one unlock ignoring its `payout_at` but still honouring the paid/cancelled
    guards (same contract as the reinvest command).
  - Logging: DEBUG per batch with the row count, INFO per successful payout
    (`unlock_uuid`, `package_uuid`, `amount`, `transaction_uuid`), WARNING on skip and on
    serialization failure, `Log::error` + `report($e)` on anything else. Summary line to the
    console, `self::FAILURE` when any row failed.
  - Schedule in `routes/console.php` next to `packages:payout-unlocked-reinvests`:
    `->hourly()->withoutOverlapping()->onOneServer()->sendOutputTo(storage_path('logs/scheduler.log'))`.
  - Files: `app/Console/Commands/Packages/PayoutUnlockedBodyAmountsCommand.php`,
    `routes/console.php`
  - Depends on: 2, 5.

- [x] **Task 9: Livewire — `unlockPackageBodyAmount()`**
  - In `app/Livewire/Account/Itc/Packages.php`:
    - Add `public string $unlockBodyAmount = '';` next to `$withdrawPackageAmount`.
    - Register a validator rule `max_body_unlock:<uuid>` in `boot()` alongside the existing
      `max_package_sum` extension, backed by `PackageBodyBalanceResolver::availableToUnlock()`.
      Parse the input the same way `max_package_sum` does
      (`str_replace([' ', ','], ['', '.'], $value)`) but compare with `BigDecimal`, not `(float)`.
    - `public function unlockPackageBodyAmount(string $uuid, UnlockPackageBodyAmountAction $action): void`
      1. DEBUG log the request (`package_uuid`, `user_id`, amount).
      2. Load the package with `transaction`; **`abort(403)` when
         `$package->transaction?->user_id !== Auth::id()`** — copy the guard from
         `unlockMaturedReinvests()` (`app/Livewire/Account/Itc/Packages.php:997`).
      3. `validateOnly('unlockBodyAmount', ['unlockBodyAmount' => ['required','numeric','min:1', "max_body_unlock:$uuid"]])`.
      4. Call the action, `reset('unlockBodyAmount')`, `dispatch('balance-edited')` (the modal
         closes on that event, like the existing balance modal) and dispatch a success
         `new-system-notification` with the amount and payout date.
  - Files: `app/Livewire/Account/Itc/Packages.php`
  - Logging: as above; WARNING on the 403 branch with `package_uuid`, `user_id`, `owner_id`.
  - Depends on: 5, 6, 7.

- [x] **Task 10: Close the ownership hole in the existing body withdrawal**
  - See *Discovered Issues*. `Packages::withdrawPackageBalance()`
    (`app/Livewire/Account/Itc/Packages.php:186`) credits `Auth::id()` for an arbitrary package
    uuid with no ownership check.
  - Add the same guard used by `unlockMaturedReinvests()`: load the package with `transaction`,
    `abort(403)` when the owner is not the caller, WARNING log on the rejected branch.
  - Also make `max_package_sum` subtract pending unlocks:
    `maxAvailable = PackageBodyBalanceResolver::availableToUnlock($package)`. Without this, a user
    can unlock 200 ITC and, once `work_to` has passed, instantly withdraw the same 200 again.
    Note that this makes `max_package_sum` slightly stricter than before — it now also accounts for
    `partner_transfers` and `reinvest_to_body`, which the old inline formula ignored; that is a
    correction, mention it in the commit message.
  - Files: `app/Livewire/Account/Itc/Packages.php`
  - Logging: WARNING on the 403 branch.
  - Depends on: 5.

- [x] **Task 11: Package card — button, modal and the pending line**
  - `resources/views/components/account/itc/package.blade.php`:
    - Add `showConfirmUnlockBody: false` and `isModalUnlockBodyActive: false` to the card's
      `x-data` object.
    - New modal (copy the `isModalEditBalanceActive` modal at line ~136 for markup and the
      `showConfirmUnlockReinvests` modal for the warning note): heading, the available figure,
      an `x-ui.input name="unlockBodyAmount"`, the "cannot be undone / money arrives on :date" note
      using `now()->addMonthNoOverflow()->format('d.m.Y')`, and
      `wire:submit="unlockPackageBodyAmount('{{ $package->uuid }}')"` closing on
      `x-on:balance-edited.window`.
    - New button below the unlock-reinvests button, rendered only when the package type is
      STANDARD / PRIVILEGE / VIP **and** the available body is positive. Compute the availability
      in the `@php` block at the top of the file from the eager-loaded sums (do **not** call the
      resolver per card — that is one query per card).
    - **Deposit figure** (line ~222): subtract `$package->pending_body_unlocks_sum_amount ?? 0`
      from the existing body expression, so the card and the new line do not show the same money
      twice.
    - New line under the card, next to the unlocked-reinvests line (line ~395), rendered only when
      `($package->pending_body_unlocks_sum_amount ?? 0) > 0`:
      label `components_account_itc_package_unlocked_body_label` plus
      `components_account_itc_package_unlocked_body_payout_at` with the amount and
      `pending_body_unlocks_min_payout_at` formatted `d.m.Y`. **When nothing is unlocked the block
      is not rendered at all** — this is the "строчку скрывать" requirement.
  - Files: `resources/views/components/account/itc/package.blade.php`
  - Logging: none (view). Frontend build note: the user may need `npm run build` / `npm run dev`.
  - Depends on: 3, 7, 9.

<!-- Commit checkpoint: tasks 8-11 -->

### Phase 4: Settlement, stats and admin

- [x] **Task 12: Settle pending unlocks when a package is closed**
  - `ItcPackageRepository::closePackage()` (`app/Repositories/ItcPackageRepository.php:129`) pays
    out `transaction->amount` in full and archives the package. A pending unlock would then be paid
    a second time by the scheduler.
  - Inside the existing transaction, before the package type is changed: load pending unlocks with
    `lockForUpdate()`, set `cancelled_at = now()`, and write one INFO log line per package with the
    uuids and total. No new transaction and no balance change — the money is already covered by the
    `WITHDRAW_PACKAGE` payout above.
  - Do **not** attempt to fix the wider `closePackage` body arithmetic here (see
    *Discovered Issues*); keep the change to unlock settlement only.
  - Files: `app/Repositories/ItcPackageRepository.php`
  - Depends on: 2.

- [x] **Task 13: Profit base excludes pending body unlocks**
  - `AdminController::createItcPackagesProfits()` (`app/Http/Controllers/AdminController.php:72`):
    add `withSum(['pendingBodyUnlocks' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')`
    and `->minus($package->pending_body_unlocks_sum_amount ?? 0)` in the `$base` chain.
  - Extend the existing `Log::debug('[AdminController.createItcPackagesProfits] base', ...)` payload
    with `pending_body_unlocks`.
  - Add a comment in the same style as the reinvest one explaining that a pending unlock has already
    left the base and will be replaced by a `balance_withdraws` row on payout — so the two never
    overlap.
  - This is the **only** ITC dividend generation path; staking uses `StakingAccrualService` and is
    out of scope.
  - Files: `app/Http/Controllers/AdminController.php`
  - Depends on: 3.

- [x] **Task 14: Dashboard reconciliation**
  - Project rule: /account stats must match the ITC Packages tab. Task 11 makes each card subtract
    its pending unlocks, so `Livewire\Account\Dashboard\Index` must do the same or the two figures
    drift apart.
  - In `app/Livewire/Account/Dashboard/Index.php:43-52` add the `pendingBodyUnlocks` `withSum` and
    subtract it from `$body`. Update the explanatory comment above the query.
  - **Leave `Partners.php:652`, `SummaryMetricsService` and `ProgressBarAction` untouched** — per
    the product decision, partner turnover, rank qualification and `user_summary` keep counting the
    amount until payout. Record that split in a code comment so the next reader does not "fix" it.
  - While here, check whether the reinvest-unlock feature introduced the same drift (the card uses
    `active_reinvest_profits_sum_amount`, the dashboard uses `reinvest_profits_sum_amount`). If it
    did, **do not fix it in this commit** — report it to the user as a separate finding.
  - Files: `app/Livewire/Account/Dashboard/Index.php`
  - Depends on: 3, 11.

- [x] **Task 15: Admin — show pending body unlocks on the user card**
  - `app/MoonShine/Pages/User/UserDetailPage.php`: mirror what commit `a20103a` did for
    `reinvest_unlocked_total`.
    - Add `withSum(['pendingBodyUnlocks as body_unlocked_sum_amount' => ...], 'amount')` to the
      package query (~line 380) and `'body_unlocked_total' => (float) ($pkg->body_unlocked_sum_amount ?? 0)`
      to the mapped row (~line 408).
    - Add `Number::make('Разблокировано с тела (ожидает выплаты)', 'body_unlocked_total', ...)`
      next to the `reinvest_unlocked_total` column (~line 991), with `->showOnExport()`.
  - Files: `app/MoonShine/Pages/User/UserDetailPage.php`
  - Logging: none (admin UI).
  - Depends on: 3.

<!-- Commit checkpoint: tasks 12-15 -->

### Phase 5: Tests (Pest)

Use `php artisan make:test --pest <Name>` and follow
`tests/Feature/Livewire/ItcPackagesUnlockReinvestsTest.php` for the package/transaction setup
helper and the Russian `it(...)` descriptions used by this project.

- [x] **Task 16: `PackageBodyUnlockTest` (Livewire)**
  - Happy path: unlocking 300 of a 1000 body creates one `package_body_unlocks` row with
    `payout_at = unlocked_at->addMonthNoOverflow()` and dispatches a success notification.
  - `addMonthNoOverflow` edge case: freeze time at 31 January, assert the payout date is 28/29
    February.
  - Rejects an amount above the available body; **accepts** an amount leaving exactly 0, exactly
    100, less than 100, and 25 out of a 100 body (the revised minimum-remainder decision).
  - Rejects a PRESENT package; accepts STANDARD/PRIVILEGE/VIP.
  - Available body accounts for `partner_transfers`, `reinvest_to_body` and existing
    `balance_withdraws`, and for an already-pending unlock (two unlocks in a row cannot exceed the
    body).
  - A foreign package uuid returns 403 and creates nothing.
  - Writes a `PackageBodyUnlocked` business activity with the expected properties.
  - Files: `tests/Feature/Livewire/PackageBodyUnlockTest.php`
  - Depends on: 4, 6, 9, 11.

- [x] **Task 17: `PayoutUnlockedBodyAmountsCommandTest`**
  - A due unlock produces exactly one `WITHDRAW_PACKAGE_TO_BALANCE` transaction, one
    `package_balance_withdraws` row, credits the MAIN balance, and stamps
    `payout_transaction_uuid`.
  - A not-yet-due unlock is untouched; a cancelled one is untouched.
  - **Idempotency:** running the command twice pays once (assert transaction count and balance).
  - `--dry-run` writes nothing; `--uuid` pays a not-yet-due row but still skips a paid one.
  - Paying the last of the body sets `month_profit_percent = 0`, and so does any payout leaving the
    body below 100; a partial unlock that leaves ≥ 100 does not.
  - Files: `tests/Feature/PayoutUnlockedBodyAmountsCommandTest.php`
  - Depends on: 4, 8.

- [x] **Task 18: `PackageProfitPendingBodyUnlockExclusionTest`**
  - Model on `tests/Feature/PackageProfitUnlockedReinvestExclusionTest.php`.
  - A pending body unlock is excluded from the generated `PackageProfit` amount.
  - **The no-gap / no-overlap invariant:** generate profit with a pending unlock, run the payout,
    generate profit again — the base is the same figure both times (the pending sum is replaced by
    the `balance_withdraws` row, never subtracted twice and never briefly restored).
  - A cancelled unlock is back in the base.
  - Files: `tests/Feature/PackageProfitPendingBodyUnlockExclusionTest.php`
  - Depends on: 12, 13.

- [x] **Task 19: Settlement, double-spend and reconciliation tests**
  - `ClosePackageBodyUnlockSettlementTest`: closing a package with a pending unlock marks it
    cancelled, and a subsequent command run pays nothing (no double payout).
  - `PackageBodyUnlockDoubleSpendTest`: with a pending unlock, `withdrawPackageBalance()` cannot
    withdraw the pending amount (validation fails), and a foreign uuid is rejected with 403
    (regression test for Task 10).
  - `DashboardBodyUnlockReconciliationTest`: with a pending unlock, the dashboard "Сумма пакетов"
    equals the sum of the card figures — the standing /account-vs-ITC-tab reconciliation rule.
  - Files: `tests/Feature/ClosePackageBodyUnlockSettlementTest.php`,
    `tests/Feature/PackageBodyUnlockDoubleSpendTest.php`,
    `tests/Feature/DashboardBodyUnlockReconciliationTest.php`
  - Depends on: 10, 12, 14.

<!-- Commit checkpoint: tasks 16-19 -->

## Verification

```bash
php artisan test --compact --filter="BodyUnlock|PayoutUnlockedBodyAmounts"
php artisan test --compact                 # full suite before the final commit
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
```

Manual smoke check after `npm run build` (or `npm run dev`):
`/account/itc` → a STANDARD package card shows the new button, unlocking part of the body drops the
deposit figure, adds the line with the payout date, and `packages:payout-unlocked-body-amounts
--uuid=<PBU-…>` moves the money to the main balance.
