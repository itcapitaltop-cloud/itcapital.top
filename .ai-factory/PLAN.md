<<<<<<< Updated upstream
# Implementation Plan: Исправление логики ручного и естественного ранга

Branch: none
Created: 2026-05-09

## Settings
- Testing: no
- Logging: verbose
- Docs: no

## Goal
Исправить поведение ручного ранга так, чтобы пользователь всегда продолжал накапливать фактический оборот по линиям и естественный ранг, а отключение переключателя `Ранг установлен вручную` возвращало пользователя к естественному рангу без обнуления дохода/прогресса по линиям.

## Requirements
- Естественный ранг должен считаться по фактическим личным депозитам и оборотам линий независимо от ручного ранга.
- При включенном ручном ранге расчет прогресса должен стартовать от минимально допустимого кумулятивного оборота для выставленного ранга.
- Требования рангов, которые пользователь проскочил за счет ручного назначения, должны считаться полностью заполненными.
- Фактический оборот линии должен сохраняться полностью и попадать в прогресс следующего ранга поверх заполненной базы ручного ранга.
- Новый оборот после включения ручного ранга должен входить в фактический оборот и добавляться поверх расчетной базы.
- При отключении ручного ранга поля override очищаются, затем `users.rank` пересчитывается к естественному значению по реальным данным без бонуса и без обнуления прогресса.

## Relevant Files
- `app/Services/User/UserRankServices.php`
- `app/Actions/User/Partner/ProgressBarAction.php`
- `app/Livewire/Account/Partners/Partners.php`
- `app/MoonShine/Resources/UserResource.php`
- `app/Console/Commands/User/UseUserRankCommand.php`
- `app/Console/Commands/UsersRecalcRankCommand.php`
- `resources/docs/1.0/manual-rank.md`

## Tasks

### Phase 1: Separate Natural And Effective Rank Concepts
- [x] Task 1: Audit and adjust `app/Services/User/UserRankServices.php` so `calculateRank(User $user)` can reliably calculate the natural rank from factual data without using manual-rank baselines when the override is being disabled.
  - Deliverable: rank calculation has a clear path for natural/factual turnover and a separate path for effective manual-rank turnover where needed.
  - Expected behavior: disabling `overridden_rank` and running `user:use-rank --no-bonus` returns `users.rank` to the user's factual rank, not `0` unless factual data truly does not meet any rank.
  - Files: `app/Services/User/UserRankServices.php`, optionally `app/Console/Commands/User/UseUserRankCommand.php` if command-level parameters need to clarify natural recalculation.
  - Dependency notes: this is the foundation for all later UI and admin behavior.
  - Logging requirements: add DEBUG logs around rank calculation entry, selected mode (`natural` vs `manual_effective` if introduced), personal deposit, per-line factual turnover, per-line effective turnover, requirement failures, and final rank; log WARNING only for missing/invalid rank requirement data; do not log sensitive user data beyond IDs and numeric calculation inputs.

### Phase 2: Preserve Manual Rank Baseline And Line Excess
- [x] Task 2: Normalize manual-rank line turnover formula in `app/Services/User/UserRankServices.php` to match the required formula: `effective = max(turnover_before_manual_rank, cumulative_base_for_manual_rank_line) + turnover_since_manual_rank`.
  - Deliverable: manual rank calculation preserves excess on strong lines and injects only the missing base on weak lines.
  - Expected behavior: for manual rank 5 with base line 1 = 4000 and base line 2 = 5000, factual line 1 = 6500 and line 2 = 3000 produces effective line 1 = 6500 and effective line 2 = 5000 before new turnover; later turnover increases both lines from that point.
  - Files: `app/Services/User/UserRankServices.php`.
  - Dependency notes: depends on Task 1 terminology/flow so natural and effective calculations do not overwrite each other.
  - Logging requirements: log DEBUG entries for `base_amount`, `before_amount`, `since_amount`, `effective_amount`, `line`, `manual_rank`, and `overridden_rank_from`; log INFO only when a recalculation changes persisted rank; keep logs structured as `[UserRankServices.method] message {data}`.

### Phase 3: Fix Admin Toggle Disable Flow
- [x] Task 3: Update `app/MoonShine/Resources/UserResource.php::saveLevelOverride()` so disabling `Ранг установлен вручную` clears override fields and recalculates the natural rank in a deterministic order without leaving stale manual-rank state in memory.
  - Deliverable: admin toggle disable persists `overridden_rank = false`, clears `overridden_rank_from`, refreshes the user or passes a clean model to recalculation, and stores the natural rank returned by the rank service/command.
  - Expected behavior: when the admin disables manual rank, user rank becomes the natural rank based on existing turnover and progress bars reflect real factual progress instead of zeroed line income.
  - Files: `app/MoonShine/Resources/UserResource.php`, optionally `app/Services/User/UserRankServices.php` if direct service use is safer than `Artisan::call()`.
  - Dependency notes: depends on Tasks 1 and 2; do not change percent override behavior in the same task except where it shares the save flow.
  - Logging requirements: log DEBUG before and after the toggle branch with `user_id`, old rank, requested rank, old override state, new override state, and `overridden_rank_from`; log INFO when manual rank is disabled and natural recalculation completes; log ERROR with exception context if recalculation fails.

### Phase 4: Align Progress Bars With Rank Calculation
- [x] Task 4: Make progress bar calculations consistent between `app/Actions/User/Partner/ProgressBarAction.php` and `app/Livewire/Account/Partners/Partners.php` so both use the same manual-rank baseline and excess formula.
  - Deliverable: user-facing and admin progress bars show identical line progress rules for manual rank and natural rank.
  - Expected behavior: progress toward the next rank starts from the full factual line turnover above the filled skipped-rank base; after disabling manual rank, progress is based only on factual cumulative turnover.
  - Files: `app/Actions/User/Partner/ProgressBarAction.php`, `app/Livewire/Account/Partners/Partners.php`, optionally extract a small shared calculator under `app/Services/User/` or `app/Actions/User/Partner/` if it reduces duplication without broad rewrite.
  - Dependency notes: depends on Tasks 1 and 2; prefer the smallest safe refactor and avoid changing unrelated partner dashboard transfer logic.
  - Logging requirements: keep DEBUG logs for effective turnover calculation with `user_id`, `line`, `target`, `cumulative_to_next`, `base_amount`, `before_amount`, `since_amount`, `effective_amount`, and calculated `current`; avoid INFO logs on every page render unless an unusual fallback happens.

### Phase 5: Reconcile Legacy Command And Documentation
- [x] Task 5: Review `app/Console/Commands/UsersRecalcRankCommand.php` for duplicated legacy rank logic and either route it through `UserRankServices` or explicitly align its manual-rank handling with the corrected service behavior.
  - Deliverable: bulk/legacy recalculation cannot reintroduce the old zeroing or inconsistent manual-rank calculation.
  - Expected behavior: both `user:use-rank` and any legacy recalculation path produce the same natural rank after override disable and the same effective rank while override is enabled.
  - Files: `app/Console/Commands/UsersRecalcRankCommand.php`, `app/Console/Commands/User/UseUserRankCommand.php`, `app/Services/User/UserRankServices.php`.
  - Dependency notes: depends on Tasks 1 through 3; if `UsersRecalcRankCommand` is unused, still document the decision in a concise code comment or remove duplication only if safe.
  - Logging requirements: log INFO at command start/end with processed user count; log DEBUG for per-user recalculation only if existing command verbosity/log-level conventions allow it; log ERROR for per-user failures with `user_id` and exception class/message.

- [x] Task 6: Update `resources/docs/1.0/manual-rank.md` only if implementation behavior or file locations change from the current documented model.
  - Deliverable: documentation remains accurate for enable, progress, disable, and implementation locations.
  - Expected behavior: docs describe that natural rank continues to be calculated in parallel and disabling manual rank returns to natural rank without zeroing line income.
  - Files: `resources/docs/1.0/manual-rank.md`.
  - Dependency notes: depends on implementation choices in Tasks 1 through 5; skip content changes if the current document remains accurate.
  - Logging requirements: no runtime logging is required for documentation changes; if docs are skipped, note the reason in the implementation summary instead of adding a report task.

## Commit Plan
- **Commit 1** (after tasks 1-3): `fix: restore natural rank after manual override disable`
- **Commit 2** (after tasks 4-6): `fix: align manual rank progress calculations`

## Verification
- Run targeted manual checks or existing app flows for the admin user detail rank toggle and partner progress page.
- Run static checks that are practical for changed files, such as `./vendor/bin/phpstan analyse` and `./vendor/bin/pint --test`.
- Do not add or modify tests because plan settings specify `Testing: no`.

## Risks And Edge Cases
- Existing code uses duplicated progress formulas in Livewire and `ProgressBarAction`; updating only one path will keep inconsistent UI behavior.
- `saveLevelOverride()` currently mixes manual rank and percent override persistence; changes must not reset percent overrides unintentionally.
- Rank bonus payouts must not be triggered when recalculating after manual-rank disable; keep `--no-bonus` behavior or equivalent service call.
- Existing calculations use floats in legacy rank/progress code; avoid expanding float use into new accounting flows, but keep changes minimal unless a dedicated money refactor is planned.
=======
# Implementation Plan: Promo Codes for Package Purchase Thresholds

Branch: none
Created: 2026-05-14

## Settings
- Testing: yes
- Logging: verbose
- Docs: no

## Scope
- Add single-use promo codes that lower the minimum threshold required to buy a package.
- Manage promo codes from the admin package area only.
- Add a `Промокоды` tab to the admin `Пакеты` section with generation modal and table.
- Add a promo code field to the user package purchase modal in the personal account.
- Show validation errors for invalid, already-used, or mismatched promo codes.

## Research Context
- User package purchase entry point: `app/Livewire/Account/Itc/Packages.php` with UI in `resources/views/livewire/account/itc/packages.blade.php`.
- Current regular package minimum is hardcoded as `min:100` in the Livewire component and referenced in translation placeholders.
- Package admin area uses MoonShine via `app/MoonShine/Resources/ItcPackageResource.php` and pages under `app/MoonShine/Pages/ItcPackage/`.
- Existing admin modal/action patterns use `ActionButton`, `Modal`, `FormBuilder`, `TableBuilder`, and controller-backed admin routes in `routes/web.php` plus `app/Http/Controllers/AdminController.php`.
- No existing promo-code/coupon implementation was found.

## Commit Plan
- **Commit 1** (after tasks 1-3): `feat: add promo code domain model`
- **Commit 2** (after tasks 4-6): `feat: integrate promo codes into admin and purchase flows`
- **Commit 3** (after tasks 7-8): `test: cover package promo code flows`

## Tasks

### Phase 1: Domain And Persistence
- [x] Task 1: Create the promo code persistence model and schema.

  Deliverable and behavior:
  - Create a migration for a `promo_codes` table with fields for code, package type, reduced minimum amount, used state, created metadata, used metadata, and timestamps.
  - Suggested columns: `id`, `code` unique, `package_type`, `reduced_minimum_amount`, `used_at`, `used_by_user_id`, `created_by_admin_id`, `created_at`, `updated_at`.
  - Add indexes for `code`, `package_type`, `used_at`, and `used_by_user_id` because the admin table and validation path will filter by these fields.
  - Create `app/Models/PromoCode.php` with casts, fillable/guarded convention matching sibling models, relations to `App\Models\User` for used user and admin creator, and helper methods for active/used checks.
  - Create `database/factories/PromoCodeFactory.php` with states for unused, used, and package type variants.

  Files:
  - `database/migrations/*_create_promo_codes_table.php`
  - `app/Models/PromoCode.php`
  - `database/factories/PromoCodeFactory.php`

  Logging requirements:
  - No runtime logging is needed in the migration/model/factory task.
  - Ensure later service tasks log code generation and redemption state changes, not raw sensitive payloads.

  Dependency notes:
  - This task must be completed before admin generation, validation, and tests can persist promo codes.

### Phase 2: Promo Code Business Logic
- [x] Task 2: Add a focused promo code generation service/action.

  Deliverable and behavior:
  - Create an application service or action such as `app/Actions/PromoCodes/GeneratePromoCodeAction.php`.
  - Accept package type and reduced minimum amount from the admin form.
  - Generate a unique human-enterable code, retry on collisions, and persist the promo code with the authenticated admin user as creator when available.
  - Validate that `reduced_minimum_amount` is non-negative and below the default package minimum for the selected type.
  - Keep the code single-use by design; do not add multi-use behavior.

  Files:
  - `app/Actions/PromoCodes/GeneratePromoCodeAction.php`
  - optionally `app/Dto/PromoCodes/GeneratePromoCodeData.php`
  - optionally `app/Enums` only if existing package type enum cannot be used directly

  Logging requirements:
  - Log DEBUG on generation start with admin id, package type, and reduced threshold.
  - Log INFO when a code is generated with promo code id and package type; do not log unnecessary user/account data.
  - Log WARNING on invalid generation input or repeated collision retries.
  - Log ERROR with context if persistence fails.

  Dependency notes:
  - Depends on Task 1 model/schema.

- [x] Task 3: Add a focused promo code validation and redemption service for package purchases.

  Deliverable and behavior:
  - Create a service/action such as `app/Actions/PromoCodes/ApplyPackagePromoCodeAction.php` or `app/Services/PromoCodes/PackagePromoCodeService.php`.
  - Given user, package type, requested amount, and optional promo code, return the effective minimum threshold and validation result.
  - Reject unknown codes, already-used codes, codes for a different package type, and amounts lower than the promo-reduced threshold.
  - Mark the promo code as used only inside the successful package purchase transaction, with `used_at` and `used_by_user_id` populated.
  - Use row-level locking or a transactional update path to prevent double redemption of a single-use code.

  Files:
  - `app/Actions/PromoCodes/ApplyPackagePromoCodeAction.php` or `app/Services/PromoCodes/PackagePromoCodeService.php`
  - `app/Livewire/Account/Itc/Packages.php` integration point in later task
  - optionally a small value object/DTO under `app/Dto/PromoCodes/`

  Logging requirements:
  - Log DEBUG when validation starts with user id, package type, amount, and whether a code was supplied.
  - Log WARNING for invalid, already-used, mismatched, or below-threshold promo attempts with promo code id when available.
  - Log INFO when a promo code is redeemed with promo code id, user id, package type, original threshold, and effective threshold.
  - Log ERROR if transactional redemption fails or a race condition is detected.

  Dependency notes:
  - Depends on Task 1.
  - Must be integrated into the existing package purchase transaction so a failed package purchase never consumes a promo code.

### Phase 3: Admin UI
- [x] Task 4: Add the admin promo codes tab/table under the package area.

  Deliverable and behavior:
  - Add a `Промокоды` tab or page in the MoonShine package section following existing `ItcPackageResource` and `ItcPackageIndexPage` conventions.
  - Show a table with code, package type, reduced threshold, used status, creation date, usage date, and username of the user who used the promo code.
  - Eager load the used user/admin creator relation to avoid N+1 queries.
  - Add filters or search for code, package type, and used status if consistent with existing MoonShine resource patterns.

  Files:
  - `app/MoonShine/Resources/PromoCodeResource.php` or modifications to `app/MoonShine/Resources/ItcPackageResource.php`
  - `app/MoonShine/Pages/PromoCode/PromoCodeIndexPage.php`
  - optionally `app/MoonShine/Pages/PromoCode/PromoCodeDetailPage.php`
  - `app/Providers/MoonShineServiceProvider.php`

  Logging requirements:
  - No log is required for read-only table rendering.
  - If custom async loading is used, log DEBUG for admin id, filters, and result count only when consistent with existing admin logging practice.

  Dependency notes:
  - Depends on Task 1.
  - The admin section must remain admin-only through MoonShine authorization/resource visibility.

- [x] Task 5: Add the admin promo code generation modal and endpoint/action binding.

  Deliverable and behavior:
  - Add a `Сгенерировать промокод` button on the promo codes tab.
  - Open a modal with fields for package type and reduced minimum amount.
  - Submit to a MoonShine async method or existing admin controller route pattern.
  - On success, create a promo code through the generation action and refresh/return the created code in the admin UI.
  - On validation failure, display admin-visible errors for package type and threshold amount.

  Files:
  - `app/MoonShine/Resources/PromoCodeResource.php` or package resource/page containing the tab
  - `app/MoonShine/Pages/PromoCode/PromoCodeIndexPage.php`
  - `app/Http/Controllers/AdminController.php` if using controller-backed forms
  - `routes/web.php` if adding admin POST route
  - `lang/ru.json`, `lang/en.json`, `lang/zh.json` if new translatable labels are required

  Logging requirements:
  - Log DEBUG on modal submit with admin id, package type, and requested threshold.
  - Log INFO on successful generation with promo code id and package type.
  - Log WARNING on validation failures or unauthorized attempts.
  - Log ERROR on unexpected generation failures.

  Dependency notes:
  - Depends on Tasks 1 and 2.

### Phase 4: User Purchase Flow
- [x] Task 6: Integrate optional promo code input into the personal account package purchase flow.

  Deliverable and behavior:
  - Add a `promoCode` Livewire property and validation path to `app/Livewire/Account/Itc/Packages.php`.
  - Add a `Промокод` input field to the package purchase modal in `resources/views/livewire/account/itc/packages.blade.php`.
  - If no promo code is supplied, preserve the existing minimum threshold behavior.
  - If a promo code is supplied, validate it and apply the reduced threshold only for the matching package type.
  - Return Livewire validation errors for invalid, used, mismatched, or below-threshold codes without creating transactions/packages.
  - Mark the code used only after the package transaction and package record are successfully created.

  Files:
  - `app/Livewire/Account/Itc/Packages.php`
  - `resources/views/livewire/account/itc/packages.blade.php`
  - `lang/ru.json`
  - `lang/en.json`
  - `lang/zh.json`

  Logging requirements:
  - Log DEBUG when purchase begins with user id, amount, package type, and whether promo code was supplied.
  - Log WARNING for invalid promo validation outcomes and balance/threshold failures.
  - Log INFO on successful purchase with transaction uuid/package id and promo code id if used.
  - Log ERROR if package creation succeeds but promo redemption cannot be committed; use transaction rollback to keep state consistent.

  Dependency notes:
  - Depends on Task 3.
  - Keep financial values out of floats where new calculations are introduced; follow existing amount conventions or `Brick\Math` where needed.

### Phase 5: Tests And Verification
- [x] Task 7: Add Pest coverage for promo code generation and single-use redemption rules.

  Deliverable and behavior:
  - Add tests proving admin generation creates a valid unused promo code with package type and reduced threshold.
  - Add service-level or feature tests proving a valid code can be redeemed once.
  - Add tests proving second redemption by the same or another user fails because the code is globally single-use.
  - Add tests proving invalid, already-used, and mismatched package-type codes do not create transactions, packages, or usage state.

  Files:
  - `tests/Feature/PromoCodeTest.php` or a similarly named feature test
  - `database/factories/PromoCodeFactory.php`
  - related factory/model files from Task 1 as needed

  Logging requirements:
  - No production logging changes are required in tests.
  - Assert persisted state and validation outcomes rather than log contents unless existing activity-log conventions require it.

  Dependency notes:
  - Depends on Tasks 1-6.
  - Use existing `RefreshDatabase` setup and factories from the test suite.

- [x] Task 8: Run formatting and targeted verification for the promo code feature.

  Deliverable and behavior:
  - Run `vendor/bin/pint --dirty --format agent` after PHP changes.
  - Run targeted Pest tests for the new promo code coverage.
  - Run a focused broader test filter if package purchase behavior is touched, such as existing package or activity-log related tests.
  - Review generated migrations and schema indexes for PostgreSQL compatibility.

  Files:
  - no new application files expected; this task verifies changed files from prior tasks

  Logging requirements:
  - No runtime logging changes are required.
  - If tests reveal missing observability in the implementation, add logs to the service/action tasks before final verification.

  Dependency notes:
  - Depends on Tasks 1-7.
  - Follow low-token batch mode for long commands: redirect full output to log files, check exit codes, and only inspect summaries or final log tails on failure.
>>>>>>> Stashed changes
