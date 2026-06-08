#!/usr/bin/env bash

set -euo pipefail

log() {
    printf '[devcontainer] %s\n' "$1"
}

warn() {
    printf '[devcontainer] WARN: %s\n' "$1" >&2
}

run_step() {
    log "$1"
    shift
    "$@"
}

ensure_env_file() {
    if [[ -f .env ]]; then
        log '.env already exists; leaving local configuration unchanged'
        return
    fi

    if [[ ! -f .env.example ]]; then
        warn '.env.example not found; create .env manually before running Laravel commands'
        return
    fi

    log 'creating .env from .env.example without printing environment contents'
    cp .env.example .env
}

ensure_app_key() {
    if [[ ! -f .env ]]; then
        warn 'skipping APP_KEY generation because .env is missing'
        return
    fi

    if grep -q '^APP_KEY=base64:' .env; then
        log 'APP_KEY already exists; skipping key generation'
        return
    fi

    run_step 'generating Laravel APP_KEY' php artisan key:generate --ansi
}

ensure_storage_link() {
    if [[ -L public/storage ]]; then
        log 'public/storage symlink already exists; skipping storage:link'
        return
    fi

    run_step 'creating Laravel storage symlink' php artisan storage:link --ansi
}

check_database() {
    local db_host
    local db_port
    local db_name

    db_host="$(grep -E '^DB_HOST=' .env 2>/dev/null | cut -d '=' -f 2- | tr -d '"' || true)"
    db_port="$(grep -E '^DB_PORT=' .env 2>/dev/null | cut -d '=' -f 2- | tr -d '"' || true)"
    db_name="$(grep -E '^DB_DATABASE=' .env 2>/dev/null | cut -d '=' -f 2- | tr -d '"' || true)"

    db_host="${db_host:-pgsql}"
    db_port="${db_port:-5432}"
    db_name="${db_name:-itc}"

    log "checking PostgreSQL readiness at ${db_host}:${db_port} for database ${db_name}"

    if pg_isready --host "$db_host" --port "$db_port" --dbname "$db_name" >/dev/null 2>&1; then
        log 'PostgreSQL is reachable'
    else
        warn "PostgreSQL is not reachable at ${db_host}:${db_port}; start services and run php artisan migrate manually"
    fi

    log 'test database expected by phpunit.xml: testing'
    log 'create it manually if tests fail with a missing database error'
}

main() {
    log 'starting post-create bootstrap'

    ensure_env_file

    run_step 'installing Composer dependencies' composer install --no-interaction --prefer-dist
    run_step 'installing npm dependencies from package-lock.json' npm ci

    ensure_app_key
    ensure_storage_link
    check_database

    log 'bootstrap complete'
    log 'next: run php artisan migrate when the PostgreSQL service is ready'
    log 'next: run npm run dev -- --host 0.0.0.0 for the Vite dev server'
}

main "$@"
