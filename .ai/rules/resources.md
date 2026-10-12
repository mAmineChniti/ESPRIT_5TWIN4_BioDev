---
paths:
  - 'resources/**'
---

# Resources

## UI languages and the English source of truth
English is the source language: every user-facing string lives in markup (or code) in English first, wrapped in `__()` with the exact English text as the key. French and Arabic live only in `lang/fr.json` and `lang/ar.json` — never hardcode a French or Arabic string in markup. When a view is renamed or reworded, grep the whole file for strings missing from the catalogs; a key renamed in markup but not in `fr.json`/`ar.json` silently falls back to English with no error.
The locale comes from the URL prefix (`/{locale}`, `Locale` enum is the single list), then the visitor's locale cookie, then English; `SetLocale` owns that chain and forgets the segment before dispatch so positional model binding keeps working. New translated surface needs a test that renders the page under `/fr` (and `/ar` for `dir="rtl"`) and asserts on the translated output.

## English-only names and identifiers
Files and directories are English and `kebab-case` for views (`farm-fields.blade.php`), `PascalCase` for classes (`JourneyStepController.php`), `snake_case` for tables and columns (`journey_steps`, `step_date`). Never French or accented names: no `etapes_parcours`, `EtapeParcours`, `parcours/`, `regions/`.

Files and directories are English and `kebab-case` for views (`farm-fields.blade.php`), `PascalCase` for classes (`JourneyStepController.php`), `snake_case` for tables and columns (`journey_steps`, `step_date`). Never French or accented names: no `etapes_parcours`, `EtapeParcours`, `parcours/`, `regions/`.

Code identifiers are English: variable and method names, class names, enum cases, route names and URI segments (`processor.journeys.steps.store`, `/agricultural-regions`). No `_name`-style abbreviations or French leftovers — use the full word.

Enum backing values, and every string compared against or persisted for them, stay English and are written once. Renaming a value means fixing the writers too, not just the readers: a data migration that rewrites rows is not finished until the seeder, factories and validation `Rule::in`/enum lists emit the new value. A stale writer silently makes rows uncreatable, and a stale column default leaves the enum unreadable.
