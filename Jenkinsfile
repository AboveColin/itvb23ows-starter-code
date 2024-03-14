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
            script { scannerHome = tool 'OWS' }
            withSonarQubeEnv('OWS') {
            sh '${scannerHome}/bin/sonar-scanner -D"sonar.projectKey=OWS" -D"sonar.sources=." -D"sonar.host.url=http://172.18.0.3:9000" -D"sonar.token=squ_37281e5b1fec23694973648bdff8718b5056ea68"'
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
