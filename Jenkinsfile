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
            withSonarQubeEnv('SonarQube Scanner') {
            sh "${scannerHome}/bin/sonar-scanner -Dsonar.projectKey=squ_40a6b9a557f6734c20f094281b3200aa6845c469"
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
