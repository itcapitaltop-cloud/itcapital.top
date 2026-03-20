---
name: moonshine2-admin-development
description: Use for any task involving MoonShine 2 admin code in this Laravel project. Activate when creating or editing MoonShine Resources, Pages, Forms, Fields, Filters, Handlers, Components, auth pipelines, admin actions, exports, or admin-specific validation and authorization. Covers keeping MoonShine as a thin admin transport layer, reusing Actions and Services for domain logic, and following existing project structure under app/MoonShine.
argument-hint: "[resource|page|fields|filters|handler|review]"
allowed-tools: Read Glob Grep Bash(git *)
disable-model-invocation: false
---

# MoonShine 2 Admin Development

Use this skill whenever the task touches `app/MoonShine/*`.

This project uses MoonShine 2 as an admin interface inside a Laravel modular monolith. MoonShine code is part of the delivery layer, not the domain layer.

## Read First

Read only what is needed:

1. `AGENTS.md`
2. `.ai-factory/ARCHITECTURE.md`
3. The target MoonShine resource/page/handler being edited
4. Neighboring files in the same area for conventions
5. Related `Actions`, `Services`, `Models`, and `Policies` only if the admin change touches business behavior

If the task also touches tests, activate `pest-testing`.
If the task touches Livewire, activate `livewire-development`.
If the task touches Tailwind or UI styling, activate `tailwindcss-development`.

For detailed guidance, load only the reference file you need:
- `references/resource-patterns.md`
- `references/page-patterns.md`
- `references/filter-and-query-risks.md`
- `references/project-examples.md`

## Core Rule

MoonShine must stay thin.

MoonShine code may:
- define admin fields, pages, filters, labels, layout, and transport-level wiring
- call existing `Actions`, `Services`, repositories, policies, and models
- map admin form input into validated application calls
- format values for display
- define admin-only export or handler orchestration

MoonShine code must not:
- become the primary home for business rules
- duplicate logic already used by controllers, Livewire, jobs, or services
- embed complex state transitions directly in resource methods when an action/service should own them
- contain large query-building blocks copied across resources
- hide domain side effects inside field callbacks or page rendering methods

## Project-Specific Boundaries

Follow the modular monolith architecture:

- `app/MoonShine/Resources/*`:
  admin resource definition, fields, pages, filters, lightweight query shaping
- `app/MoonShine/Pages/*`:
  page composition and admin presentation
- `app/MoonShine/Handlers/*`:
  admin-triggered orchestration such as export flows
- `app/Actions/*`:
  write-side domain operations
- `app/Services/*`:
  reusable business/application logic
- `app/Models/*`:
  persistence and relationships

If a MoonShine method mutates more than one model, performs a financial/account transition, talks to an external integration, or contains reusable business branching, move that behavior into an `Action` or `Service`.

## Existing Repo Patterns To Preserve

Based on the current codebase:

- Resources live in `app/MoonShine/Resources`
- CRUD pages are split into dedicated classes under `app/MoonShine/Pages/<Domain>/`
- Admin handlers exist under `app/MoonShine/Handlers`
- Some resources currently contain heavy query logic and action methods; prefer reducing that over expanding it
- Multi-language admin content exists, for example `NewsResource`
- Admin export and integration flows exist, for example Google Sheets export handlers

When editing existing MoonShine files, preserve the established structure unless there is a clear improvement with low migration risk.

## Workflow

### 1. Identify the change type

Classify the task first:

- `resource`: fields, pages, labels, query, validation
- `page`: index/detail/form composition
- `fields`: field config, formatting, tabs, readonly states, relation displays
- `filters`: filter UI and query application
- `handler`: exports, bulk actions, external integration triggers
- `review`: inspect MoonShine code for architecture drift and admin risks

### 2. Inspect local conventions

Before editing:
- read the target file
- read a similar resource/page/handler in the same project
- check whether the behavior already exists in an `Action` or `Service`
- check whether the model already exposes a relationship or accessor that should be reused

Do not introduce a new admin pattern if an existing one already exists nearby.

### 3. Decide where the logic belongs

Keep the logic in MoonShine only if it is mostly:
- field declaration
- page layout
- display formatting
- simple admin filtering
- transport wiring for an existing application service

Move it out of MoonShine if it involves:
- domain validation beyond field-level constraints
- state transitions such as ban/unban, accruals, package creation, financial posting
- external API calls
- reusable export/business workflows
- audit/log side effects
- multi-step persistence

### 4. Implement with explicit boundaries

Preferred direction:

- Resource/Page/Handler
  calls
- Action/Service
  uses
- Model/Repository/Contract

Avoid the reverse dependency.

## Resource Guidelines

In `ModelResource` classes:

- keep `fields()` focused on admin representation
- keep `rules()` explicit and local to admin input validation
- keep `pages()` declarative
- keep `query()` readable and limited to resource listing concerns
- prefer model scopes, query objects, or service/query extraction if `query()` becomes large
- avoid embedding large closures repeatedly across fields or filters when they can be extracted to private methods

Good fit for a resource:
- labels
- field visibility by page
- display formatting
- simple ordering
- simple eager loading
- resource-level validation rules

Bad fit for a resource:
- financial/account state changes
- session invalidation
- export upload workflows
- broad cross-domain orchestration

## Page Guidelines

In `FormPage`, `IndexPage`, and `DetailPage`:

- keep pages compositional
- use them to arrange admin UI layers, not to hold domain behavior
- if a page method is empty pass-through boilerplate, avoid expanding it unless needed
- if repeated layout fragments appear across pages, consider a shared helper or component

## Field Guidelines

- Prefer explicit field names and model attributes
- Keep formatting callbacks small and presentation-oriented
- For multilingual content, use consistent tab/locale structure across resources
- Keep file/image constraints explicit in field config and mirrored in validation
- Avoid hidden mutation in `formatted`, `resolve`, or callback-heavy field definitions

## Filter Guidelines

- Filters should be understandable in one pass
- If filter closures start duplicating query fragments, extract shared query helpers
- Be careful with `orWhere` chains and global scopes; admin filters often accidentally widen results
- Prefer model scopes or dedicated query methods for non-trivial filtering logic

For more detail, read `references/filter-and-query-risks.md`.

## Handler Guidelines

Handlers are often where admin code drifts into application logic.

Handlers may:
- trigger exports
- invoke services
- return admin responses
- coordinate admin-side file generation

Handlers should not:
- own external API protocol details if a contract/service already should
- parse files and upload data inline if that logic is reusable elsewhere
- swallow exceptions silently
- accumulate unrelated responsibilities

For exports and integrations:
- prefer a service or contract for the external system
- keep the handler as the admin entry point
- log failures with enough operational context
- surface actionable admin-facing errors where appropriate

## Validation And Authorization

- Use Laravel validation and authorization conventions
- Keep admin validation explicit in `rules()` or dedicated request-like abstractions if already used
- Reuse policies, gates, and application services rather than inventing MoonShine-only authorization logic
- Do not rely on UI visibility alone for protection

## Query And Performance Rules

- Prefer Eloquent relationships and eager loading
- Avoid N+1 in index/detail pages
- Do not build extremely wide joins in multiple resources if a scope/view/query object can centralize them
- Keep admin queries readable; if a query needs a long comment to be understandable, it likely belongs elsewhere

## Error Handling

- Never swallow exceptions
- Use specific exceptions where meaningful
- Log operational failures with context
- Admin toast and error responses should be useful but not expose sensitive details

## Code Review Checklist For MoonShine Changes

Check these first:

- Is MoonShine code still a thin admin layer?
- Did any business logic get added to a resource/page/handler that belongs in `Actions` or `Services`?
- Are filters/query changes correct under global scopes and `orWhere` semantics?
- Are field callbacks presentation-only?
- Are validation and authorization explicit?
- Is duplicated admin logic being introduced?
- Are export/integration flows delegated to services/contracts?
- Are tests needed for the changed behavior?

## Output Expectations

When completing a MoonShine task:

- explain briefly whether the change stayed in MoonShine or was moved into an action/service
- mention any architecture boundary concern you found
- if reviewing code, list findings first, ordered by severity
- if no issues are found, say so explicitly and mention any residual testing or maintenance risk

## Examples Of Good Decisions

Good:
- add a new admin field tab for translated content in a resource
- wire a button or handler to an existing application service
- extract repeated filter logic into a model scope
- move a ban/unban/account transition out of a resource method into an action

Bad:
- add financial mutation logic directly in a resource action
- add external API upload logic directly in a field callback
- duplicate the same account/package rule in controller, Livewire, and MoonShine
- let a resource own a large cross-domain SQL/query orchestration block without extraction
