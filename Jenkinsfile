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

        stage('SonarQube') {
          steps {
            script { scannerHome = tool 'SonarQube Scanner' }
            withSonarQubeEnv('SonarQube') {
              sh "${scannerHome}/bin/sonar-scanner
                -Dsonar.projectKey=sqp_a2b65a614ece26e2f2d8d8691a5ab072c584313b"
            }
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
