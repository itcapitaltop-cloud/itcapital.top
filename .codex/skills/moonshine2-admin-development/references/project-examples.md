# Project Examples

Use these examples as orientation points for this repository.

## Example: `NewsResource`

`app/MoonShine/Resources/NewsResource.php` is a good reference for:
- explicit resource structure
- multilingual tabbed admin fields
- page-aware field visibility
- localized display formatting
- direct, readable admin validation rules
- simple query ordering kept inside the resource

This is the preferred shape for content-focused MoonShine resources.

## Example: `UserResource`

`app/MoonShine/Resources/UserResource.php` shows both useful patterns and risk areas.

Useful patterns:
- dedicated page classes
- resource-specific list query shaping
- admin filters tied to summary data

Risk areas to watch:
- large inline filter closures
- mutation methods directly on the resource
- broad query complexity
- many infrastructure/domain dependencies imported into the resource

When editing similar resources, prefer moving reusable or state-changing behavior into `Actions` or `Services`.

## Example: `GoogleSheetsExportIndexDataHandler`

`app/MoonShine/Handlers/GoogleSheetsExportIndexDataHandler.php` is a good example of why handlers need boundary discipline.

Valid role of a handler:
- admin entry point
- invoking export flow
- returning MoonShine response

Risk area:
- external integration details and file processing can accumulate inline and become hard to reuse or test

Preferred direction:
- handler coordinates
- service/contract owns upload/integration logic
- logging and error handling stay explicit

## Example: `UserFormPage`

`app/MoonShine/Pages/User/UserFormPage.php` shows the page split pattern:
- dedicated page class
- layer methods available for composition
- minimal structure ready for page-level extension

Do not bloat such pages unless the customization is genuinely page-specific.

## Practical Heuristic

If the code looks like:
- admin labels, tabs, fields, formatting, visibility: MoonShine is the right place
- state changes, account actions, export workflows, external API details: move down into `Actions`, `Services`, or contracts
