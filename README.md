# NutriTrace

Farm-to-plate food traceability platform (subject n°5): follow every product from
producer → processor → distributor → consumer, with environmental footprint and
certifications (organic, local, fair trade) to fight greenwashing and help
consumers make informed choices.

**Stack:** Laravel · Breeze (auth) · MariaDB · Tailwind CSS 4 · Alpine.js ·
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

This creates the `foods` and `meals` tables and fills them with demo data
(20 foods, 10 meals) plus a test user (`test@example.com` / `password`).
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
Pint, Vite build, migrations and PHPUnit on PHP 8.4 + SQLite.

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
