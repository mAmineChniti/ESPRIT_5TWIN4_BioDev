<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- This project uses the streamlined Laravel 11+ structure: register middleware, exceptions, and routing in `bootstrap/app.php` and service providers in `bootstrap/providers.php`. There is no `app/Http/Kernel.php` or `app/Console/Kernel.php`, and commands in `app/Console/Commands/` auto-register.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== frontend rules ===

# Frontend & April UI

Before building any UI, confirm what April UI already provides. Do not hand-roll what the library ships — that is the single most common source of drift in this codebase.

- List the real components first: `ls vendor/yungifez/april-ui/resources/views/components/`. Available: accordion, alert, alert-dialog, avatar, badge, banner, breadcrumb, bubble, button, button-group, card, carousel, chart, checkbox, collapsible, combobox, command, context-menu, data-table, date-picker, dialog, dropdown-menu, editor, input, input-group, label, loading-spinner, native-select, popover, select, separator, sheet, sidebar, skeleton, slider, steps, switch, tabs, textarea, tooltip, attachment.
- Use them by default for: cards, buttons, every form control, tables (`april:data-table`), confirmations (`april:alert-dialog`, not `confirm()`), status pills (`april:badge`), timelines and progress (`april:steps`), loading (`april:loading-spinner`), empty results (`april:skeleton`).
- Check the sibling views for the established convention and match it. A component that only one view uses is drift, not a feature.
- Prefer `variant="none"` plus semantic classes when a component has no variant that fits. April has no `success` badge/alert variant — do not invent one; use `secondary`.
- April renders slots **conditionally** (`@isset($content)`). A mis-nested `<x-slot:>` drops that whole region silently instead of erroring. Assert on rendered output in tests, not just on a 200.

## Theme tokens

Every colour must come from `resources/css/app.css`. That file redefines April's HSL channels, so repainting the theme repaints every component at once.

- The only valid colour utilities are: `background`, `foreground`, `card`, `card-foreground`, `popover`, `popover-foreground`, `primary`, `primary-foreground`, `secondary`, `secondary-foreground`, `muted`, `muted-foreground`, `accent`, `accent-foreground`, `destructive`, `destructive-foreground`, `border`, `input`, `ring`, `sidebar`, `sidebar-foreground`, `sidebar-primary`, `sidebar-primary-foreground`, `sidebar-accent`, `sidebar-accent-foreground`, `sidebar-border`, `sidebar-ring`, `chart-1`–`chart-5`.
- Never use a Tailwind palette name (`bg-green-900`, `text-red-500`, `bg-white`) or a hex/`rgb()`/`hsl()` literal in markup. The theme has no warning/amber channel, so those never respond to the palette.
- Never hand-write `dark:` variants. The tokens already switch; a manual `dark:bg-*` fights them and can produce unreadable dark-on-dark.
- Opacity modifiers (`bg-primary/10`) are fine and resolve through `color-mix`.
- Bare `rounded` is a hardcoded `0.25rem` that does **not** read `--radius`. Use `rounded-md`/`lg` so it tracks the scale.
- Bar radius must not exceed its container's — an `rounded-xl` panel inside an `rounded-lg` card looks like a rendering fault.

### Class names must be statically visible

Tailwind scans source text. A class built at runtime is never emitted.

- Never interpolate: `class="text-{{ $tone }}"`. This silently produces no CSS at all, and the bug is invisible until someone looks at the page.
- Return whole class strings from PHP instead, following `EnvironmentalScore::badgeClasses()`: `match ($this) { … }`.
- Keep the literals in one place (a `match` on an enum, as in `App\Enums\VerdictTone`) so both the scan and the rendering stay correct.
- Verify any utility you are unsure actually exists in the compiled CSS after `npm run build`. `text-md` is not in Tailwind v4's scale; the classes are `text-xs`…`text-5xl`.

### Blade + Alpine

- On a component tag (`<april:…>`, `<x-…>`), a leading `:` is a **Blade** binding. `<april:button :disabled="! enabled">` makes Blade resolve `enabled` as a PHP constant and fatal. Use `x-bind:disabled`.
- On a plain HTML element, `:class`/`x-bind:class` pass through to Alpine normally.
- Any JSON posted from the page needs the CSRF token: the `front` layout must publish `<meta name="csrf-token">` and the JS must send `X-CSRF-TOKEN`. Without it every POST is a 419.

## Accessibility is part of "done"

- Every input has a matching `id` and a `<april:label for="…">`. A `<label>` wrapping a checkbox is fine; wrapping a *second* control inside it is not — that control inherits the label text as its accessible name and toggles the checkbox on click.
- Errors link to their field: `aria-describedby` pointing at the message, `role="alert"`/`aria-live` on transient status text, and `aria-invalid` when the field is bad.
- Every `<th>` carries `scope`. A `colspan` must match the real column count.
- A control hidden with `class="hidden"` is out of the tab order and unusable by keyboard. Use a real label, or `sr-only` only when nothing competes for `width`/`height` on the same element.
- Icon-only buttons need `aria-label` or `sr-only` text.
- A disabled control must say *why* it is disabled, not just look faded.

=== audit rules ===

# Code Correctness & Auditing

Correctness is the first requirement. Style is secondary and never compensates for logic that is wrong.

## Verify, do not assume

- An audit is not finished because static analysis, a 200 status, or a passing test against a fake passed. Confirm behaviour at runtime.
- Prefer a test that stubs the boundary (e.g. `Http::fake()`) over assertions on prose from a live model: model output is non-deterministic, so live assertions are flaky. Assert the stable part — severities, categories, scores — not the wording.
- Add `Http::preventStrayRequests()` to any test that fakes HTTP, so a missing fake fails loudly instead of quietly hitting a real paid API.
- After changing config or a model name, check it against the live service. Retired provider models return `404` on every call, which looks like a broken feature rather than a config typo.
- When a page renders a component that depends on an external service, confirm the failure path too. It must say it could not check — never imply a pass it did not produce.

## Authorization

- Middleware and policies must agree. If a policy grants `admin` but the middleware excludes `admin`, the grant is unreachable dead code; if the middleware is broad and the policy narrow, test both layers.
- Role-based domain rules (which role may perform which stage) belong on the enum/policy, not scattered through controllers.
- Route-model-binding is not authorization. If a route binds two models, verify the second belongs to the first.
- View guards (`@can`) and controller guards (`authorize()`) are separate. Guard both; a route readable by any signed-in user must not render owner-only actions.
- Guard the actions that mutate state behind an explicit confirmation, and make the confirmation reflect the real consequence.

## Data and state

- Sync/pivot updates must not be conditional on the key being present, or "clear everything" becomes impossible to express.
- Multi-write operations belong in a transaction. A partial failure must not leave a product with no chain.
- Any column a query filters, sorts or groups on needs an index. `Category::firstOrCreate()` needs a unique index on the column it matches.
- Bound result sets, and aggregate in SQL rather than filtering a paginated page in PHP.
- Don't replace an authorization rule with a data cap: truncating the list a form depends on breaks the form.

## Do not pollute the project

- **Never run `tinker` against the development database.** Use factories in tests. If something must be checked live, inspect read-only; restore with `php artisan migrate:fresh --seed` afterwards.
- Do not commit secrets. Keys live in `.env` (gitignored); never paste a live key into a tracked file, a test, or a message.
- Before finishing, check `git status` for stray config files, temp scripts and logs.

## Report honestly

- State what you verified and how, separately from what you believe.
- Call out anything deliberately left undone and why, rather than implying full coverage.
- If an earlier claim turns out to be wrong, say so plainly and correct it.

=== navigation & access rules ===

# Navigation, Roles and Conditional UI

Broken navigation and leaked or hidden controls are correctness bugs, not polish. A link that 403s, a role menu that points at a role-gated area, or a page that renders the wrong view for a role is a defect.

## Navigation

- **Every link must resolve for the person who can see it.** Before shipping a nav entry, breadcrumb, footer link or CTA, check it returns 200 for each role that reaches the page — not just that the route exists.
- Role-gated destinations need a guard in the markup: `@auth`, `@can`, or an `isAdmin()`/`isProfessional()` check. A hardcoded `href="{{ url('/admin') }}"` in a shared layout 403s every non-admin and bounces guests to login.
- Prefer a named route (`route('consumer.dashboard')`) over `url()`. If a dashboard is per-role, resolve the route name from the role rather than hardcoding one role's path.
- Shared layouts are used by every role. Anything inside one that names a role-specific area must be conditional.
- Keep the nav honest about what exists: an entry pointing at a route you have not registered is worse than no entry.

## Middleware and roles

- The route file is the single source of truth for who may reach a controller. Put a whole feature in one `Route::middleware(...)` group rather than repeating the check per route, so two routes for the same feature cannot drift apart.
- Middleware decides who may **reach** a controller; policies decide what they may **do** inside it. Both layers are needed and they must agree — see the authorization notes in the audit rules.
- If a role is deliberately excluded (e.g. admins cannot register products, only professionals can), encode that in the policy and cover it with a test. Do not leave it as an accident of a middleware list.
- When you add or change a route, run `php artisan route:list` and confirm the entry appears once, with the intended middleware. A duplicate registration silently replaces the first.

## Conditional UI

- A control that mutates state must be guarded by the same rule the controller enforces: `@can('update', $food)`, `@can('recordTransition', [$food, $stage])`. The route being readable by any signed-in user is not permission to offer the action.
- **Gate views and controllers independently.** A hidden button is not authorization; a `403` from a forged POST is. Ship both.
- Conditional branches must be reachable. If a view is only ever rendered for certain roles, a branch for any other role is dead code that misleads the next reader — delete it rather than leaving it as a false promise. Before deleting, confirm the view really cannot render for that role.
- Data a template branches on must actually be supplied for that route. If a view reads `$myMeals` but the controller never routes that view for the role that would receive it, the branch is unreachable.
- Disabled controls need a reason. A faded `Delete` that silently does nothing is worse than a tooltip saying why.

## Pages render correctly

- Assert on rendered output, not only on status. April drops slots conditionally, so a 200 can still be missing a card, a table body or an alert.
- A table's `colspan` must equal its real column count. An empty-state row with the wrong span leaves a dangling border.
- Count `@if`/`@endif` (and `@foreach`/`@endforeach`, `@forelse`/`@endforeach`) when editing a template. An orphaned `@endif` parses to a fatal error in the compiled view, not a helpful Blade error — and it will not be caught by tests for routes that never render that view.
- Check every role's landing page and every page in a nav menu actually render. A quick matrix beats reasoning about it:

  ```bash
  # for each role: log in, then walk the routes that role can reach
  php artisan route:list --except-vendor
  ```

  Seeded demo accounts are `admin@nutritrace.com`, `producer@`, `processor@`, `distributor@`, `test@` / `consumer2@` (`example.com`), all with password `password`.
- After a seeder changes, re-check demo credentials and any hard-coded assumption about seeded IDs — the seeder owns that data, not the code.

=== verification rules ===

# Definition of Done

Finishing the edit is not finishing the task. After writing or changing code, run these before reporting back — an unverified change is an unfinished change.

## 1. Format

- Any PHP file touched → `vendor/bin/pint --dirty --format agent`. Do not hand-format around a Pint complaint; let Pint decide.
- Run it **after** the final edit, not before. Pint reformats, and re-editing afterwards re-introduces the drift.

## 2. Build

- Any Blade, CSS or JS change → `npm run build`. A class that compiles locally but is missing from the manifest fails only in the browser.
- If a Tailwind utility is not applying, check the built CSS for the class before assuming the markup is wrong.

## 3. Test

- Run the narrowest test that covers the change first, for a fast signal:
  `php artisan test --compact tests/Feature/SomeTest.php`
- Then run the **whole suite** before declaring done:
  `php artisan test --compact`
- A change is not verified until the full suite has been run *after* the last edit. Earlier green runs do not carry over.
- If a test fails, fix the cause rather than relaxing the assertion. An assertion loosened to pass is a deleted test.
- New behaviour gets a test that fails without the fix. Confirm this by reverting the fix and watching it go red, then restore it.

## 4. Then check

- `git status` — no stray config files, temp scripts, logs or `.bak` files.
- No secret committed. Keys belong in `.env` only.
- If you touched the dev database while testing, restore it with `php artisan migrate:fresh --seed`.

## 5. Report

- Say what you ran and what it output. "Tests pass" without a count is not a report.
- If something could not be run, say so instead of implying it was.
- Never claim completion from having written the code. Completion is the output of the commands above.

### Environment note

The suite is configured for SQLite in-memory (`phpunit.xml`). If it fails to connect, `pdo_sqlite` is not installed on this machine. Point it at a scratch MySQL schema rather than editing `phpunit.xml`, and delete the scratch config afterwards:

```bash
sed -e 's|value="sqlite"|value="mysql"|' -e 's|value=":memory:"|value="test_nutritrace"|' \
    phpunit.xml > phpunit.mysql.xml
php artisan test --configuration=phpunit.mysql.xml --compact
rm phpunit.mysql.xml
```

Never let that override reach a tracked file.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
