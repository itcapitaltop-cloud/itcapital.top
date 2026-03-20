# Filter And Query Risks

Use this file when reviewing or modifying MoonShine filters, listing queries, and admin data retrieval.

## Main Risk Areas

MoonShine filters and admin queries often fail in subtle ways:
- `orWhere` changes grouping and widens result sets
- global scopes interact badly with admin visibility toggles
- joins duplicate rows unexpectedly
- summary columns become nullable and break filters/sorting
- repeated query fragments drift between resources

## Review Checklist

Check these first:
- Are `where` and `orWhere` grouped correctly?
- Does disabling a global scope affect only the intended records?
- Can the join multiply rows?
- Are nullable joined columns handled safely?
- Is the same query fragment duplicated elsewhere?
- Would a model scope make this easier to read and safer to reuse?

## Preferred Patterns

Prefer:
- grouped closures for logical blocks
- model scopes for repeated filters
- eager loading over ad hoc per-row lookups
- clear aliases for joined summary columns
- extracted private methods when the same filter block repeats in one resource

Be careful with:
- `withoutGlobalScope(...)->orWhere(...)`
- mixed `where` and `orWhere` without grouping
- large inline filter closures
- filters that depend on hidden assumptions in joined tables

## When To Extract

Extract query logic out of a resource when:
- more than one admin surface needs it
- it takes more than a quick scan to verify correctness
- it encodes business semantics rather than display semantics
- the same join/selection/filter block appears repeatedly

Preferred extraction targets:
- model scopes
- query helpers close to the model
- application service/query object if the behavior is broader than MoonShine

## Performance Notes

For admin indexes:
- avoid N+1
- select only needed columns when joining summary tables
- do not compute expensive per-row data in formatting callbacks
- prefer database-side sorting/filtering when reasonable

Correctness is more important than micro-optimizing, but unreadable admin queries create long-term bugs.
