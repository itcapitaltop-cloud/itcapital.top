# Page Patterns

Use this file when working with `app/MoonShine/Pages/*`.

## Purpose

Pages define admin presentation structure:
- form layout
- detail composition
- index composition
- page-local admin UI layers

Pages are not the home for domain orchestration.

## Page Responsibilities

### IndexPage

Use for:
- listing composition
- index widgets/components
- page-level UI arrangement
- admin-only visual helpers

Avoid:
- heavy query logic
- state transitions
- external calls

### FormPage

Use for:
- form composition
- top/main/bottom layer arrangement
- admin presentation around existing resource fields

Avoid:
- embedding save-side business rules
- duplicating validation already expressed in the resource or application layer

### DetailPage

Use for:
- detail layout
- admin display components
- grouped read-only information

Avoid:
- hidden mutations triggered by rendering
- loading excessive unrelated data

## Layer Methods

Methods like:
- `topLayer()`
- `mainLayer()`
- `bottomLayer()`

should stay compositional.

If they only return parent layers, keep them minimal. Do not add boilerplate unless the page actually needs customization.

## Reuse

If multiple pages repeat the same admin fragments:
- extract a reusable MoonShine component
- extract a small helper method
- avoid copy-paste across domains

Prefer reuse for presentation, not for domain behavior.

## Testing Guidance

Page changes usually need tests when they affect:
- visibility of important controls
- authorization behavior
- admin-triggered actions
- critical rendering of operational data

Do not over-test layout boilerplate. Test meaningful admin behavior.
