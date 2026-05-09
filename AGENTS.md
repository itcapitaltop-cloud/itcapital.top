# AGENTS.md

> Project map for AI agents. Keep this file up-to-date as the project evolves.

## Project Overview
IT Capital is a Laravel 12 application for investment, ITC staking, partner rewards, deposits, withdrawals, notifications, and admin workflows. See `.ai-factory/DESCRIPTION.md` for the project specification and stack details.

## Tech Stack
- **Language:** PHP 8.4, JavaScript ES modules
- **Framework:** Laravel 12, Livewire 3
- **Admin:** MoonShine 2
- **Database:** PostgreSQL
- **Cache/Realtime:** Redis, Laravel Reverb, Laravel Echo
- **Frontend Build:** Vite 5, Tailwind CSS 3
- **Testing:** Pest 4, PHPUnit
- **Quality:** Larastan/PHPStan, Laravel Pint, Rector Laravel

## Project Structure
```text
app/                 Laravel application code: services, actions, models, admin resources, Livewire, observers
app/ActivityLog/     Activity-log manager and event strategy classes
app/Actions/         Action classes for focused business operations
app/Dto/             Data transfer objects
app/Enums/           Domain enums
app/Http/            HTTP controllers, middleware, requests, and web layer code
app/Livewire/        Livewire components
app/Models/          Eloquent models
app/MoonShine/       MoonShine admin resources, pages, components, handlers, and permissions
app/Repositories/    Repository abstractions and implementations
app/Services/        Application and domain services
app/Settings/        Spatie settings classes
app/Tasks/           Task classes for business workflows
app/View/Components/ Blade component classes
bootstrap/           Laravel bootstrap files
config/              Laravel and package configuration
database/            Migrations, factories, seeders, and settings data
.devcontainer/       Dev Container manifest, Compose override, and bootstrap script
docker/              Docker build contexts for app, nginx, PostgreSQL, and Redis
lang/                Localization files
public/              Public web root and built/static assets
resources/           Blade views, CSS, JS, and image assets
routes/              Laravel route definitions
storage/             Runtime storage for logs, cache, framework files, and uploads
tests/               Pest/PHPUnit test suite
```

## Key Entry Points
| File | Purpose |
|------|---------|
| `artisan` | Laravel CLI entry point |
| `routes/web.php` | Main web routes |
| `routes/channels.php` | Broadcast channel authorization routes |
| `routes/console.php` | Console command routes |
| `app/Providers/AppServiceProvider.php` | Core service provider bootstrapping |
| `app/Providers/MoonShineServiceProvider.php` | MoonShine admin configuration |
| `app/Providers/RepositoryProvider.php` | Repository bindings |
| `resources/js/app.js` | Frontend JavaScript entry point |
| `resources/js/echo.js` | Echo/Reverb client setup |
| `vite.config.js` | Vite build configuration |
| `composer.json` | PHP dependencies and autoload configuration |
| `package.json` | JavaScript dependencies and frontend scripts |
| `docker-compose.yml` | Local app, nginx, PostgreSQL, and Redis services |
| `.devcontainer/devcontainer.json` | Dev Container IDE/runtime manifest |
| `.devcontainer/docker-compose.devcontainer.yml` | Dev Container Compose override |
| `.devcontainer/post-create.sh` | Dev Container bootstrap script |
| `phpstan.neon` | PHPStan/Larastan configuration |
| `phpunit.xml` | Test runner configuration |
| `pint.json` | Laravel Pint formatting configuration |
| `rector.php` | Rector refactoring configuration |
| `.mcp.json` | Project MCP server configuration |

## Documentation
| Document | Path | Description |
|----------|------|-------------|
| README | `README.md` | Project notes, refactoring warning, Pest and PHPStan rules |
| Project Description | `.ai-factory/DESCRIPTION.md` | AI Factory project specification and tech stack |
| Architecture | `.ai-factory/ARCHITECTURE.md` | Architecture decisions and dependency guidelines |
| Project Map | `AGENTS.md` | This file: structural map for AI agents |

## AI Context Files
| File | Purpose |
|------|---------|
| `AGENTS.md` | Project structure map |
| `.ai-factory/DESCRIPTION.md` | Project specification and detected stack |
| `.ai-factory/ARCHITECTURE.md` | Architecture decisions and coding guidelines |
| `.mcp.json` | MCP server configuration |
| `.kilocode/skills/itcapital-context/SKILL.md` | Project-specific AI skill for IT Capital conventions |

## Agent Rules
- Treat this as an existing production-like Laravel application with known technical debt; prefer small, safe, tested changes.
- Write Pest tests for new behavior and bug fixes when feasible.
- Keep PHPStan/Larastan compatibility in mind when changing PHP code.
- Use `spatie/laravel-activitylog` for new business event logs where logging is required.
- Do not use floats for money or token/accounting values; follow existing money/value conventions.
- Avoid broad rewrites unless a plan explicitly calls for them.
- Do not read or expose `.env`, credentials, tokens, private keys, or production data.
- Never combine shell commands with `&&`, `||`, or `;` when following AI Factory command guidance; execute each command as a separate tool call.

## MCP Servers
| Server | Purpose | Required Environment |
|--------|---------|----------------------|
| `github` | GitHub repository and PR/issue integration | `GITHUB_TOKEN` |
| `postgres` | PostgreSQL database inspection and queries | `DATABASE_URL` |
| `filesystem` | Project-scoped filesystem operations | none |
