# Project: ITCapital

## Overview
ITCapital is a Laravel 12 web application for account management, wallet operations, package and staking flows, partner programs, academy content, and MoonShine-based admin operations.

The codebase is a single-repo Laravel application with server-rendered Blade views, Livewire components for reactive UI, PostgreSQL as the primary database, Redis for caching and queues, and Docker Compose for local infrastructure.

## Core Features
- User authentication, email verification, password reset, and account dashboards
- Wallet deposit and withdrawal flows with QR-based wallet utilities
- ITC package, staking, profit, reinvest, and accrual workflows
- Partner hierarchy, rank, reward, and closure management
- Academy landing and multilingual news publishing
- MoonShine admin panel for operational management and reporting

## Tech Stack
- **Language:** PHP 8.4
- **Framework:** Laravel 12
- **Frontend:** Blade, Livewire 3, Vite, Tailwind CSS 3
- **Database:** PostgreSQL
- **Cache / Realtime:** Redis, Laravel Reverb
- **ORM:** Eloquent ORM
- **Admin:** MoonShine 2
- **Testing:** Pest 4
- **Infrastructure:** Docker Compose, Nginx

## Integrations
- Google API client for Drive / Sheets related uploads and exports
- Pusher-compatible frontend realtime stack via `laravel-echo` and `pusher-js`
- Spatie packages for activity logging and application settings

## Architecture Notes
- The application is best treated as a modular monolith: one deployable Laravel app with domain-oriented boundaries inside `app/`.
- HTTP controllers, Livewire components, actions, services, repositories, models, and admin resources cooperate inside domain areas such as account, wallet, packages, partners, academy, and admin.
- Infrastructure concerns stay at the edges: Docker, Nginx, PostgreSQL, Redis, external Google integrations, and MoonShine admin tooling.

## Non-Functional Requirements
- Logging: New operational event logging should use `spatie/laravel-activitylog`, matching the current project guidance in `README.md`
- Quality gates: New work should include Pest coverage where practical and remain compatible with PHPStan / Larastan expectations
- Security: Keep auth, verification, signed URLs, throttling, and admin access aligned with Laravel conventions already present in routes and providers
- Performance: Prevent N+1 database access, prefer eager loading for account and admin flows, and keep heavy operations suitable for queued or offline handling when needed
- Operations: Local development should remain Docker Compose friendly and environment-driven

## Architecture
See `.ai-factory/ARCHITECTURE.md` for detailed architecture guidelines.
Pattern: Modular Monolith
