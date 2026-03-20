# Resource Patterns

Use this file when creating or reviewing `app/MoonShine/Resources/*`.

## Purpose

A `ModelResource` should define how an admin works with a model in MoonShine:
- labels and admin-facing naming
- field definitions
- page registration
- validation rules
- lightweight listing query shaping
- simple filters and display formatting

It should not become the owner of reusable business behavior.

## Recommended Structure

A typical resource should stay close to this shape:

1. model and title
2. optional MoonShine flags
3. `fields()`
4. `pages()`
5. `rules()`
6. `filters()` if needed
7. `query()` if needed
8. small private helpers for repeated field/filter config

## Fields

Prefer fields that are:
- explicit about attribute names
- easy to scan
- split by page concerns using `hideOnForm`, `hideOnIndex`, and similar methods
- using small formatting callbacks only for presentation

Good examples:
- translated field display for index rows
- page-specific visibility
- hints and max lengths for admin input
- locale tabs for multilingual content

Avoid:
- long closures with business branching
- mutation or side effects inside formatting
- repeating the same field definitions across resources without extraction

## Pages

Prefer dedicated page classes under `app/MoonShine/Pages/<Domain>/`.

Use `pages()` only to declare:
- `IndexPage`
- `FormPage`
- `DetailPage`

Do not push business logic into page registration.

## Rules

Use `rules()` for admin input validation only.

Good fit:
- required fields
- enum validation
- image/file constraints
- locale-specific string length rules
- uniqueness rules

Bad fit:
- domain workflow checks that must also hold outside MoonShine
- state transition policy logic
- external integration preconditions

If a rule matters for non-admin flows too, consider moving that validation into a reusable application layer.

## Query

A resource `query()` may:
- order records
- join lightweight summary data for listing
- eager load relationships
- remove or adjust global scopes when the admin use case requires it

A resource `query()` should not:
- become the main reporting engine
- duplicate the same complex joins across multiple resources
- hide subtle `orWhere` behavior that changes result semantics unexpectedly

If the query becomes hard to read, extract to:
- model scopes
- query helper methods
- a dedicated service/query object if already appropriate for the codebase

## Private Helpers

Private methods are preferred when they reduce repetition in:
- locale tabs
- common field groups
- common filter fragments
- repeated display formatting

Keep helpers local to presentation concerns. If the helper performs domain work, it belongs elsewhere.

## Project Notes

Observed project patterns:
- multilingual admin content in `NewsResource`
- separate pages per resource domain
- some resources currently contain heavier mutation/query logic than ideal

When editing existing resources:
- preserve compatible structure
- prefer incremental cleanup over broad rewrites
- reduce architecture drift rather than expanding it
