node {
  docker.withRegistry('https://registry.hub.docker.com', 'docker-registry-login') {
    stage('Checkout') {
      checkout scm
    }

    def container = docker.image("scomm/php5.6:latest")
    container.pull()
    container.inside("-v ${env.WORKSPACE}:/var/www/html -u 0:0") {
      withEnv(['SYMFONY_ENV=prod', 'APP_ENV=prod']) {
        stage('Composer') {
          sh "composer config -g github-oauth.github.com 0c32f31599c34beae4b8da4c06791d5cb6aad342"
          sh "composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader"
        }
        stage ('Cache') {
          sh 'bin/console cache:clear --env=prod'
        }
      }
    }

    stage("Copy Artifacts") {
      if (!env.BRANCH_NAME.contains('PR-')) {
        step([$class: 'ArtifactArchiver', artifacts: '**'])
      }
  }
}
