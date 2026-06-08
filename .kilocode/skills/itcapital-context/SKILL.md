---
name: itcapital-context
description: >-
  Applies IT Capital project conventions for Laravel 12, Livewire 3, MoonShine 2, PostgreSQL, staking,
  partner rewards, financial workflows, activity logging, Pest tests, and PHPStan-safe changes. Use when
  modifying or reviewing this codebase.
license: MIT
metadata:
  category: project-context
  project: itcapital.top
  version: "1.0"
---

# IT Capital Context

Use this skill when working in the IT Capital Laravel application.

## Project Shape

- Treat the project as a production-like Laravel 12 application with existing technical debt.
- Preserve the current stack: PHP 8.4, Laravel 12, Livewire 3, MoonShine 2, PostgreSQL, Redis, Reverb, Vite 5, and Tailwind CSS 3.
- Prefer small, safe changes over broad rewrites.
- Keep existing project structure and naming conventions unless a plan explicitly changes them.

## Business Rules

- Do not use floats for money, tokens, balances, package amounts, staking accruals, partner rewards, deposits, or withdrawals.
- Keep financial and staking state transitions explicit, transactional, and idempotent where practical.
- Use row-level locking or equivalent concurrency controls when changing dependent balance or financial state.
- Preserve legacy data assumptions unless a migration and backfill plan explicitly changes them.

## Laravel Conventions

- Put orchestration and business workflows in `app/Actions`, `app/Services`, or `app/Tasks`.
- Keep controllers, Livewire components, MoonShine resources, and pages focused on presentation, validation, authorization, and delegation.
- Keep Eloquent models focused on relationships, casts, scopes, accessors, mutators, and simple invariants.
- Use repositories where the project already uses them; do not introduce repository layers by default.
- Use eager loading, aggregates, pagination, and indexed query paths for admin tables, dashboards, summaries, and feeds.

## Activity Logging

- Use `spatie/laravel-activitylog` for new business event logs when logging is required.
- Prefer existing activity-log services and strategy classes under `app/ActivityLog` and `app/Services/ActivityLog`.
- Do not log secrets, passwords, tokens, raw credentials, or sensitive payloads.

## Admin And UI

- Preserve MoonShine 2 patterns under `app/MoonShine`; do not apply MoonShine 3-only APIs unless the project upgrades first.
- Preserve existing Blade, Livewire, Vite, and Tailwind conventions for user-facing UI.
- Keep JavaScript additions minimal and integrate through existing entry points such as `resources/js/app.js`, `resources/js/bootstrap.js`, and `resources/js/echo.js`.

## Testing And Quality

- Write Pest tests for new behavior and bug fixes when feasible.
- Prioritize regression tests for financial, staking, partner, withdrawal, permission, and activity-log behavior.
- Keep PHPStan/Larastan compatibility in mind; prefer explicit types where practical.
- Use Laravel Pint formatting conventions.
- Useful verification commands include `php artisan test`, `./vendor/bin/pest`, `./vendor/bin/phpstan analyse`, `./vendor/bin/pint --test`, and `npm run build`.

## Safety

- Keep environment files, credentials, private keys, tokens, and production data out of agent-visible output and routine inspection.
- Do not change historical migrations unless the project has not shared or run them; add new migrations instead.
- Do not implement features during setup-only workflows; restrict setup to AI context, skills, MCP, and documentation artifacts.
