pipeline {
    agent any

    environment {
        MYSQL_HOST = 'db'
        MYSQL_USER = 'root'
        MYSQL_PASSWORD = '123456'
        MYSQL_DB = 'hive'
    }


    stages {
        stage('Checkout') {
            steps {
                echo 'Checking out code...'
                // Get the latest code from the source control
                checkout scm
            }
        }
        stage('Build') {
            steps {
                echo 'Building the Docker image...'
                // Build the Docker image
                sh 'docker-compose build'
            }
        }
        stage('Test') {
            steps {
                echo 'Running tests...'
                // Run tests here
                sh 'docker-compose run app vendor/bin/phpunit'
            }
        }
        stage('Deploy') {
            steps {
                echo 'Deploying the application...'
                // Deploy your application
                sh 'docker-compose up -d'
            }
        }
    }
}