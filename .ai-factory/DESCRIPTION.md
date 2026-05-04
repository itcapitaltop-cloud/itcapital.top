# Project: IT Capital

## Overview
IT Capital is an existing Laravel application for an investment, staking, partner rewards, deposits, withdrawals, notifications, and admin-management domain. The codebase combines a server-rendered Laravel application, Livewire components, MoonShine admin resources, PostgreSQL persistence, Redis-backed infrastructure, and Vite/Tailwind frontend assets.

## Core Features
- User accounts, authentication-related flows, verification, locale handling, and account settings.
- Deposits, withdrawals, transactions, payment sources, package balances, and financial summaries.
- ITC packages, staking purchases, accruals, reinvestment, profit withdrawal, top-up, and zeroing flows.
- Partner levels, ranks, rewards, closures, line limits, and referral-related business logic.
- Admin operations through MoonShine resources, pages, components, handlers, and permissions.
- Activity logging for business events using `spatie/laravel-activitylog` and project-specific strategies.
- Notifications, Reverb/Echo real-time integration, and user-facing Blade/Livewire views.

## Tech Stack
- **Language:** PHP 8.4, JavaScript ES modules
- **Backend Framework:** Laravel 12
- **Frontend:** Blade, Livewire 3, Vite 5, Tailwind CSS 3, Laravel Echo, Pusher JS
- **Admin UI:** MoonShine 2 with MoonShine permissions and CKEditor integration
- **Database:** PostgreSQL via Docker Compose service `pgdb`
- **Cache/Queue/Broadcast Infrastructure:** Redis, Laravel Reverb
- **Testing:** Pest 4, PHPUnit configuration, Faker, Mockery
- **Static Analysis and Refactoring:** Larastan/PHPStan, Laravel Pint, Rector Laravel
- **Containers:** Docker Compose with PHP-FPM app, Nginx, PostgreSQL, and Redis services
- **Important Packages:** `brick/money`, `spatie/laravel-activitylog`, `spatie/laravel-settings`, `spatie/sheets`, `league/csv`, `phpoffice/phpspreadsheet`, `google/apiclient`, `anhskohbo/no-captcha`

## Identified Patterns
- Domain logic is split across `app/Services`, `app/Actions`, `app/Tasks`, `app/Repositories`, DTOs, enums, observers, and model-level behavior.
- Admin behavior is implemented under `app/MoonShine` using resources, pages, components, handlers, traits, and auth pipelines.
- Activity logging uses `app/ActivityLog` strategies plus dedicated services under `app/Services/ActivityLog`.
- Data schema changes are migration-driven with a substantial existing migration history.
- Views and UI components live in `resources/views`, `app/View/Components`, `resources/js`, `resources/css`, and `resources/assets`.
- README project rules emphasize writing Pest tests and following PHPStan.

## Architecture Notes
- Prefer small application-service or action classes for business workflows instead of adding complex controller, MoonShine page, or model logic.
- Keep financial and staking state transitions explicit, transactional, idempotent where possible, and covered by Pest tests.
- Use `brick/money` or existing money/value conventions for monetary calculations; avoid floats for financial values.
- Use `spatie/laravel-activitylog` for new business event logs, following the existing ITC staking activity-log direction from README.
- Use Eloquent relationships with eager loading for admin tables, dashboards, summaries, and activity feeds to avoid N+1 queries.
- Treat database migrations and backfills carefully because the schema has legacy migration and repair history.

## Non-Functional Requirements
- **Testing:** New behavior should include Pest coverage, especially financial state changes, permissions, and backfills.
- **Static Analysis:** Changes should satisfy Larastan/PHPStan expectations and keep types explicit where practical.
- **Formatting:** Use Laravel Pint and existing project formatting conventions.
- **Logging:** Business events should use the activity-log package and existing activity services/strategies when applicable.
- **Security:** Validate all user/admin inputs, enforce authorization in web/admin flows, protect sensitive financial operations, and avoid leaking credentials or tokens.
- **Performance:** Use eager loading, pagination, indexed query paths, and background work for long-running operations.
- **Configuration:** Environment-specific values belong in `.env` and config files, not hardcoded application code.

## External Integrations
- Google API client for spreadsheet or external Google workflows.
- NoCaptcha for bot protection.
- Laravel Echo/Pusher-compatible broadcasting with Reverb.
- MCP integrations configured at project level in `.mcp.json` for GitHub, PostgreSQL, and filesystem access.
