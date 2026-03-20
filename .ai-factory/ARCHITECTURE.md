# Architecture: Modular Monolith

## Overview
ITCapital already behaves like a modular monolith: one Laravel application, one deployment unit, and several business areas with shared infrastructure. That matches the current team and code shape better than microservices or a strict layered rewrite.

The goal is not to force a new structure on the codebase. The goal is to keep growing the existing Laravel app with clearer boundaries between account, wallet, package, partner, academy, and admin concerns while preserving Laravel's normal ergonomics.

## Decision Rationale
- **Project type:** Multi-domain financial/account web platform with user-facing and admin-facing workflows
- **Tech stack:** PHP 8.4, Laravel 12, Blade, Livewire 3, PostgreSQL, Redis
- **Key factor:** The domain is broad and stateful, but the app is still a single deployable system with many shared models, services, and admin operations

## Folder Structure
```text
app/
  Actions/            Small write-side domain operations
  Http/Controllers/   HTTP entry points and thin request orchestration
  Livewire/           Reactive UI components for account/auth/public flows
  Models/             Eloquent models and relationships
  MoonShine/          Admin panel resources, pages, handlers, auth pipelines
  Repositories/       Data-access and external persistence adapters
  Services/           Domain/application services grouped by area
  Providers/          Container bindings and bootstrapping
database/
  migrations/         Schema evolution
  factories/          Test and seeding factories
resources/
  views/              Blade pages and shared templates
  js/                 Vite entrypoints and realtime bootstrap
routes/
  web.php             Public, account, academy, and admin routes
docker/               Runtime containers for app, nginx, db, redis, cron
tests/                Pest feature coverage for domain flows
```

## Dependency Rules
- ✅ Routes, controllers, and Livewire components may depend on actions, services, form-like validation objects, and Eloquent models
- ✅ Actions and services may depend on models, repositories, contracts, and framework abstractions
- ✅ Repositories may depend on external SDKs, query logic, and persistence concerns
- ✅ MoonShine resources may reuse application services instead of embedding business rules directly
- ❌ Controllers must not become the home for package, partner, wallet, or accrual business logic
- ❌ Livewire components must not duplicate domain rules that already belong in actions or services
- ❌ Repositories must not render views or own HTTP concerns
- ❌ Domain services should not depend on Blade views or MoonShine resource classes

## Layer / Module Communication
- Public web routes, account pages, academy pages, and admin resources should call into shared domain actions and services instead of inventing parallel logic paths
- Shared business rules should live in reusable services or actions, with transport-specific adapters at the edges: controllers, Livewire, console, or MoonShine
- Eloquent models remain the canonical persistence layer, but orchestration across multiple models should happen in actions/services instead of fat controllers
- External integrations such as Google Drive / Sheets should stay behind contracts and repository-style adapters bound in service providers

## Key Principles
1. Organize new code by business capability first, framework type second.
2. Keep entry points thin and move state transitions into explicit actions or services.
3. Reuse the same domain logic across Blade, Livewire, admin, and console flows whenever behavior overlaps.

## Code Examples

### Thin Controller Delegating to an Action
```php
<?php

namespace App\Http\Controllers\Packages;

use App\Actions\Staking\CreateStakingPackageAction;
use App\Http\Requests\Packages\StoreStakingPackageRequest;
use Illuminate\Http\RedirectResponse;

class ItcStakingController
{
    public function store(StoreStakingPackageRequest $request, CreateStakingPackageAction $action): RedirectResponse
    {
        $action->handle(
            user: $request->user(),
            payload: $request->validated(),
        );

        return to_route('itc-staking')->with('status', 'staking-created');
    }
}
```

### Service Depending on a Repository Contract
```php
<?php

namespace App\Services\Admin;

use App\Contracts\ExternalServices\GoogleSheetsUploaderContract;

class SummaryMetricsService
{
    public function __construct(
        protected GoogleSheetsUploaderContract $googleSheetsUploader,
    ) {
    }

    public function export(array $rows): void
    {
        $this->googleSheetsUploader->upload($rows);
    }
}
```

## Anti-Patterns
- ❌ Adding more business branching directly inside `routes/web.php`, controllers, or MoonShine resource callbacks
- ❌ Copying the same accrual, partner, or wallet logic into both Livewire and admin code paths
- ❌ Treating repositories as generic dumping grounds for business decisions
- ❌ Splitting the app into pseudo-microservices before domain boundaries are stable and independently deployable
