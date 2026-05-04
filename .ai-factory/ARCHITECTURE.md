# Architecture

## Recommended Pattern
Use a pragmatic layered Laravel architecture with explicit application services and actions around the existing Eloquent models, MoonShine admin resources, Livewire components, and Blade views.

This project already contains technical debt and mixed patterns, so architecture work should improve seams incrementally rather than force a large rewrite.

## Layers
| Layer | Locations | Responsibilities |
|-------|-----------|------------------|
| Presentation | `routes/`, `app/Http/`, `app/Livewire/`, `resources/views/`, `app/View/Components/`, `app/MoonShine/` | Routing, request validation, authorization entry points, rendering, admin UI behavior |
| Application | `app/Actions/`, `app/Services/`, `app/Tasks/` | Business workflows, orchestration, transactions, external integrations, activity logging coordination |
| Domain Data | `app/Models/`, `app/Enums/`, `app/Dto/`, `app/Casts/`, `app/Settings/` | Eloquent state, typed domain values, settings, casts, relationships, query scopes |
| Persistence | `database/migrations/`, `database/factories/`, `database/seeders/`, `app/Repositories/` | Schema, test data, data access abstractions where already used |
| Infrastructure | `docker/`, `docker-compose.yml`, `config/`, `.mcp.json` | Runtime services, framework configuration, external tools |

## Dependency Rules
- Controllers, Livewire components, and MoonShine pages may call actions or services, not embed complex business workflows.
- Services and actions may use Eloquent models, repositories, DTOs, enums, settings, events, queues, and logging services.
- Models should keep relationships, casts, scopes, and simple invariants; avoid growing large procedural workflows inside models.
- Repositories should be used consistently where they already exist; do not introduce a repository for every model by default.
- Activity logging should be centralized through existing activity-log services and strategies rather than repeated inline logging blocks.
- Database write flows that affect balances, staking state, profit, reinvestment, withdrawals, ranks, or rewards should be transactional.
- Admin UI resources should delegate calculations and state changes to services/actions.

## Financial and Staking Rules
- Avoid floating-point arithmetic for financial values.
- Keep package, staking, accrual, reinvestment, and withdrawal transitions explicit and test-covered.
- Prefer idempotent operations for scheduled jobs, backfills, observers, and retryable work.
- Use row-level locking or other concurrency controls when changing balances or dependent financial state.
- Preserve legacy data assumptions unless a migration/backfill plan explicitly changes them.

## Activity Logging
- New business event logs should use `spatie/laravel-activitylog` and existing project services/strategies.
- Prefer structured event names and properties that allow admin audit views to filter and explain what happened.
- Do not log secrets, raw tokens, passwords, full credentials, or sensitive payloads.

## Testing Guidelines
- Use Pest for new tests unless a nearby test file uses PHPUnit style and consistency is more important.
- Feature tests should cover HTTP/admin-visible behavior, authorization, validation, and persistence outcomes.
- Unit tests should cover pure services, DTOs, calculation logic, and transition rules.
- Use factories and fakes for queues, notifications, events, HTTP clients, and storage.
- Add regression tests for bug fixes in financial, partner, staking, and activity-log behavior.

## Database Guidelines
- Use migrations for all schema changes.
- Index columns used in frequent filters, joins, admin tables, dashboards, and reports.
- Use eager loading and aggregate queries for admin dashboards and feeds.
- Review expensive queries with PostgreSQL `EXPLAIN ANALYZE` when performance is relevant.
- Treat backfills as production-impacting work: make them resumable or scoped when data volume may be large.

## Frontend Guidelines
- Preserve the existing Blade, Livewire, Vite, and Tailwind stack.
- Keep UI components aligned with existing account, dashboard, data, tab, widget, and index component patterns.
- Use Livewire for reactive server-backed interfaces already following project conventions.
- Keep JavaScript additions minimal and integrate with existing `resources/js/app.js`, `bootstrap.js`, and `echo.js` as appropriate.

## Example Workflow Shape
```php
final readonly class PurchaseStakingPackageAction
{
    public function __construct(
        private BusinessActivityLogger $activityLogger,
    ) {}

    public function execute(User $user, PurchaseStakingPackageData $data): ItcPackage
    {
        return DB::transaction(function () use ($user, $data): ItcPackage {
            // Validate state, create or update package records, then log the business event.
        });
    }
}
```

## Verification Commands
Use the commands that match the changed area:
- `php artisan test`
- `./vendor/bin/pest`
- `./vendor/bin/phpstan analyse`
- `./vendor/bin/pint --test`
- `npm run build`

## Migration Strategy
- Prefer small migrations with clear names.
- Keep destructive schema changes separate from data backfills.
- For legacy repair migrations, document the assumptions in the migration body with concise comments.
- Avoid changing historical migrations unless the project has not shared or run them anywhere; add a new migration instead.
