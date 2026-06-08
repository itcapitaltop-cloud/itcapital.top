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
