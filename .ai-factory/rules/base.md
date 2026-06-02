# Project Base Rules

> Auto-detected conventions from codebase analysis. Edit as needed.

## Naming Conventions

- Files: PascalCase for PHP classes, kebab-case for Blade views and JS files
- Variables: camelCase for PHP variables and JS; use descriptive names (`isRegisteredForDiscounts`, not `discount`)
- Methods: camelCase; verb-noun pattern preferred (`calculateProfit`, `createWithdrawal`)
- Classes: PascalCase; suffix signals role (`UserService`, `CreateStakingAction`, `DepositTask`, `UserRepository`, `ItcPackageDto`)
- Enums: PascalCase keys (`Active`, `Pending`, `FrozenByAdmin`)
- Database columns: snake_case

## Module Structure

- `app/Actions/` — focused single-responsibility business operations (grouped by domain subdirectory)
- `app/Services/` — multi-step orchestration and cross-concern logic (grouped by domain)
- `app/Tasks/` — workflow task units
- `app/Repositories/` — data access abstractions for complex or reused queries
- `app/Models/` — Eloquent state, scopes, relationships, casts; no workflow logic
- `app/Dto/` — typed data transfer objects
- `app/Enums/` — domain enumerations
- `app/Livewire/` — reactive Livewire components
- `app/MoonShine/` — admin resources, pages, components, handlers
- `app/ActivityLog/` — activity logging strategies

## Error Handling

- Validate and authorize at the HTTP / Livewire layer using Form Requests or inline validation
- Throw domain-specific exceptions; let Laravel's handler render them
- Financial and state-change flows must be wrapped in database transactions
- Prefer explicit guard clauses and early returns over nested conditionals

## Logging

- Use `spatie/laravel-activitylog` for business events via existing activity services in `app/Services/ActivityLog/`
- Use existing strategies under `app/ActivityLog/` for new log event types
- Avoid `Log::` for domain events; reserve it for debugging and infrastructure noise

## Testing

- All new behavior must have a Pest feature test in `tests/Feature/`
- Unit tests live in `tests/Unit/` for isolated logic
- Use model factories and factory states; do not manually hydrate models in tests
- Financial state-change tests must assert before and after values explicitly
- Run: `php artisan test --compact` (or `--filter=TestName`)

## Financial Values

- Never use PHP `float` for money, token amounts, or accounting values
- Use `brick/money` or existing project money/value patterns
- Round only at output boundaries; keep full precision internally

## Code Style

- PHP 8.4 with constructor property promotion
- Explicit return type declarations and typed parameters on all methods
- Curly braces required for all control structures
- Run `vendor/bin/pint --dirty --format agent` after editing PHP files
- PHPStan/Larastan level configured in `phpstan.neon` — changes must not degrade the analysis result
