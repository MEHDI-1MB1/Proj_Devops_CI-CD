pipeline {
    agent any
    
    stages {
        stage('Checkout') {
            steps {
                cleanWs()
                git branch: 'main',
                    url: 'https://github.com/MEHDI-1MB1/Proj_Devops_CI-CD.git'
            }
        }
        
        stage('Install Dependencies') {
            steps {
                sh 'composer install --no-progress --no-interaction'
            }
        }
        
        stage('PHP Lint') {
            steps {
                sh 'find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \; | grep -v "No syntax errors" || true'
            }
        }
        
        stage('PHPUnit Tests') {
            steps {
                sh './vendor/bin/phpunit --testdox'
            }
        }
        
        stage('Build Docker') {
            steps {
                sh 'docker build -t codes-cite:latest .'
            }
        }
        
        stage('Deploy') {
            steps {
                sh '''
                    cd /home/m3hdi/proj_devops/codes_cite
                    docker-compose down || true
                    docker-compose up -d
                '''
            }
        }
    }
    
    post {
        failure {
            echo '❌ Pipeline échoué !'
        }
        success {
            echo '✅ Pipeline réussi ! Site: http://192.168.40.134'
        }
    }
}
