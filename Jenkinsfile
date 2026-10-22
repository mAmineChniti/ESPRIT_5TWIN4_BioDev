pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
        buildDiscarder(logRotator(numToKeepStr: '20'))
    }

    environment {
        SONAR_HOST_URL = 'http://localhost:9000'
        JAVA_HOME = '/usr/lib/jvm/java-21-openjdk'
        PATH = "${JAVA_HOME}/bin:${env.PATH}"
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Install PHP dependencies') {
            steps {
                sh 'composer install --no-interaction --prefer-dist'
            }
        }

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

        stage('Lint (Pint)') {
            steps {
                sh './vendor/bin/pint --test'
            }
        }

        stage('Syntax check') {
            steps {
                sh 'find app database routes tests -name "*.php" -print0 | xargs -0 -n1 -P4 php -l > /dev/null'
            }
        }

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

        stage('Migrate & seed') {
            steps {
                sh '''
                    set -e
                    php artisan migrate --force
                    php artisan db:seed --force
                '''
            }
        }

        stage('Tests & coverage') {
            steps {
                sh 'php -d zend_extension=xdebug.so -d xdebug.mode=coverage vendor/bin/phpunit --coverage-clover coverage.xml'
            }
        }

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
    }

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
}
