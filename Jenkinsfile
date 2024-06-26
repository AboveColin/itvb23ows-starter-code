pipeline {
    agent any
    stages {

        stage('SCM') {
            steps {
                echo 'Checking out code...'
                checkout scm
            }
        }

        stage('phpunit tests') {
            steps {
                sh 'php -v'
                sh 'composer --version'
                sh "composer install --working-dir ${env.WORKSPACE}/App"
                sh "chmod +x ${env.WORKSPACE}/App/vendor/bin/phpunit"
                sh "${env.WORKSPACE}/App/vendor/bin/phpunit --version"
                sh "${env.WORKSPACE}/App/vendor/bin/phpunit --configuration ${env.WORKSPACE}/App/phpunit.xml"

            }
        }

        stage('SonarQube Analysis') {
            steps {
                script { scannerHome = tool 'OWS' }
                withSonarQubeEnv('OWS') {
                    sh "${scannerHome}/bin/sonar-scanner"
                }
                echo 'SonarQube analysis completed'
            }
        }

        stage('Deploy') {
            steps {
                echo 'Deploying...'
            }
        }
    }   
}