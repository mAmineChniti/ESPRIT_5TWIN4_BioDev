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
                    npm run build
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

        stage('Deploy to Render') {
            steps {
                withCredentials([string(credentialsId: 'render-deploy-hook', variable: 'RENDER_DEPLOY_HOOK')]) {
                    sh '''
                        set -e
                        echo "Triggering Render deployment..."
                        curl -fsS -X POST "$RENDER_DEPLOY_HOOK"
                        echo
                        echo "Render deployment triggered successfully."
                    '''
                }
            }
        }
    }

    post {
        success {
            echo 'CI/CD pipeline completed successfully.'
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
