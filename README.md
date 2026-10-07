# NutriTrace

Farm-to-plate food traceability platform (subject n°5). Products are registered
by a producer, then move along a recorded supply chain — **produced →
processed → distributed** — and consumers can look up where a product came
from and which certifications back it.

Every supply chain hand-off is stored as a row in `stage_transitions`, so the
journey shown on a product page is real data rather than a claim. Certifications
are a proper entity with an issuing body and an expiry date, and a product's
environmental grade (A–E) is an enum, not free text — a product cannot claim a
certification that is not on file, and a grade renders with a colour that matches
its actual value.

**Stack:** Laravel 12 · Breeze (auth) · MariaDB · Tailwind CSS 4 · Alpine.js ·
[April UI](https://aprilui.dev) Blade components · Lucide icons · Docker.

---

## 1. Requirements

| Tool | Minimum | Notes |
| --- | --- | --- |
| PHP | 8.3+ with `pdo_mysql` | See driver notes below |
| Composer | 2.x | [getcomposer.org](https://getcomposer.org) |
| Node.js + npm | 22+ | [nodejs.org](https://nodejs.org) |
| MariaDB (or MySQL) | 10.6+ / 8.0+ | Server running locally |
| Git | any | — |

**Linux (Arch/CachyOS):** `sudo pacman -S php composer nodejs npm mariadb`
then `sudo systemctl enable --now mariadb`.
**Linux (Ubuntu/Debian):** `sudo apt install php php-mysql php-xml php-mbstring php-curl php-zip composer nodejs npm mariadb-server`
then `sudo systemctl enable --now mariadb`.
**Windows (XAMPP):** install [XAMPP](https://www.apachefriends.org) (bundles PHP,
MariaDB and phpMyAdmin), then [Node.js LTS](https://nodejs.org) and Composer
(composer-setup.exe — point it at `C:\xampp\php\php.exe`). Open the XAMPP
Control Panel and **Start** Apache and MySQL. Use the "Shell" button in the
control panel (or add `C:\xampp\php` and `C:\xampp\mysql\bin` to your PATH)
for all commands below.
Then open a terminal and enable the MySQL driver by editing your `php.ini`
(find it with `php --ini`; with XAMPP it is `C:\xampp\php\php.ini`) and
uncommenting (removing the `;`):

```ini
extension=pdo_mysql
```

(XAMPP usually ships it already enabled — only check if the verify step fails.)

Verify on any OS with:

```bash
php -v
php -m | grep -i pdo_mysql   # must print "pdo_mysql"
composer --version
node --version
```

> If `pdo_mysql` is missing on Linux, install/enable it
> (Ubuntu: `sudo apt install php-mysql`; Arch: add `extension=pdo_mysql`
> to `/etc/php/conf.d/` or `php.ini`) and retry. Without it you will get
> `could not find driver` on every page. As a last resort without root
> access, this repo ships `.php/pdo.ini` — point PHP at it per shell with
> `export PHP_INI_SCAN_DIR="$PWD/.php"` before any `php`/`artisan` command.

---

## 2. Get the code

```bash
git clone https://github.com/mAmineChniti/NutriTrace.git
cd NutriTrace
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate
```

---

## 3. Create the database

Start MariaDB if needed (`sudo systemctl start mariadb` on Linux; on Windows
press **Start** next to MySQL in the XAMPP Control Panel), then create the
database. Via command line:

```bash
mariadb -u root -p -e "CREATE DATABASE IF NOT EXISTS nutritrace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Or on Windows/XAMPP without the command line: open phpMyAdmin
(http://localhost/phpmyadmin), tab **Databases**, create `nutritrace`
with collation `utf8mb4_unicode_ci`.

> `-p` asks for your root password (Linux with password auth). On Linux with
> socket auth, use `sudo mariadb -e "..."` instead. **XAMPP default:** user
> `root` with an **empty password** — so in `.env` set `DB_HOST=127.0.0.1`,
> `DB_PORT=3306`, `DB_DATABASE=nutritrace`, `DB_USERNAME=root`,
> `DB_PASSWORD=` (leave empty). Adjust the `DB_*` values in `.env` to match
> your machine (Linux: your MySQL username, host and password).

---

## 4. Install dependencies

```bash
composer install
npm install
```

---

## 5. Migrate and seed

```bash
php artisan migrate --seed
```

This creates every table and fills it with demo data: 6 categories, 3
certifications, 20 products (each owned by a producer, each with a recorded
supply chain of 1–3 steps), 10 meals and 5 users. Demo logins all use the
password `password`:

| Email | Role |
| --- | --- |
| `producer@example.com` | producer |
| `processor@example.com` | processor |
| `distributor@example.com` | distributor |
| `test@example.com` | consumer |
| `admin@example.com` | admin |

To start over: `php artisan migrate:fresh --seed` (safe to re-run).

---

## 6. Build the frontend and serve

```bash
npm run build
php artisan serve
```

Open in your browser:

- Front office → http://localhost:8000
- Back office → http://localhost:8000/admin (login required — use the test
  account above, or register a new one at `/register`)
- Login / Register → http://localhost:8000/login

Password-reset emails use the `log` mail driver in development — read them in
`storage/logs/laravel.log`.

For live-reload development, run `npm run dev` in a second terminal instead of
`npm run build`.

---

## 7. Docker (alternative)

With Docker and Docker Compose installed, no local PHP/Node/MariaDB needed:

```bash
docker compose up --build
```

This starts MariaDB plus the app (dependencies installed, frontend built,
migrated) on http://localhost:8000. Seed demo data afterwards:

```bash
docker compose exec app php artisan db:seed
```

Stop everything with `docker compose down` (add `-v` to also drop the database).

---

## 8. Checks (lint, tests, CI)

```bash
composer lint      # Pint — fails if PHP is not formatted
composer format    # Pint — auto-fix formatting
php artisan test   # full suite (Breeze auth + example tests)
```

`phpunit.xml` uses in-memory SQLite, so the suite runs anywhere the
`pdo_sqlite` driver exists. Without it, point the suite at MySQL instead:

```bash
DB_CONNECTION=mysql DB_HOST=localhost DB_DATABASE=<scratch_db> DB_USERNAME=... DB_PASSWORD=... php vendor/bin/phpunit
```

> Use a scratch database — tests wipe it (`RefreshDatabase`).
> Never run the suite against your dev database.

Pushes and pull requests run GitHub Actions (`.github/workflows/ci.yml`):
Pint, a PHP syntax sweep, Vite build, migrations, seeding and PHPUnit on
**PHP 8.3 + SQLite** — the same version the Docker image runs.

---

## 9. Troubleshooting

| Symptom | Fix |
| --- | --- |
| `could not find driver` | `pdo_mysql` not loaded — see Requirements. |
| `Address already in use` / wrong port | Another server holds the port: `php artisan serve --port=8001`, or stop the old one. |
| Page shows old styles or old April script host | `php artisan view:clear`, then `npm run build` if CSS changed. |
| `SQLSTATE[HY000] [1049] Unknown database` | Create the database (step 3) or fix `DB_*` in `.env`. |
| `SQLSTATE[HY000] [1045] Access denied` | Wrong `DB_USERNAME`/`DB_PASSWORD` in `.env`. |
| `Vite manifest not found` | Frontend never built — run `npm run build` (or `npm run dev`). |
| `419 Page Expired` on forms | Session/cookie issue — `php artisan config:clear`, reload the form page first. |
| Blank page after pulling changes | `composer install && npm install && npm run build && php artisan migrate` |

---

## 10. Consumer space

Three public pages, no login required to view them:

| Page | Route | What it does |
| --- | --- | --- |
| Search / scan | `/products` | Look a product up by name, origin, producer or certification. Filter by category, eco grade or "certified only", and sort by recency, scans, grade or rating. `/products/scan?code=…` resolves a scanned code straight to a product and increments its scan counter. |
| Product page | `/products/{food}` | The full traceability record: producer, certifications with issuer and expiry, consumer reviews, and an **interactive Chart.js timeline** showing how long each supply chain stage took. Click a bar for who recorded it. |
| Greenwashing guide | `/greenwashing` | Awareness page: six concrete things to check on any label, and exactly how NutriTrace records each one. |

Every product page carries a **transparency score** out of 100, computed only from
checkable facts — how many chain stages are recorded, whether certifications are
on file and still valid, and how many greenwashing reports a reviewer has upheld:

```
40 base
+12 per recorded stage (max 3)
+15 if any certification is on file, +5 more if one is still valid
+10 if an environmental grade is recorded
−15 per upheld report (capped at −40)
```

Consumers can leave a review and file a greenwashing report against any reason in
`App\Enums\ReportReason`. Reports are not decorative: an **upheld** report lowers
the product's transparency score and flips its badge to *At risk*. Only admins can
uphold or dismiss a report.

The signed-in consumer dashboard lives at `/consumer/dashboard`: calories per day
(line chart), eco-grade mix of what they ate (doughnut), share certified, plus
their own reviews and filed reports. Charts load from a separate Vite entry
(`resources/js/charts.js`) so they are only downloaded on pages that use them.

---

## 11. Espace Consommateur — Amine Chnitti

The consumer-facing AI module, at `/consumer/space`. Four features, all reading the
same traceability record.

| Feature | Where | What it does |
| --- | --- | --- |
| AI greenwashing detection | product page, `/consumer/space` | An auditor model is given the product's **claims** (name, category, origin, grade, certifications) and its **evidence** (recorded chain steps with actors, nutrition, reports, transparency score), and returns typed findings with a severity and the exact record field that triggered each one. |
| Product assistant | product page | Answers questions about one product **only** from the record, and cites the fields it used. If the record does not contain the answer it says so rather than guessing. |
| Responsible recommendations | `/consumer/recommendations` | Ranked by transparency score, then environmental grade, scoped to categories the consumer has logged. |
| Reporting suspicious information | product page, `/consumer/space` | Every finding carries a **one-click report** that files it under the matching `ReportReason` automatically. |

### Switching the AI on

The detector and assistant need an API key. Set it in `.env`:

```dotenv
AI_PROVIDER=groq
AI_KEY=your_key_here
AI_MODEL=openai/gpt-oss-120b
```

Free tiers, no payment card required:

| Provider | Key | Provider value | Model |
| --- | --- | --- | --- |
| Groq | <https://console.groq.com/keys> | `groq` | `llama-3.3-70b-versatile` |
| OpenRouter | <https://openrouter.ai/keys> | `openrouter` | `meta-llama/llama-3.3-70b-instruct` |
| Google AI Studio | <https://aistudio.google.com/apikey> | `gemini` | `gemini-2.5-flash` |

Everything but Gemini speaks the OpenAI chat-completions shape; Gemini uses its own
request shape and is handled by one branch in `app/Services/Ai/AiClient.php`.
No vendor package is required — Laravel's HTTP client is used directly.

**Without a key every page still loads.** The detector reports that it could not
run and renders an *Analysis unavailable* panel. It never falls back to declaring a
product clean, because a traceability platform that invents an all-clear is worse
than one that admits it could not check.

### How it is wired

```
app/Services/Ai/AiClient.php                 HTTP + provider shapes + JSON decoding
app/Services/Greenwashing/
    ProductRecord.php                         flattens a Food into claims + evidence
    GreenwashingDetector.php                  prompt, JSON Schema, caching
    AnalysisReport.php                        typed result, risk score
    Finding.php
app/Services/Assistant/ProductAssistant.php   grounded question answering
app/Services/Recommendations/
    ProductRecommender.php                    computed ranking, model-written reason
app/Http/Controllers/ConsumerIntelligenceController.php
```

Design decisions worth knowing:

- **The model never picks the products it recommends.** Ranking is computed from
  the record; the model only writes the one-line reason. A model choosing
  "responsible products" has no basis to compare transparency scores.
- **The model never gets to state an unverified fact.** Both prompts state that a
  missing field is itself a finding, and the assistant is required to answer
  "NutriTrace has no record of…" rather than infer.
- **Findings map to report reasons** through `App\Enums\FindingCategory`, which is
  why a detected problem can be escalated in one click.
- **The risk score is derived from the findings**, so the headline number can never
  contradict the reasoning shown beneath it.
- **Responses are sanitised** — markup and whitespace runs are stripped from
  anything the model returns before it reaches a consumer.
- **Analyses are cached for 12 hours**, keyed on a hash of the record's own
  contents, so recording a new chain step invalidates the entry automatically.
  The cache stores a plain array, never the object, because every cache driver
  except `array` serialises.
- **The API key never reaches the browser.** The assistant asks the server, which
  holds the key and the record, and returns the answer plus its sources.

---

## Project conventions

- English only — code, comments, UI strings.
- April UI components are used as-is (`<april:...>`); never edit `vendor/`.
  To customize one: `php artisan april:publish <name>`, which copies it to
  `resources/views/vendor/april/components/`.
- Theme lives in `resources/css/app.css` (palette, certification + footprint
  tokens, dark mode). Layouts must use semantic tokens (`bg-background`,
  `text-foreground`, …), never hardcoded neutrals.
- Icons: Lucide via `<x-lucide-... />` (e.g. `<x-lucide-sprout />`).
- Auth is Breeze (Blade) restyled with April UI. **Never re-run
  `php artisan breeze:install`** — it overwrites the theme, the Vite config
  and the routes.
- Run `composer format` before committing; CI enforces `composer lint`.
- Run `php artisan april:doctor` if component rendering looks off.
