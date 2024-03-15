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
                sh 'php -r "echo \'Hello, world!\';"'
                sh 'php -r "echo \'Hello, world!\';" > output.txt'
                archiveArtifacts artifacts: 'output.txt'
            }
        }

        stage('SonarQube Analysis') {
            steps {
                script { scannerHome = tool 'OWS' }
                withSonarQubeEnv('SonarQube') {
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