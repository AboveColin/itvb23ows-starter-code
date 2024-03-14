node {
    stage('SCM') {
        checkout scm
    }

    stage('tests') {
        agent {
        docker {
            image 'php:8.3.1'
            args '-u root:sudo'
        }

        }
        steps {
        echo 'Running PHP 7.4 tests...'
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
        def scannerHome = tool 'OWS';
        withSonarQubeEnv() {
        sh "${scannerHome}/bin/sonar-scanner"
        }
    }
}