# NutriTrace Jenkins Pipeline — Step-by-Step Documentation

This document explains the NutriTrace Jenkins pipeline defined in `Jenkinsfile`.
Each section shows the exact pipeline step and a short explanation.

## Pipeline Options

```groovy
options {
    timestamps()
    disableConcurrentBuilds()
    buildDiscarder(logRotator(numToKeepStr: '20'))
}
```

- `timestamps()`: adds timestamps to console output.
- `disableConcurrentBuilds()`: prevents overlapping builds.
- `buildDiscarder(...)`: keeps only the last 20 builds.

## Environment Variables

```groovy
environment {
    SONAR_HOST_URL = 'http://localhost:9000'
    JAVA_HOME = '/usr/lib/jvm/java-21-openjdk'
    PATH = "${JAVA_HOME}/bin:${env.PATH}"
}
```

- `SONAR_HOST_URL`: SonarQube server URL.
- `JAVA_HOME` / `PATH`: Java 21 used by SonarScanner.

---

## Stage 1 — Checkout

```groovy
stage('Checkout') {
    steps {
        checkout scm
    }
}
```

Clones the repository from GitHub and loads the `Jenkinsfile`.

---

## Stage 2 — Install PHP Dependencies

```groovy
stage('Install PHP dependencies') {
    steps {
        sh 'composer install --no-interaction --prefer-dist'
    }
}
```

Installs PHP dependencies without running interactive prompts.

---

## Stage 3 — Prepare Environment

```groovy
stage('Prepare environment') {
    steps {
        sh '''
            set -e
            cp .env.example .env
            php artisan key:generate
            touch database/database.sqlite
            sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
            sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${WORKSPACE}/database/database.sqlite|" .env
        '''
    }
}
```

Creates a local `.env`, generates the application key, and configures SQLite for testing.

---

## Stage 4 — Lint (Pint)

```groovy
stage('Lint (Pint)') {
    steps {
        sh './vendor/bin/pint --test'
    }
}
```

Checks PHP code style using Laravel Pint.

---

## Stage 5 — Syntax Check

```groovy
stage('Syntax check') {
    steps {
        sh 'find app database routes tests -name "*.php" -print0 | xargs -0 -n1 -P4 php -l > /dev/null'
    }
}
```

Runs `php -l` on all PHP files in parallel.

---

## Stage 6 — Build Frontend

```groovy
stage('Build frontend') {
    steps {
        sh '''
            set -e
            npm ci
            for attempt in 1 2 3; do
                if npm run build; then
                    break
                fi
                echo "Frontend build failed on attempt $attempt; retrying..."
                sleep 5
                if [ "$attempt" = "3" ]; then
                    exit 1
                fi
            done
        '''
    }
}
```

Installs Node dependencies and builds Tailwind/Vite assets. The build is retried up to three times to handle transient network failures while fetching fonts.

---

## Stage 7 — Migrate & Seed

```groovy
stage('Migrate & seed') {
    steps {
        sh '''
            set -e
            php artisan migrate --force
            php artisan db:seed --force
        '''
    }
}
```

Creates the database schema and inserts demo data.

---

## Stage 8 — Tests & Coverage

```groovy
stage('Tests & coverage') {
    steps {
        sh 'php -d zend_extension=xdebug.so -d xdebug.mode=coverage vendor/bin/phpunit --coverage-clover coverage.xml'
    }
}
```

Runs the full PHPUnit suite with Xdebug coverage and generates `coverage.xml`.

---

## Stage 9 — SonarQube Analysis

```groovy
stage('SonarQube Analysis') {
    steps {
        withCredentials([string(credentialsId: 'sonar', variable: 'SONAR_TOKEN')]) {
            sh '''
                set -e
                if [ -z "${SONAR_HOST_URL:-}" ]; then
                  echo "WARN: SONAR_HOST_URL not configured. Skipping SonarQube analysis."
                  exit 0
                fi

                sonar-scanner \
                  -Dsonar.host.url="$SONAR_HOST_URL" \
                  -Dsonar.token="$SONAR_TOKEN" \
                  -Dsonar.scanner.skipJreProvisioning=true \
                  -Dsonar.qualitygate.wait=true \
                  -Dsonar.qualitygate.timeout=300
            '''
        }
    }
}
```

Runs SonarScanner and waits for the quality gate. The build fails if the gate fails.

---

## Stage 10 — Deploy Locally

```groovy
stage('Deploy locally') {
    steps {
        sh '''
            set -e
            echo "Deploying locally with Docker Compose..."
            docker compose down || true
            docker compose up -d --build
            echo "Local deployment complete."
            echo "UI available at: http://localhost:8000"
        '''
    }
}
```

Deploys the application locally using Docker Compose and prints the local UI link.

---

## Post Actions

```groovy
post {
    success {
        echo 'CI/CD pipeline completed successfully.'
        echo 'UI available at: http://localhost:8000'
    }

    failure {
        echo 'Pipeline failed. Check the stage logs above.'
    }

    cleanup {
        archiveArtifacts artifacts: 'coverage.xml,.scannerwork/report-task.txt', allowEmptyArchive: true
        sh 'rm -f coverage.xml; rm -rf .scannerwork || true'
    }
}
```

- `success`: printed when all stages pass.
- `failure`: printed when a stage fails.
- `cleanup`: archives coverage/report files and removes temporary files.

---

## Secrets Used

| Jenkins Credential ID | Purpose |
| --- | --- |
| `sonar` | SonarQube authentication token |


Secrets are stored only in Jenkins and are never committed to Git.
