pipeline {
    agent any

    stages {
        stage('Build') {
            steps {
                echo 'Building the project...'
                checkout scm
            }
        }

        stage('Test') {
            steps {
                echo 'Running tests...'
            }
        }

        stage('SonarQube Analysis') {
            def scannerHome = tool 'OWS';
            withSonarQubeEnv() {
            sh "${scannerHome}/bin/sonar-scanner"
            }
        }

        stage('Deploy') {
            steps {
                echo 'Deploying...'
            }
        }
    }

    post {
        success {
            echo 'Build successful! Deploying...'
        }
        failure {
            echo 'Build failed! Notify the team...'
        }
    }
}
