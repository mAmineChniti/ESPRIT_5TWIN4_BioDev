---
paths:
  - 'resources/**'
---

# Resources

## English-only UI, names and identifiers
The app UI is English-only. All Blade copy, section titles, table headers, `aria-label`/`sr-only` text, alt text, `<caption>`, validation messages, flash messages and notification text must be English. No French or other non-English string in markup, including comments intended for end users. When a view is renamed or translated, grep the whole file for leftover French — the `Parcours`→`Journey` and `Region`→`AgriculturalRegion` renames both left stale strings in `back/farms` and the agricultural-region views that no test caught.

Files and directories are English and `kebab-case` for views (`farm-fields.blade.php`), `PascalCase` for classes (`JourneyStepController.php`), `snake_case` for tables and columns (`journey_steps`, `step_date`). Never French or accented names: no `etapes_parcours`, `EtapeParcours`, `parcours/`, `regions/`.

Code identifiers are English: variable and method names, class names, enum cases, route names and URI segments (`processor.journeys.steps.store`, `/agricultural-regions`). No `_name`-style abbreviations or French leftovers — use the full word.

Enum backing values, and every string compared against or persisted for them, stay English and are written once. Renaming a value means fixing the writers too, not just the readers: a data migration that rewrites rows is not finished until the seeder, factories and validation `Rule::in`/enum lists emit the new value. A stale writer silently makes rows uncreatable, and a stale column default leaves the enum unreadable.
