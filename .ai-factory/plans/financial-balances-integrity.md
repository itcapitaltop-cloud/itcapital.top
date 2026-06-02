# Financial Balances Integrity: Single-Source Summary, Idempotent Reinvest, Reversal Corrections

**Branch:** dev (no feature branch — staying on dev per user choice)
**Created:** 2026-06-02
**Type:** Refactor + bug fix (financial integrity, performance)

## Context / Problem

The system has **four divergent definitions of "balance"** that have drifted apart:

1. `TransactionRepository::getBalanceAmountByUserIdAndType` (PHP, driven by `TrxTypeEnum::getDebits()/getCredits()`) — used by user dashboard (ЛК). **Authoritative.**
2. PL/pgSQL trigger `trg_user_summary_on_transaction` — maintains `user_summary` on every write. Type lists currently match the enum, but it **ignores `rejected_at`** and treats `accepted_at` inconsistently.
3. PL/pgSQL function `refresh_user_summary()` — **stale**: only 5 debit / 2 credit types, missing all partner/bonus/accrual types. Corrupts data if run.
4. `MoonShine UserResource` partner_balance subquery — its own 4th variant.

The admin panel (MoonShine `UserResource`, `UserIndexPage`, `UserDetailPage`, `AdminController`) reads `user_summary`; the user dashboard computes live. Measured drift on the dev DB sample: **116 of 445 users mismatch on `investments_sum`, total abs diff ≈ 223 432 ITC.** Production user count is larger, so all reconcile/backfill work MUST be chunked and resumable — never assume the dev sample size.

Root cause: balance logic is duplicated across PHP and SQL with no mechanism to keep them in sync. Adding a new `TrxTypeEnum` case silently breaks the triggers and `refresh_user_summary()`.

Secondary issues addressed by this plan:
- **Double reinvest** (`Packages::profitReinvest`): no `DB::transaction`, no lock, no idempotency → a retried/double-submitted request creates two reinvests of the same dividends (the SvetLana case).
- **`DB::raw('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE')`** is a no-op (returns an Expression, never executes) in `checkBalanceAndStore`, `withdrawProfit`, `whenCurrentProfitAmountIsPositive` — the intended SERIALIZABLE isolation is never applied.
- **Manual DB edits** of balances (admin overwrites `investments_sum`/`partner_balance`) get stomped by triggers and bypass the ledger.
- **Dashboard weight up to 5 MB**: `Dashboard/Index` loads the full transaction history via `->get()` (no pagination) and sums collections in PHP.

## Target Architecture (decided)

- **`transactions` = append-only ledger**, the single source of truth. Corrections are new reversing/adjustment transactions, not edits/deletes.
- **`user_summary` = pure projection**, maintained by **one** PHP writer (`UserSummaryService`) driven by `TrxTypeEnum`. No DB triggers.
- Keep the ES *principles* (immutable ledger, rebuildable projection, idempotent commands); do **not** adopt Spatie Event Sourcing / a CQRS framework.

## Settings

- **Testing:** Yes — Pest. Reconciliation (summary == ledger), reinvest idempotency/concurrency regression, `rejected_at` exclusion regression, reversal-correction outcome, dashboard pagination.
- **Logging:** Verbose (DEBUG) during development — log computed summary values, divergences, reconcile progress, reinvest locking. Use `spatie/laravel-activitylog` for admin-facing business events (reversals).
- **Docs:** Warn-only (no mandatory docs checkpoint).
- **Static analysis / format:** `vendor/bin/pint`, `vendor/bin/phpstan analyse` must pass.

## Roadmap Linkage

- Milestone: "none"
- Rationale: No ROADMAP.md present in the project.

## Money / Concurrency Rules (from ARCHITECTURE.md)

- No floats for financial values — use `brick/math` / `brick/money` / decimal strings as the surrounding code does.
- Balance-affecting writes are transactional with row-level locking.
- Backfills are resumable / chunked / scoped — production data volume is large.

---

## Tasks

### Phase 1 — Single source of truth for the projection

- [x] **T1. Extract one canonical balance formula driven by `TrxTypeEnum`.**
  Today the debit/credit + `accepted_at`/`rejected_at` rules live only inside `TransactionRepository::getBalanceAmountByUserIdAndType`. Extract this into a single reusable computation (e.g. `app/Services/User/UserBalanceCalculator.php` or a query helper) that both the repository method and the new `UserSummaryService` call — so there is exactly one implementation.
  - Define the canonical `balance_type → summary field` mapping explicitly and document it in the class PHPDoc:
    - `investments_sum` = MAIN-balance per `getDebits()/getCredits()` with `rejected_at IS NULL` (debits also require `accepted_at IS NOT NULL`).
    - `partner_balance` = PARTNER-balance with the same sign rules.
    - `reinvests_sum` = Σ `package_profit_reinvests.amount` − Σ reinvest-withdraws (`package_profit_reinvest_withdraws`).
    - `buy_packages_sum` = Σ accepted `buy_package`.
    - `partners_count`, `first_package_at`.
  - **Decision to encode:** the old trigger lumped `regular_premium`/`staking` balance types into `investments_sum` via `balance_type <> 'partner'`; the live calc counts only MAIN. Standardize on **MAIN-only** for `investments_sum` and log any per-user delta this introduces during reconcile (T6) so it is visible, not silent.
  - Files: `app/Services/User/UserBalanceCalculator.php` (new), `app/Repositories/TransactionRepository.php` (delegate to it), `app/Enums/Transactions/TrxTypeEnum.php` (reference only).
  - Logging: DEBUG the resolved debit/credit sets and computed per-type balances.

- [x] **T2. `UserSummaryService::recompute(int $userId): void`.**
  Recomputes one user's `user_summary` row from the ledger using T1's calculator and upserts it, inside `DB::transaction`. Single-user recompute is cheap and synchronous.
  - Files: `app/Services/User/UserSummaryService.php` (new).
  - Logging: DEBUG old vs new values per field on each recompute.

- [x] **T3. `TransactionObserver` + reinvest/partner observers → recompute.**
  Add `app/Observers/TransactionObserver.php` (`created`/`updated`/`deleted`) calling `UserSummaryService::recompute($transaction->user_id)`. Extend the affected-user recompute to reinvest events: hook `PackageProfitReinvestObserver`, `PackageProfitReinvestWithdrawObserver`, and a `PartnerObserver` (resolve owning user via package→transaction / `partner_id`) to call `recompute`. Register all in `app/Providers/AppServiceProvider.php` next to existing `::observe()` calls. Reuse the existing observer pattern; keep activity-logging observers untouched (add recompute alongside).
  - Files: `app/Observers/TransactionObserver.php` (new), `app/Observers/PartnerObserver.php` (new), edits to existing reinvest/reinvest-withdraw observers, `app/Providers/AppServiceProvider.php`.
  - Logging: DEBUG which user_id was recomputed and the triggering event.

**Commit checkpoint:** `feat(summary): maintain user_summary projection from PHP via TrxTypeEnum`

### Phase 2 — Remove the DB triggers/functions

- [x] **T4. Migration: drop all summary triggers + functions.**
  `DROP TRIGGER`/`DROP FUNCTION` for: `trg_user_summary_on_transaction`, `trg_user_summary_on_reinvest`, `trg_user_summary_on_reinvest_withdraw`, `trg_user_summary_on_partner`, `trg_user_summary_on_user_insert`, and `refresh_user_summary`. Replace the `users` insert trigger behavior (create empty `user_summary` row) with `UserSummaryService` / a `UserObserver::created` recompute, so new users still get a row. `down()` recreates the dropped objects from their captured current definitions for rollback safety. Document assumptions in the migration body.
  - Must land **after** Phase 1 so there is no window where nothing maintains `user_summary`.
  - Files: `database/migrations/XXXX_drop_user_summary_triggers.php` (new), optional `app/Observers/UserObserver.php`.

**Commit checkpoint:** `refactor(summary): drop divergent PL/pgSQL summary triggers and functions`

### Phase 3 — Reconcile command (scale-aware) + schedule + one-off backfill

- [x] **T5. `summary:reconcile` artisan command.**
  Chunked recompute over all users via `chunkById` (NOT `get()`), reusing `UserSummaryService`. Options: `--user=` (single), `--dry-run` (report divergences without writing), `--chunk=` (default e.g. 500). Resumable and safe to re-run. Built for large production user counts.
  - Files: `app/Console/Commands/SummaryReconcileCommand.php` (new).
  - Logging: INFO progress per chunk; WARN per-user divergence (id + field + old vs computed) in dry-run.

- [x] **T6. Schedule `summary:reconcile` nightly.**
  Add to `routes/console.php` at a low-traffic time as a self-healing safety net (full recompute, or `--dry-run` + alert — decide during impl).
  - Files: `routes/console.php`.

- [x] **T7. One-off reconcile (operational).** Applied on dev: 441 processed, follow-up dry-run 0 mismatched.
  Run `summary:reconcile --dry-run` first to quantify drift on production-sized data, capture the report, then run the real reconcile to align all divergent users. Verify admin grid numbers match the dashboard afterward.

**Commit checkpoint:** `feat(summary): add chunked summary:reconcile command + nightly schedule`

### Phase 4 — Double-reinvest fix

- [ ] **T8. Make `Packages::profitReinvest` idempotent and atomic.**
  Wrap in `DB::transaction`; `lockForUpdate` the package row; recompute the available dividend amount under the lock; link the created `PackageProfitReinvest` to the consumed `PackageProfit` rows via `PackageProfitWithReinvestLink` (mirror `reinvestOneProfit`); reject if nothing is available. Replace `DB::raw('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE')` with `DB::statement(...)` here and in `TransactionRepository::checkBalanceAndStore`, `Packages::withdrawProfit`, `ItcPackageRepository::whenCurrentProfitAmountIsPositive`.
  - Files: `app/Livewire/Account/Itc/Packages.php`, `app/Repositories/TransactionRepository.php`, `app/Repositories/ItcPackageRepository.php`.
  - Logging: DEBUG lock acquisition, computed amount, consumed profit uuids.

- [ ] **T9. Frontend double-submit guard.**
  Add `wire:loading.attr="disabled"` + `wire:target="profitReinvest"` (and the withdraw/continue actions) to the relevant buttons so a frozen connection / double click cannot resubmit.
  - Files: `resources/views/livewire/account/itc/packages.blade.php` (+ any reinvest button partials).

**Commit checkpoint:** `fix(reinvest): make profit reinvest idempotent under concurrency`

### Phase 5 — Reversal corrections (immutable ledger for admin)

- [ ] **T10. Admin balance correction via reversing transaction.**
  Replace direct edits of `investments_sum`/`partner_balance` (`AdminController`, `MoonShine UserResource`) with an action/service that posts a balancing adjustment transaction through the ledger (existing adjustment trx_type or a new dedicated one added to `TrxTypeEnum` + `getDebits/getCredits`), logged via `spatie/laravel-activitylog`. Summary updates automatically via the T3 observer. Remove/guard the raw summary-column edit path.
  - Files: `app/Actions/...` (new), `app/Http/Controllers/AdminController.php`, `app/MoonShine/Resources/UserResource.php`, possibly `app/Enums/Transactions/TrxTypeEnum.php`.

- [ ] **T11. Admin "reverse reinvest" action.**
  One-click cancellation of an erroneous `PackageProfitReinvest` (the SvetLana case) via a reversal entry (`PackageProfitReinvestWithdraw` / reversing record) + activity log — no manual DB access. Recompute follows via observers.
  - Files: `app/Actions/...` (new), relevant MoonShine resource/page.

**Commit checkpoint:** `feat(admin): ledger-based balance corrections and reinvest reversal`

### Phase 6 — Dashboard performance (5 MB)

- [ ] **T12. Paginate dashboard transactions and move sums to SQL.**
  Replace the unbounded `Transaction::...->get()` in `Dashboard/Index::render` with pagination (`WithPagination` or an explicit limit + "load more"). Convert `depositTotalAmount` from `->get()->sum(...)` to a SQL aggregate. Merge the two duplicated 5-level partner traversals (`partnersInLines`/`partnersTotal`) into one pass (or use the existing `PartnerClosure`). Now that `user_summary` is trustworthy, read balances/partner counts from it where appropriate. Add indexes if `EXPLAIN ANALYZE` shows a need.
  - Files: `app/Livewire/Account/Dashboard/Index.php`, related Blade view, optional migration for indexes.
  - Logging: none required; verify reduced payload manually.

**Commit checkpoint:** `perf(dashboard): paginate transactions and aggregate sums in SQL`

### Phase 7 — Tests & verification

- [ ] **T13. Pest coverage.**
  - Reconciliation: for seeded users with mixed trx types (incl. rejected and partner/bonus types), `user_summary` after recompute == `UserBalanceCalculator` live values, per field.
  - `rejected_at` regression: a rejected transaction is excluded from both live and summary (the old trigger bug).
  - Reinvest idempotency: simulated double-submit / concurrent call creates exactly one reinvest; second is rejected.
  - Reversal correction: admin adjustment posts a transaction and summary reflects it; no raw column edit.
  - Dashboard: transactions are paginated (bounded query), totals correct.
  - Files: `tests/Feature/...`, `tests/Unit/UserBalanceCalculatorTest.php`.

- [ ] **T14. Static analysis & format.**
  Run `vendor/bin/pint`, `vendor/bin/phpstan analyse`, and `php artisan test --compact` for affected suites; fix findings.

---

## Commit Plan (checkpoints)

1. After Phase 1 — `feat(summary): maintain user_summary projection from PHP via TrxTypeEnum`
2. After Phase 2 — `refactor(summary): drop divergent PL/pgSQL summary triggers and functions`
3. After Phase 3 — `feat(summary): add chunked summary:reconcile command + nightly schedule`
4. After Phase 4 — `fix(reinvest): make profit reinvest idempotent under concurrency`
5. After Phase 5 — `feat(admin): ledger-based balance corrections and reinvest reversal`
6. After Phase 6 — `perf(dashboard): paginate transactions and aggregate sums in SQL`
7. After Phase 7 — `test: reconciliation, reinvest idempotency, reversal, dashboard regressions`

## Sequencing / Dependencies

- Phase 1 (T1→T2→T3) before Phase 2 (T4): the PHP projection must be live before triggers are dropped — no maintenance gap.
- Phase 3 (T5→T7) after Phase 2: reconcile aligns data once the single writer is the only writer.
- Phases 4, 5, 6 are independent of each other and can follow once Phase 1–3 are stable; Phase 5 (T10) benefits from T1's `TrxTypeEnum` adjustment type.
- Phase 7 spans all; write tests alongside each phase, run the full pass at the end.

## Risks / Notes

- Production user volume ≫ dev sample — every loop over users uses `chunkById`, never `get()`.
- Dropping triggers is reversible via `down()`; keep the captured definitions in the migration.
- The `investments_sum` MAIN-only decision (T1) may shift admin-displayed numbers for users with `regular_premium`/`staking` balance types — surface these deltas in the T7 dry-run before applying.
- `user_summary` is consumed by MoonShine sorting/filtering — verify those still work after recompute changes column semantics.
