# Implementation Plan: Admin-Managed Package Definitions

Branch: feature/admin-package-definitions
Created: 2026-05-25

## Settings
- Testing: yes
- Logging: verbose
- Docs: yes

## Context
Goal: Add an admin-managed "Пакеты" section where package types can be created, edited, deleted, and configured with default profitability percent, minimum start amount, working duration, package name, and card image.

User note: Basic migrations and model were already created for the new package workflow.

Repository note: The current branch was created from `main`, where `PackageDefinition` files are not present. Local `dev` contains the user-provided base files:
- `app/Models/Package/PackageDefinition.php`
- `database/migrations/2026_05_25_194402_create_package_definitions_table.php`
- `database/migrations/2026_05_25_195653_add_column_package_definition_id_to_itc_package_table.php`
- `database/migrations/2026_05_25_195927_fill_package_definition.php`

Important correction: the relation migration in `dev` targets `itc_package`, but the existing table is `itc_packages`. The implementation must use `itc_packages` and avoid changing historical migrations unless those new files have not been run/shared.

## Commit Plan
- **Commit 1** (after tasks 1-3): "feat: add configurable package definition admin"
- **Commit 2** (after tasks 4-6): "feat: apply package definitions to package creation"
- **Commit 3** (after tasks 7-8): "test: cover configurable package definitions"

## Tasks

### Phase 1: Data Model And Admin CRUD
- [x] Task 1: Reconcile the package definition schema and model foundation.

  Deliverable: Ensure `PackageDefinition` exists as the canonical configurable package type model with safe casts, fillable attributes, soft deletes, and relationships needed by `ItcPackage`.

  Expected behavior: `package_definitions` stores `type`, `name`, `default_profit_percent`, `min_start_amount`, `duration_months`, `card_image_path`, `is_active`, and `sort_order`; `itc_packages.package_definition_id` references package definitions without breaking existing rows; existing package economics remain snapshotted on `itc_packages`.

  Files:
  - `app/Models/Package/PackageDefinition.php`
  - `app/Models/ItcPackage.php`
  - `database/migrations/2026_05_25_194402_create_package_definitions_table.php`
  - `database/migrations/2026_05_25_195653_add_column_package_definition_id_to_itc_package_table.php`
  - `database/migrations/2026_05_25_195927_fill_package_definition.php`

  Dependency notes: Start from the user-provided files on `dev` when available. Fix the table name to `itc_packages`; do not introduce floats for monetary values or token amounts. If those migrations may already have run anywhere, add a corrective migration instead of editing them.

  Logging requirements: No runtime business logs are required for passive model/migration definitions. Add concise migration comments for non-obvious legacy assumptions and log migration/backfill progress with `info` only if a data backfill is introduced.

- [x] Task 2: Add a MoonShine CRUD resource for package definitions.

  Deliverable: Create the admin section `Пакеты` for managing package definition records, including create, edit, detail, delete/soft-delete where supported, active flag, sorting, numeric fields, enum type selection, and card image upload.

  Expected behavior: Admins can manage package type records from the sidebar; validation prevents duplicate package `type`, invalid percents, invalid minimum amounts, invalid durations, and invalid images. Image upload uses the existing `public` disk pattern and stores only a relative path.

  Files:
  - `app/MoonShine/Resources/PackageDefinitionResource.php`
  - `app/MoonShine/Pages/PackageDefinition/PackageDefinitionIndexPage.php`
  - `app/MoonShine/Pages/PackageDefinition/PackageDefinitionFormPage.php`
  - `app/MoonShine/Pages/PackageDefinition/PackageDefinitionDetailPage.php`
  - `app/Providers/MoonShineServiceProvider.php`

  Dependency notes: Follow the `NewsResource` and `News*Page` patterns for CRUD pages and `Image::make(...)->disk('public')->dir('package-definitions')`. Register the menu with `->canSee($this->canSeeResource(PackageDefinitionResource::class))` so permissions match existing admin behavior.

  Logging requirements: Use verbose logging only around custom save/delete hooks if custom hooks are needed. Log admin-side validation/save failures at `warning` or `error` with resource id/type and without file contents or sensitive payloads. Do not add noisy logs for standard MoonShine field rendering.

- [x] Task 3: Add a package definition resolver service for reusable defaults.

  Deliverable: Create a small service that resolves an active `PackageDefinition` by `PackageTypeEnum` and exposes typed defaults for creation flows.

  Expected behavior: User and admin package creation can ask one service for default profitability percent, minimum start amount, duration, package definition id, and active status. Missing or inactive definitions fail with a clear domain exception or validation message before creating financial records.

  Files:
  - `app/Services/Package/PackageDefinitionResolver.php`
  - `app/Models/Package/PackageDefinition.php`
  - `app/Enums/Itc/PackageTypeEnum.php`

  Dependency notes: Keep this service small; do not introduce a repository layer unless nearby code already requires it. Return decimals as strings or safe decimal-compatible values, not floats.

  Logging requirements: With verbose logging, log resolver entry at `debug` with requested package type, log selected definition id/type at `debug`, log inactive/missing definition at `warning`, and log unexpected database errors at `error` with exception context.

### Phase 2: Apply Definitions To Package Creation
- [x] Task 4: Update user-facing ITC package creation to use package definitions.

  Deliverable: Replace hard-coded default percent, minimum amount, and duration values in the regular package purchase flow with values resolved from active package definitions.

  Expected behavior: New user-created packages snapshot the selected package definition id, `month_profit_percent`, `duration_months`, and `work_to` from the active definition while preserving existing package rows. Validation uses `min_start_amount` from the definition instead of hard-coded minimums where applicable.

  Files:
  - `app/Livewire/Account/Itc/Packages.php`
  - `resources/views/livewire/account/itc/packages.blade.php`
  - `resources/views/components/account/itc/package.blade.php`
  - `resources/views/components/account/itc/package-modal.blade.php`

  Dependency notes: Depends on tasks 1 and 3. Keep financial calculations decimal-safe. Preserve present-package and promo-code behavior unless explicitly covered by a package definition field.

  Logging requirements: With verbose logging, log purchase-flow entry at `debug` with user id and package type, log resolved definition id/defaults at `debug`, log validation failures at `warning`, log package creation success at `info` with package uuid/definition id, and log exceptions at `error` without exposing balances beyond existing safe conventions.

- [x] Task 5: Update admin-created package flows to use package definitions.

  Deliverable: Adjust the MoonShine user-detail package creation workflow so admin defaults and created packages come from active package definitions.

  Expected behavior: Admin package creation pre-fills or derives profitability percent, duration, and definition id from the selected package type. Admin overrides should remain only where the current flow intentionally allows manual percent/duration edits; persisted package records still snapshot the final values.

  Files:
  - `app/MoonShine/Pages/User/UserDetailPage.php`
  - `app/MoonShine/Resources/UserResource.php`
  - `app/Repositories/ItcPackageRepository.php`
  - `app/Contracts/Packages/ItcPackageRepositoryContract.php`

  Dependency notes: Depends on tasks 1 and 3. Preserve staking special handling through `CreateStakingPackageAction`. Avoid embedding business rules in MoonShine pages; delegate defaults to the resolver/service.

  Logging requirements: With verbose logging, log admin creation request at `debug` with admin id, target user id, package type, and definition id; log override usage at `info`; log failed definition resolution or validation at `warning`; log package creation completion at `info`; log unexpected exceptions at `error`.

- [x] Task 6: Integrate package definitions with staking and promo-code minimum logic where applicable.

  Deliverable: Review and update staking package creation and promo-code minimum amount checks so they consistently use package definitions when the relevant type is configured there.

  Expected behavior: Staking continues to respect existing staking-specific business rules, but default percent/duration/definition id can come from the `staking` definition if enabled. Promo-code minimum checks use the configured standard package minimum instead of a hard-coded `100.00000000` where that value represents package minimum requirements.

  Files:
  - `app/Actions/Staking/CreateStakingPackageAction.php`
  - `app/Services/Package/Staking/StakingPurchaseService.php`
  - `app/Services/PromoCodes/PackagePromoCodeService.php`
  - `app/Actions/PromoCodes/GeneratePromoCodeAction.php`
  - `app/MoonShine/Resources/PromoCodeResource.php`
  - `app/MoonShine/Pages/PromoCode/PromoCodeIndexPage.php`

  Dependency notes: Depends on tasks 1 and 3. Do not change existing staking economics for already-created staking packages. If a definition is inactive/missing, fail safely before creating new packages or generating misleading promo-code thresholds.

  Logging requirements: With verbose logging, log definition resolution at `debug`, promo-code threshold selection at `debug`, generated promo-code minimum values at `info`, staking package creation completion at `info`, and inactive/missing definition or inconsistent thresholds at `warning`.

### Phase 3: UI Display, Tests, And Documentation
- [x] Task 7: Update package card/display data to use configurable definitions where required.

  Deliverable: Wire configured package names, card images, default percent, minimum amount, and duration into the package card display paths that currently show static package information.

  Expected behavior: Account/package cards render admin-configured labels and images for active definitions while maintaining a safe fallback for existing packages that do not have a definition id. Public display must not expose inactive/deleted definitions for new purchases.

  Files:
  - `resources/views/pages/account/itc/itc-packages.blade.php`
  - `resources/views/livewire/account/itc/packages.blade.php`
  - `resources/views/components/account/itc/package.blade.php`
  - `resources/views/components/account/itc/package-staking.blade.php`
  - `resources/views/components/account/itc/package-modal.blade.php`
  - `app/View/Components/Account/Itc/Package.php`
  - `app/View/Components/Account/Itc/PackageModal.php`

  Dependency notes: Depends on tasks 1, 3, 4, and 6. Preserve existing visual language and avoid broad frontend rewrites.

  Logging requirements: UI rendering should not emit routine logs. If fallback behavior is implemented in PHP components/services, log missing definition fallbacks at `debug` in verbose mode and repeated inconsistent data at `warning`.

- [x] Task 8: Add Pest coverage and documentation checkpoint for package definitions.

  Deliverable: Add focused Pest tests for model/schema behavior, MoonShine resource validation, package creation defaults, promo-code minimum behavior, and image upload handling; update documentation through the mandatory docs checkpoint.

  Expected behavior: Tests prove that admins can define package conditions, new packages snapshot the configured values, invalid definitions are rejected, inactive definitions are not used for new package creation, and existing packages remain unaffected. Documentation records the new admin package configuration workflow and implementation caveats.

  Files:
  - `tests/Feature/PackageDefinitionTest.php`
  - `tests/Feature/PromoCodeTest.php`
  - `tests/Feature/StartBonusAccuralTest.php`
  - `tests/Feature/StakingPerformanceTest.php`
  - `database/factories/PackageDefinitionFactory.php`
  - Documentation files selected by `/aif-docs`

  Dependency notes: Depends on tasks 1-7. Use existing Pest style from `PromoCodeTest`, `StartBonusAccuralTest`, and `StakingPerformanceTest`. Use `Storage::fake('public')` and `UploadedFile::fake()->image(...)` for image upload tests if admin HTTP/resource testing is practical.

  Logging requirements: Tests should assert important side effects and avoid relying on log text unless testing explicit logging behavior. If logging is tested, fake the logger or assert only safe structured context such as package type, definition id, and package uuid.

## Verification
- Run targeted Pest tests for package definitions, promo codes, start bonus/package creation, and staking flows.
- Run `./vendor/bin/pint --test` for formatting.
- Run `./vendor/bin/phpstan analyse` if touched types/services create static-analysis risk.
- Run `npm run build` only if frontend assets or JavaScript are changed.

## Implementation Notes
- Do not use floats for package amounts, balances, token values, or persisted financial values.
- Existing package rows must keep their original profitability and duration values; definitions configure future package creation and display metadata.
- Use `spatie/laravel-activitylog` only for business events where the project already logs comparable package/admin actions; do not log uploaded image contents or sensitive financial payloads.
- Prefer small services/actions over adding business logic to Livewire components or MoonShine pages.
- The docs checkpoint is mandatory because `Docs: yes` was selected.
