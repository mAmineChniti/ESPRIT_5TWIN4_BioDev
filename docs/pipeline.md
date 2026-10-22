# NutriTrace CI/CD Pipeline — Official Documentation

## Overview

NutriTrace uses a Jenkins declarative pipeline to build, test, analyze, and deploy the
application automatically. The pipeline is defined in the repository root as `Jenkinsfile`
and is executed by a Jenkins job named `nutritrace`.

The pipeline integrates:

- **Composer / PHP** dependency installation
- **Laravel** environment setup and database migration
- **Pint** code style checks
- **PHP syntax** checks
- **Vite / Tailwind** frontend build
- **PHPUnit** tests with Xdebug coverage
- **SonarQube** static analysis and quality gate
- **Render** deployment through a secured webhook

## Prerequisites

| Tool | Version | Notes |
| --- | --- | --- |
| Jenkins | 2.x | Running on port `8090` |
| SonarQube | 26.x | Running on port `9000` |
| PHP | 8.3+ | `pdo_mysql`, `gd`, `sqlite` extensions |
| Composer | 2.x | |
| Node.js | 22+ | |
| SonarScanner CLI | 6.2+ | Installed at `/opt/sonar-scanner` |

## Jenkins Configuration

### Credentials

The following Jenkins credentials (Secret text) must exist:

| Credential ID | Purpose |
| --- | --- |
| `sonar` | SonarQube user token |
| `render-deploy-hook` | Render deploy webhook URL |

Secrets are stored only in Jenkins and are never committed to the repository.

### Global Tools

- Java 21+ (used by SonarScanner)
- Git
- Node.js (optional, system Node is used)

## Pipeline Stages

### 1. Checkout

Clones the repository from GitHub (`master` branch) and loads `Jenkinsfile`.

### 2. Install PHP Dependencies

```bash
composer install --no-interaction --prefer-dist
```

### 3. Prepare Environment

- Copies `.env.example` to `.env`
- Generates `APP_KEY`
- Creates a SQLite database file
- Forces SQLite for the test environment

### 4. Lint (Pint)

```bash
./vendor/bin/pint --test
```

Ensures PHP code follows the project style.

### 5. Syntax Check

```bash
find app database routes tests -name '*.php' -print0 | xargs -0 -n1 -P4 php -l
```

### 6. Build Frontend

```bash
npm ci
npm run build
```

Compiles Tailwind CSS and JavaScript assets with Vite.

### 7. Migrate & Seed

```bash
php artisan migrate --force
php artisan db:seed --force
```

### 8. Tests & Coverage

```bash
php -d zend_extension=xdebug.so -d xdebug.mode=coverage vendor/bin/phpunit --coverage-clover coverage.xml
```

Runs the full PHPUnit suite and generates a Clover coverage report.

### 9. SonarQube Analysis

```bash
sonar-scanner \
  -Dsonar.host.url="$SONAR_HOST_URL" \
  -Dsonar.token="$SONAR_TOKEN" \
  -Dsonar.scanner.skipJreProvisioning=true \
  -Dsonar.qualitygate.wait=true \
  -Dsonar.qualitygate.timeout=300
```

The quality gate must pass before deployment.

### 10. Deploy to Render

```bash
curl -fsS -X POST "$RENDER_DEPLOY_HOOK"
```

Triggered only after all previous stages succeed.

## SonarQube Configuration

Project key: `NutriTrace`

Configuration file: `sonar-project.properties`

Important settings:

- Sources: `app`, `config`, `database`, `routes`, `resources`
- Tests: `tests`
- PHP version: `8.5`
- Coverage report: `coverage.xml`

## Render Deployment

The application is deployed to Render using a Blueprint (`render.yaml`).

Services:

- `nutritrace` (web, Docker)
- `nutritrace-db` (MySQL 8, private service with persistent disk)

Required secrets in Render:

- `APP_KEY`
- `DB_PASSWORD`
- `MYSQL_ROOT_PASSWORD`
- `MYSQL_PASSWORD`

## Running the Pipeline Manually

### Trigger from Jenkins UI

1. Open `http://localhost:8090/job/nutritrace/`
2. Click **Build Now**

### Trigger from CLI

```bash
rm -f /tmp/jcookies.txt
curl -s -c /tmp/jcookies.txt -u "admin:admin" 'http://localhost:8090/' >/dev/null
CRUMB=$(curl -s -b /tmp/jcookies.txt -u "admin:admin" 'http://localhost:8090/crumbIssuer/api/xml?xpath=concat(//crumbRequestField,":",//crumb)')
FIELD=${CRUMB%%:*}; VALUE=${CRUMB#*:}
curl -b /tmp/jcookies.txt -u "admin:admin" -X POST "http://localhost:8090/job/nutritrace/build" -H "$FIELD: $VALUE"
```

## Verification Checklist

- [ ] `vendor/bin/pint --dirty --format agent` passes
- [ ] `npm run build` passes
- [ ] `php artisan test --compact` passes (677 tests)
- [ ] SonarQube quality gate passes
- [ ] Jenkins build is `SUCCESS`
- [ ] Render deployment succeeds
