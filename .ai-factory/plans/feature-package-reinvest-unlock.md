# Implementation Plan: Unlock All Matured Reinvests on a Package

Branch: `feature/package-reinvest-unlock` (branched from `dev`, not `main` — the whole UI layer of
this feature lives only in `dev`)
Created: 2026-09-14

## Feature Summary

A user presses **"Снять все реинвесты"** on an ITC package card in the cabinet and confirms.

On confirm, every reinvest of that package whose unfreeze date has **already passed**:

- immediately stops taking part in dividend generation (leaves the profit base),
- is shown as a separate line under the package card together with its payout date,
- lands on the user's MAIN balance exactly one calendar month later,
- while nothing is unlocked, the line is not rendered at all.

This is the reinvest-side twin of the already implemented body unlock
(`.ai-factory/plans/package-body-unlock.md`). The mechanics, naming and safety rules mirror that
feature; only the money source differs.

## Settings
- Testing: yes (Pest)
- Logging: minimal (WARN/ERROR via `Log::` only on failure paths; business events go through
  `spatie/laravel-activitylog`)
- Docs: no (warn-only)

## Roadmap Linkage
Not applicable — `.ai-factory/ROADMAP.md` does not exist in this project.

## Critical Context: this is a rebuild, not a greenfield feature

`package-body-unlock.md:19-21` describes this feature as **already implemented** at commit
`a20103a`, with a plan file `feature-package-reinvest-unlock.md`. Neither exists in this
repository:

```
$ git cat-file -t a20103a        → fatal: Not a valid object name
$ git log --all -- '*feature-package-reinvest-unlock*'  → (nothing)
$ git stash list                 → (empty)
$ git fsck --lost-found          → 2 dangling commits, neither related
```

The implementation was lost in the failed `git stash pop` that was committed as `3d0f608` (the
commit that also left 27 conflict markers in 15 files, repaired in `0841cc1`). It is not
recoverable — it must be written again.

**What survived** (already on `dev`, do not rewrite):

| Layer | Location | State |
|---|---|---|
| Button + gating | `resources/views/components/account/itc/package.blade.php:432-443` | done |
| Confirm modal | `…/package.blade.php:114-131` | done |
| Separate sum line, hidden at zero | `…/package.blade.php:461-475` | done |
| Livewire entry point + ownership guard | `app/Livewire/Account/Itc/Packages.php:1041` | done |
| Relations `activeReinvestProfits` / `unlockedReinvestProfits` / `unlockableReinvestProfits` | `app/Models/ItcPackage.php:139-170` | done |
| Activity event `PackageReinvestUnlocked` | `app/Enums/Activity/ActivityEventTypeEnum.php:27` | done |
| Feed filtering | `app/Services/ActivityLog/ActivityFeedService.php:579` | done |
| Translations (ru/en/zh), all 4 keys | `lang/{ru,en,zh}.json`, `lang/*/activity/feed.php` | done |
| Admin export column `reinvest_unlocked_total` | `app/MoonShine/Pages/User/UserDetailPage.php:395,426,1351` | done |
| Hourly schedule entry | `routes/console.php:58` | done |

**What is missing** — everything the surviving layer calls into. Verified at runtime on `dev`:

```
unlockedReinvestProfits:   QueryException — column "unlocked_at" does not exist
unlockableReinvestProfits: RelationNotFoundException (scope unlockable() missing)
admin aggregate:           QueryException — column "unlocked_at" does not exist
UnlockMaturedReinvestsAction: MISSING
packages:payout-unlocked-reinvests: scheduled hourly, command does not exist
```

### Two live breakages on `dev` right now

1. **The admin user card is broken.** `UserDetailPage.php:395` runs
   `withSum(['unlockedReinvestProfits as reinvest_unlocked_sum_amount'])`, which queries the
   non-existent `unlocked_at`. Verified: `SQLSTATE[42703] Undefined column`. Task 1 fixes this as a
   side effect; it is the reason this plan should not sit in the backlog.
2. **The scheduler fails hourly.** `routes/console.php:58` schedules
   `packages:payout-unlocked-reinvests`; `artisan list packages` shows only
   `packages:expired-set-profit` and `packages:payout-unlocked-body-amounts`. Task 8 fixes this.

## Domain Analysis (current behaviour — verified in code)

**What a reinvest is.** One row in `package_profit_reinvests` — `uuid`, `package_uuid`, `amount`,
`matured_at`, soft-deletable. Each row *is* one amount, which is why this feature needs two nullable
columns rather than the separate ledger table the body unlock required
(`package-body-unlock.md:84`).

**The unfreeze date** is `matured_at`, set to `created_at + 180 days` when the reinvest is created
(`app/Livewire/Account/Itc/Packages.php:1028`). Legacy rows may have `matured_at = NULL`; the
`COALESCE(matured_at, created_at)` rule documented at `app/Models/ItcPackage.php:162-164` keeps them
reachable.

**How a reinvest reaches the balance today.** `PackageReinvestRepository::withdraw()`
(`app/Repositories/PackageReinvestRepository.php:34`) creates a
`WITHDRAW_PACKAGE_REINVEST_PROFIT` transaction (prefix `WPRP-`) on the MAIN balance and writes a
`PackageProfitReinvestWithdraw` row keyed by the transaction uuid. That row is what
`whereDoesntHave('withdraw')` keys off everywhere.

**The dividend base.** `AdminController::createItcPackagesProfits()`
(`app/Http/Controllers/AdminController.php:72-101`) is the only place ITC dividends are generated:

```
transaction.amount
  + reinvest_profits_sum_amount      ← reinvestProfits: whereDoesntHave('withdraw')
  + partner_transfers_sum_amount
  + reinvest_to_body_sum_amount
  − balance_withdraws_sum_amount
  − pending_body_unlocks_sum_amount
```

→ **Leaving the profit base = being subtracted from that sum.**

**The core defect.** The comment at `AdminController.php:89-90` already claims
*"activeReinvestProfits исключает и выплаченные, и разлоченные"*, and `Log::debug` at line 106
reads `$package->active_reinvest_profits_sum_amount`. But the base on line 97 uses
`reinvest_profits_sum_amount`, and `activeReinvestProfits` is **never aggregated anywhere**:

```
$ grep -rn 'activeReinvestProfits' app/ | grep -v ItcPackage.php
app/Http/Controllers/AdminController.php:90:   // activeReinvestProfits исключает …   ← a comment
```

So `active_reinvest_profits_sum_amount` is always `null` and the exclusion never happens. Task 7 is
the single most important task in this plan — without it the button changes nothing about profit.

## Design Decisions

1. **Two nullable columns on `package_profit_reinvests`**, not a new table. Each reinvest row is
   exactly one amount with one payout, so `unlocked_at` + `payout_at` carry the whole state. This is
   the distinction `package-body-unlock.md:84` draws explicitly.

2. **The payout reuses `PackageReinvestRepository::withdraw()`.** It already creates the
   `WPRP-` transaction and the `PackageProfitReinvestWithdraw` row. Once that row exists, both
   `reinvestProfits` and `activeReinvestProfits` drop the reinvest, and `unlockedReinvestProfits`
   stops counting it. While pending, only `unlocked_at` excludes it. The two states never overlap,
   so the base never drops twice — the same "no gap, no double subtraction" property the body
   unlock relies on (`package-body-unlock.md:87-92`).

3. **Exactly one calendar month** = `Carbon::addMonthNoOverflow()`. 31 Jan → 28/29 Feb, not 3 Mar.
   The confirm modal already previews the date with this exact call
   (`package.blade.php:121`), so the action must not drift from it.

4. **No amount input.** Unlike the body unlock, this action takes no amount — it unlocks *all*
   matured reinvests of the package. The Livewire method already calls
   `$action->execute($package, Auth::user())` with no amount
   (`app/Livewire/Account/Itc/Packages.php:1069`); the action signature must match.

5. **Idempotency via `whereNull('unlocked_at')` + `lockForUpdate()`** inside the transaction, and
   via the existing `PackageProfitReinvestWithdraw` uniqueness on payout — mirroring
   `PackageReinvestRepository::withdraw()`.

6. **`activeReinvestProfits` becomes the profit-base relation** rather than adding a subtracted
   `pending_reinvest_unlocks` sum. The relation already exists and already has the right predicate;
   the base just has to use it. This keeps one definition of "reinvest that still earns".

## Discovered Issues (found while planning — read before implementing)

- **[correctness, in scope] `continuePackageWork()` folds unlocked reinvests back into the body.**
  `app/Livewire/Account/Itc/Packages.php:353` collects reinvests with `whereDoesntHave('withdraw')`
  only — which includes unlocked-but-unpaid ones — credits their amount to the body via
  `ReinvestToPackageBody`, then soft-deletes them. The user is then promised a payout that the
  command can no longer find, *and* the same money is already back in the body. Task 9.
  `package-body-unlock.md:119-124` deferred this as "introduced by the reinvest unlock feature, not
  this plan's scope" — this *is* that feature, so it is in scope here.

- **[correctness, in scope] `ItcPackageRepository::closePackage()` pays every non-withdrawn
  reinvest.** Line 143 loads `reinvestProfits` with `whereDoesntHave('withdraw')` and line 167-168
  calls `$reinvestRepo->withdraw()` on each. For unlocked-but-unpaid rows this is *early* payout,
  not double payout — the `PackageProfitReinvestWithdraw` row then blocks the command. Task 10
  confirms this with a test rather than changing behaviour; early payout on close is correct.

- **[test infrastructure, blocking Task 13] `RefreshDatabase` cannot run
  `SET TRANSACTION ISOLATION LEVEL SERIALIZABLE`.** `PackageReinvestRepository::withdraw():37`
  issues it as the first statement of its transaction, exactly like
  `ItcPackageRepository::closePackage():137`. `tests/Pest.php:14` applies `RefreshDatabase` to all
  Feature tests, wrapping each in an outer transaction, so PostgreSQL rejects the statement:
  `SQLSTATE[25001] … must be called before any query`. This already fails 2 tests on `dev`
  (`ClosePackageBodyUnlockSettlementTest`). Task 11 resolves it once for both.

- **[cosmetic, out of scope] Dashboard/card drift.** `Livewire\Account\Dashboard\Index:51,63` and
  the card at `package.blade.php:303` both display `reinvest_profits_sum_amount`, which keeps
  counting an unlocked reinvest until it is paid. This is a *display* figure, not the profit base;
  the money genuinely is still the user's. Left as-is deliberately, matching the body-unlock
  decision that ranks/turnover/`user_summary` keep counting until the money lands
  (`package-body-unlock.md:40`). Noted so it is not "fixed" by accident.

## Commit Plan

- **Commit 1** (tasks 1-4): `feat: add reinvest unlock state and card aggregates`
- **Commit 2** (tasks 5-7): `feat: unlock matured reinvests and drop them from the profit base`
- **Commit 3** (tasks 8-10): `feat: pay out unlocked reinvests after one calendar month`
- **Commit 4** (tasks 11-14): `test: cover reinvest unlock, payout and settlement`

## Tasks

### Phase 1: Schema and model foundation

- [x] **Task 1: Add `unlocked_at` and `payout_at` to `package_profit_reinvests`.**
  Create via `php artisan make:migration --no-interaction`. Follow
  `database/migrations/2026_09_09_145139_create_package_body_unlocks_table.php` for style.
  - `timestamp('unlocked_at')->nullable()` — when the user pressed the button.
  - `timestamp('payout_at')->nullable()` — `unlocked_at + 1 calendar month`.
  - `index(['payout_at'])` — the payout command scans by it hourly.
  - Both nullable with no default: existing rows stay "not unlocked", no backfill needed.
  - Do **not** add a `payout_transaction_uuid`; `PackageProfitReinvestWithdraw` already carries that
    link and its own uniqueness.
  - Add a short comment in the migration body explaining that these two columns replace the separate
    ledger the body unlock needed, because one reinvest row is one amount.
  - **Verifies:** `php artisan migrate` then re-run the two runtime probes in *Critical Context* —
    both `unlockedReinvestProfits` and the admin aggregate must stop throwing.
  - **Logging:** none (schema only).
  - Files: `database/migrations/<new>_add_unlock_columns_to_package_profit_reinvests_table.php`

- [x] **Task 2: Teach `PackageProfitReinvest` about the unlock state.** (depends on 1)
  `app/Models/PackageProfitReinvest.php`. Mirror `app/Models/PackageBodyUnlock.php` exactly for
  style — it is the twin model written for the same feature family.
  - Add `'unlocked_at'`, `'payout_at'` to `$fillable`.
  - Add a `casts()` **method** (not the `$casts` property): both columns `'datetime'`.
  - Add `#[Scope]` methods using `Illuminate\Database\Eloquent\Attributes\Scope`, each with a
    `@param Builder<PackageProfitReinvest>` / `@return Builder<PackageProfitReinvest>` PHPDoc block:
    - `unlockable()` — `whereDoesntHave('withdraw')`, `whereNull('unlocked_at')`, and
      `whereRaw('COALESCE(matured_at, created_at) <= ?', [now()])`. The `COALESCE` is required:
      `app/Models/ItcPackage.php:162-164` documents that legacy rows with a NULL unfreeze date must
      stay reachable, and `ItcPackage::unlockableReinvestProfits()` already calls `->unlockable()`.
    - `unlocked()` — `whereDoesntHave('withdraw')`, `whereNotNull('unlocked_at')` (pending payout).
    - `duePayout()` — `unlocked()` plus `where('payout_at', '<=', now())`.
  - Extend the `@property` PHPDoc block with `unlocked_at` / `payout_at` as
    `\Illuminate\Support\Carbon|null`, matching the existing block's shape.
  - **Verifies:** `ItcPackage::query()->with('unlockableReinvestProfits')->first()` no longer throws
    `RelationNotFoundException`.
  - **Logging:** none (model state only).
  - Files: `app/Models/PackageProfitReinvest.php`

- [x] **Task 3: Add factory states for the unlock lifecycle.** (depends on 1, 2)
  `database/factories/PackageProfitReinvestFactory.php`. Follow
  `database/factories/PackageBodyUnlockFactory.php` for style.
  - `matured()` — `matured_at` in the past (unlockable).
  - `notMatured()` — `matured_at` in the future.
  - `legacy()` — `matured_at = null` (exercises the `COALESCE` branch).
  - `unlocked()` — `unlocked_at = now()`, `payout_at = now()->addMonthNoOverflow()`.
  - `duePayout()` — `unlocked_at` a month ago, `payout_at` in the past.
  - Project rule: tests must use factory states, never hand-hydrated models
    (`.ai-factory/rules/base.md`, Testing).
  - **Logging:** none.
  - Files: `database/factories/PackageProfitReinvestFactory.php`

### Phase 2: Wire the existing UI to real data

- [x] **Task 4: Add the four missing card aggregates.** (depends on 1, 2)
  `app/Models/ItcPackage.php`, scope `userPackagesWithFinancials()` (line 330) — the query
  `Packages::render()` (line 1269) actually uses. Append next to the existing
  `pendingBodyUnlocks` lines (343-344), using the identical
  `COALESCE(SUM(amount),0)` form:
  - `withCount(['unlockableReinvestProfits'])` → `unlockable_reinvest_profits_count`
  - `withSum(['unlockableReinvestProfits'])` → `unlockable_reinvest_profits_sum_amount`
  - `withSum(['unlockedReinvestProfits'])` → `unlocked_reinvest_profits_sum_amount`
  - `withMin('unlockedReinvestProfits', 'payout_at')` → `unlocked_reinvest_profits_min_payout_at`
  - These four names are **fixed by the existing blade** — `package.blade.php:432,438,461,468,469`
    reads exactly these attributes. Renaming any of them silently hides the button or the line,
    because every read is `?? 0` / `?? null` guarded.
  - **Verifies:** with a matured reinvest seeded, the button renders; with an unlocked one, the
    separate line renders with a real date instead of `—`; with neither, nothing renders.
  - **Logging:** none (query shape only).
  - Files: `app/Models/ItcPackage.php`

### Phase 3: The unlock action

- [x] **Task 5: Add `UnlockMaturedReinvestsResult` DTO.**
  `app/Dto/Packages/UnlockMaturedReinvestsResult.php`. Copy the shape of
  `app/Dto/Packages/UnlockPackageBodyResult.php` — `final readonly`, `declare(strict_types=1)`,
  constructor property promotion.
  - `public BigDecimal $amount` — total unlocked (`Brick\Math\BigDecimal`, never float).
  - `public CarbonInterface $payoutAt`.
  - `public int $count` — how many reinvest rows were unlocked.
  - The first two fields are **fixed by the caller**: `Packages::unlockMaturedReinvests()`
    (`app/Livewire/Account/Itc/Packages.php:1069-1070`) already reads `$result->amount` and
    `$result->payoutAt`.
  - **Logging:** none (value object).
  - Files: `app/Dto/Packages/UnlockMaturedReinvestsResult.php`

- [x] **Task 6: Implement `UnlockMaturedReinvestsAction`.** (depends on 2, 5)
  `app/Actions/Packages/UnlockMaturedReinvestsAction.php`. Mirror
  `app/Actions/Packages/UnlockPackageBodyAmountAction.php` — same shape, same guard order.
  - Signature **fixed by the caller**: `execute(ItcPackage $package, ?User $causer = null): UnlockMaturedReinvestsResult`.
    No amount argument (Design Decision 4).
  - Wrap everything in `DB::transaction()`.
  - Re-load the package `->lockForUpdate()` inside the transaction, then select its reinvests with
    `->unlockable()->lockForUpdate()`. The `whereNull('unlocked_at')` inside the scope plus the row
    lock is what makes a double click a no-op instead of a second unlock.
  - If nothing is unlockable, throw `InvalidAmountException` with an existing translated message —
    do not invent a new lang key unless none fits; all four UI keys already exist in ru/en/zh and
    adding a fifth means touching three files.
  - `$unlockedAt = now()`, `$payoutAt = $unlockedAt->copy()->addMonthNoOverflow()` — must match the
    modal preview at `package.blade.php:121`.
  - Bulk-`update()` the selected rows with both timestamps; sum the amounts with `BigDecimal`
    (never float — `.ai-factory/rules/base.md`, Financial Values).
  - Write the business activity via the same logger the body action injects:
    `type: ActivityEventTypeEnum::PackageReinvestUnlocked` (already defined),
    `feeds: [Packages, UserDetailUser]`, properties `amount`, `package_uuid`, `payout_at`, `count`,
    `causer: $causer ?? auth()->user()`, `logName: 'packages'`,
    `context: auth()->check() ? 'account' : 'system'`. The `payout_at` property is required —
    `ActivityManager::formatDate()` renders it into the `:date` placeholder of
    `activity/feed.business.package_reinvest_unlocked`, which already exists in all three languages.
  - **Logging (minimal):** no `Log::debug`. `Log::warning` only when the action rejects the request
    (nothing unlockable), with `package_uuid` and `user_id`. Format:
    `[UnlockMaturedReinvestsAction.execute] message {data}`. The caller already emits its own
    `Log::debug` at `Packages.php:1043`; leave it, do not add more.
  - Files: `app/Actions/Packages/UnlockMaturedReinvestsAction.php`

### Phase 4: The profit base (core mechanic)

- [x] **Task 7: Drop unlocked reinvests from the dividend base.** (depends on 1, 2)
  `app/Http/Controllers/AdminController.php`, `createItcPackagesProfits()`.
  - Add `->withSum(['activeReinvestProfits' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')`
    next to the existing `withSum` calls (lines 82-87).
  - Change the base on line 97 from `->plus($package->reinvest_profits_sum_amount)` to
    `->plus($package->active_reinvest_profits_sum_amount ?? 0)`.
  - **This is the task that makes the button mean anything.** The comment on lines 89-90 and the
    `Log::debug` on line 106 already describe this behaviour; today the code does not implement it
    and `active_reinvest_profits_sum_amount` is always `null`.
  - Do **not** also subtract a separate pending sum — `activeReinvestProfits` already excludes both
    withdrawn and unlocked rows (`app/Models/ItcPackage.php:139-144`). Subtracting as well would
    remove the money twice.
  - Keep `reinvestProfits` in the `withSum` list only if something else still reads
    `reinvest_profits_sum_amount`; grep before removing.
  - **Logging (minimal):** the existing `Log::debug` at line 106 becomes truthful once the aggregate
    is loaded — keep it as the one exception, since it is the only visibility into a nightly money
    calculation. Add nothing new.
  - Files: `app/Http/Controllers/AdminController.php`

### Phase 5: Payout after one calendar month

- [x] **Task 8: Implement `packages:payout-unlocked-reinvests`.** (depends on 2, 5)
  `app/Console/Commands/Packages/PayoutUnlockedReinvestsCommand.php`. Copy the structure of
  `app/Console/Commands/Packages/PayoutUnlockedBodyAmountsCommand.php`.
  - **The schedule entry already exists** (`routes/console.php:58`, hourly, `withoutOverlapping()`,
    `onOneServer()`), and is currently failing because the command does not exist. Do not add a
    second entry; only confirm the signature matches `packages:payout-unlocked-reinvests`.
  - Mirror the body command's options (`--dry-run` / limit / package filter — copy whatever it
    exposes at line 37-40) so the two commands are operable the same way.
  - Select with the `duePayout()` scope from Task 2, and page with `chunkById(100, …)` — no
    `orderBy`, matching the comment at `PayoutUnlockedBodyAmountsCommand.php:62-63`.
  - Per row: `lockForUpdate()`, re-check it is still unpaid, then delegate to
    `PackageReinvestRepository::withdraw($uuid, $transactionRepo, writeAdminAudit: false)`. It
    already creates the `WPRP-` MAIN-balance transaction and the `PackageProfitReinvestWithdraw`
    row that makes the payout idempotent. Pass `false` for the audit flag, as
    `ItcPackageRepository::closePackage()` does for system-initiated payouts.
  - Do **not** re-issue `SET TRANSACTION ISOLATION LEVEL SERIALIZABLE` around the call —
    `withdraw()` already opens its own transaction and issues it as that transaction's first
    statement. Wrapping it would break the statement (see Task 11).
  - Reuse the body command's serialization-failure retry helper (`isSerializationFailure()`,
    line 229) so concurrent package closing does not lose a payout.
  - **Logging (minimal):** `Log::warning` on a skipped row (already paid, package gone),
    `Log::error` on a failed payout with `reinvest_uuid`, `package_uuid` and the exception message.
    Per-row success goes to command output only, not the log. Format:
    `[PayoutUnlockedReinvestsCommand.handle] message {data}`.
  - **Verifies:** `php artisan schedule:list` shows the entry resolving, and
    `php artisan packages:payout-unlocked-reinvests --dry-run` runs without error.
  - Files: `app/Console/Commands/Packages/PayoutUnlockedReinvestsCommand.php`

### Phase 6: Settlement integrity

- [x] **Task 9: Stop `continuePackageWork()` from reclaiming unlocked reinvests.** (depends on 2)
  `app/Livewire/Account/Itc/Packages.php:353`.
  - The reinvest collection inside it uses `whereDoesntHave('withdraw')` only, so it sweeps up
    unlocked-but-unpaid rows, credits them to the package body via `ReinvestToPackageBody`, and
    soft-deletes them — cancelling a payout the user was already promised while the money
    simultaneously returns to the body.
  - Add `->whereNull('unlocked_at')` to that query so unlocked reinvests are left alone: they have
    already left the profit base and belong to the pending payout.
  - Add a short comment in the style of the body-unlock comments explaining that an unlocked
    reinvest is already committed to a payout and must not be folded back.
  - **Logging (minimal):** none on the happy path.
  - Files: `app/Livewire/Account/Itc/Packages.php`

- [x] **Task 10: Confirm package closing settles pending unlocks exactly once.** (depends on 2, 8)
  `app/Repositories/ItcPackageRepository.php:131` (`closePackage()`).
  - Current behaviour: line 143 loads `reinvestProfits` (`whereDoesntHave('withdraw')`) and lines
    167-168 withdraw each one. For an unlocked-but-unpaid reinvest this pays it out *early*, which
    is correct — the package is closing, the money is the user's.
  - The safety property to establish is that the payout command can never pay it a second time: the
    `PackageProfitReinvestWithdraw` row written by `withdraw()` removes the row from `duePayout()`.
  - **Expected outcome: no production change** — this task is the analysis plus the regression test
    in Task 14. If the analysis turns out wrong, fix it here and say so explicitly in the commit.
  - Files: `app/Repositories/ItcPackageRepository.php` (only if the analysis proves a defect)

### Phase 7: Tests

- [x] **Task 11: Unblock `SERIALIZABLE` under `RefreshDatabase`.** (depends on 8)
  This blocks Tasks 13 and 14 and already fails 2 tests on `dev`.
  - `tests/Pest.php:14` applies `RefreshDatabase` to every Feature test, wrapping each in an outer
    transaction. `PackageReinvestRepository::withdraw():37` and
    `ItcPackageRepository::closePackage():137` both issue
    `SET TRANSACTION ISOLATION LEVEL SERIALIZABLE` as their transaction's first statement, which
    PostgreSQL rejects inside a savepoint: `SQLSTATE[25001]`.
  - Preferred fix: guard both call sites with `if (DB::transactionLevel() === 1)`. Inside a nested
    savepoint the statement is meaningless *and* illegal, so skipping it is correct behaviour rather
    than a test accommodation — production paths still run at level 1 and keep full isolation.
  - Apply the same guard to both call sites in one commit; leave a comment saying why.
  - **Verifies:** `tests/Feature/ClosePackageBodyUnlockSettlementTest.php` (currently 2 failures on
    `dev`) turns green without being modified.
  - Files: `app/Repositories/PackageReinvestRepository.php`,
    `app/Repositories/ItcPackageRepository.php`

- [x] **Task 12: Test the unlock action.** (depends on 3, 6)
  New Pest feature test. Model the package/transaction setup on
  `tests/Feature/Livewire/PackageBodyUnlockTest.php`.
  - Unlocks only reinvests whose unfreeze date has passed; leaves not-yet-matured ones untouched.
  - A `matured_at = null` legacy reinvest **is** unlockable (the `COALESCE` branch).
  - `payout_at` is exactly one calendar month later — assert the 31 Jan → 28/29 Feb edge explicitly
    via `Carbon::setTestNow()`, not just `addMonth()`.
  - Pressing twice unlocks nothing the second time and does not move `payout_at`.
  - A foreign user calling `unlockMaturedReinvests()` with someone else's uuid gets 403
    (the guard at `Packages.php:1060` — assert it, it is the money-safety boundary).
  - Asserts the `package_reinvest_unlocked` activity row is written with a `payout_at` property.
  - Files: `tests/Feature/Livewire/ItcPackagesUnlockReinvestsTest.php`

- [x] **Task 13: Test profit-base exclusion and payout.** (depends on 7, 8, 11)
  - **Exclusion:** a package with one matured reinvest generates dividends on
    `body + reinvest`; after unlocking, the next accrual run generates dividends on `body` only.
    Assert the generated `PackageProfit.amount` before and after explicitly
    (`.ai-factory/rules/base.md`: financial tests assert before *and* after).
  - **Payout:** running the command before `payout_at` pays nothing; running it after creates
    exactly one `WPRP-` MAIN-balance transaction for the right amount and one
    `PackageProfitReinvestWithdraw` row.
  - **Idempotency:** running the command twice in a row produces exactly one transaction.
  - **No double subtraction:** once paid, the profit base does not drop a second time — the
    reinvest simply stays out.
  - Files: `tests/Feature/PackageProfitUnlockedReinvestExclusionTest.php`,
    `tests/Feature/PayoutUnlockedReinvestsCommandTest.php`

- [x] **Task 14: Test settlement paths.** (depends on 9, 10, 11)
  - `continuePackageWork()` on a package with one unlocked and one active reinvest folds only the
    active one into the body; the unlocked one survives and is still paid by the command
    (regression for the Task 9 defect).
  - `closePackage()` on a package with a pending unlocked reinvest pays it exactly once, and a
    subsequent command run pays nothing (regression for Task 10).
  - Files: `tests/Feature/PackageReinvestUnlockSettlementTest.php`

## Verification

Run after each commit checkpoint:

```
docker compose exec -T app php artisan test --compact
vendor/bin/pint --dirty --format agent
```

Tests must be run **inside the container** — the host cannot resolve the `pgsql` database host.

Baseline on `dev` before this plan: **348 passed, 2 failed**
(`ClosePackageBodyUnlockSettlementTest`, resolved by Task 11). The plan is not done until the suite
is fully green.
