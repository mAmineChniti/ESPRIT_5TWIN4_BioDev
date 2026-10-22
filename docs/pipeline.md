# Pipeline Jenkins NutriTrace — Documentation étape par étape

Ce document explique le pipeline Jenkins de NutriTrace défini dans `Jenkinsfile`.
Chaque section présente l'étape exacte du pipeline et une courte explication.

## Options du pipeline

```groovy
options {
    timestamps()
    disableConcurrentBuilds()
    buildDiscarder(logRotator(numToKeepStr: '20'))
}
```

- `timestamps()` : ajoute des horodatages dans la sortie console.
- `disableConcurrentBuilds()` : empêche les builds simultanés.
- `buildDiscarder(...)` : conserve uniquement les 20 derniers builds.

## Variables d'environnement

```groovy
environment {
    SONAR_HOST_URL = 'http://localhost:9000'
    JAVA_HOME = '/usr/lib/jvm/java-21-openjdk'
    PATH = "${JAVA_HOME}/bin:${env.PATH}"
}
```

- `SONAR_HOST_URL` : URL du serveur SonarQube.
- `JAVA_HOME` / `PATH` : Java 21 utilisé par SonarScanner.

---

## Étape 1 — Checkout

```groovy
stage('Checkout') {
    steps {
        checkout scm
    }
}
```

Clone le dépôt depuis GitHub et charge le `Jenkinsfile`.

---

## Étape 2 — Installation des dépendances PHP

```groovy
stage('Install PHP dependencies') {
    steps {
        sh 'composer install --no-interaction --prefer-dist'
    }
}
```

Installe les dépendances PHP sans interaction.

---

## Étape 3 — Préparation de l'environnement

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

Crée un fichier `.env` local, génère la clé d'application et configure SQLite pour les tests.

---

## Étape 4 — Lint (Pint)

```groovy
stage('Lint (Pint)') {
    steps {
        sh './vendor/bin/pint --test'
    }
}
```

Vérifie le style du code PHP avec Laravel Pint.

---

## Étape 5 — Vérification syntaxique

```groovy
stage('Syntax check') {
    steps {
        sh 'find app database routes tests -name "*.php" -print0 | xargs -0 -n1 -P4 php -l > /dev/null'
    }
}
```

Exécute `php -l` sur tous les fichiers PHP en parallèle.

---

## Étape 6 — Build du frontend

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

Installe les dépendances Node et compile les assets Tailwind/Vite. Le build est réessayé jusqu'à trois fois pour gérer les échecs réseau transitoires lors du téléchargement des polices.

---

## Étape 7 — Migration et seed

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

Crée le schéma de base de données et insère les données de démonstration.

---

## Étape 8 — Tests et couverture

```groovy
stage('Tests & coverage') {
    steps {
        sh 'php -d zend_extension=xdebug.so -d xdebug.mode=coverage vendor/bin/phpunit --coverage-clover coverage.xml'
    }
}
```

Exécute toute la suite PHPUnit avec la couverture Xdebug et génère `coverage.xml`.

---

## Étape 9 — Analyse SonarQube

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

Exécute SonarScanner et attend le quality gate. Le build échoue si le quality gate échoue.

---

## Étape 10 — Déploiement local

```groovy
stage('Deploy locally') {
    steps {
        sh '''
            set -e
            export BUILD_ID=dontKillMe
            export JENKINS_NODE_COOKIE=dontKillMe

            # Avoid docker-credential-secretservice D-Bus failures in CI.
            mkdir -p .docker-ci/bin
            cat > .docker-ci/bin/docker-credential-secretservice << 'EOF'
#!/bin/sh
case "$1" in
  get|list|store|erase) echo '{}' ;;
  *) echo '{}' ;;
esac
exit 0
EOF
            chmod +x .docker-ci/bin/docker-credential-secretservice
            export PATH="$PWD/.docker-ci/bin:$PATH"

            mkdir -p .docker-ci/config
            printf '{"auths":{},"credHelpers":{}}' > .docker-ci/config/config.json
            export DOCKER_CONFIG="$PWD/.docker-ci/config"

            echo "Stopping any previous local server..."
            pkill -f "artisan serve" || true
            sleep 1

            echo "Deploying locally with Docker Compose..."
            docker compose down || true
            docker compose up -d --build

            echo "Local deployment complete."
            echo "UI available at: http://localhost:8000"
            rm -rf .docker-ci
        '''
    }
}
```

Déploie l'application localement avec Docker Compose, en contournant le problème de helper d'identifiants Docker, puis affiche le lien de l'interface.

---

## Actions post-build

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

- `success` : affiché quand toutes les étapes réussissent.
- `failure` : affiché quand une étape échoue.
- `cleanup` : archive les fichiers de couverture/rapport et supprime les fichiers temporaires.

---

## Secrets utilisés

| Identifiant de credential Jenkins | Usage |
| --- | --- |
| `sonar` | Jeton d'authentification SonarQube |


Les secrets sont stockés uniquement dans Jenkins et ne sont jamais commités dans Git.

---

## Équipe BioDev — 5TWIN4

- Mohamed Amine Chniti
- Mohamed Amine Souibgui
- Wiem Karaja
- Hiba Khadhraoui
- Montaha Trabelsi
