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
            agent {
                docker {
                    image 'php:7.4'
                    args '-u root:sudo'
                }
            }
            steps {
                echo 'Running phpunit tests...'
                sh 'php -v'
                echo 'Installing Composer'
                sh 'curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer'
                echo 'Installing project composer dependencies...'
                sh 'cd $WORKSPACE && composer install --no-progress'
                echo 'Running PHPUnit tests...'
                sh 'php $WORKSPACE/vendor/bin/phpunit --coverage-html $WORKSPACE/report/clover --coverage-clover $WORKSPACE/report/clover.xml --log-junit $WORKSPACE/report/junit.xml'
                sh 'chmod -R a+w $PWD && chmod -R a+w $WORKSPACE'
                junit 'report/*.xml'
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