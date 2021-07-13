pipeline {
    agent {
        kubernetes {
            inheritFrom "composer deployment sonar"
            yaml '''
            spec:
              volumes:
                - name: env-file
                  configMap:
                    name: symfony-env-file
              containers: 
              - name: composer
                volumeMounts:
                  - mountPath: /app/.env
                    subPath: .env
                    name: env-file
'''
        }
    }
    stages {
        stage('Build') {
            steps {
                container('composer') {
                    sh "cp /app/.env .env"
                    sh "composer install"
                    sh "./vendor/bin/phpunit"
                }
            }
            post {
                always {
                    container('sonar') {
                        sh "printenv"
                        script {
                            println new JsonBuilder(env).toPrettyString()
                        }
                        sh "echo \"SONAR_HOST_URL=$SONAR_HOST_URL BRANCH_NAME=$BRANCH_NAME SONAR_LOGIN=$SONAR_LOGIN\""
                        sh "sonar-scanner"
                    }
                }
            }
        }
        stage('Build Image') {
            steps {
                container('kaniko') {
                    script {
                        withCredentials([usernamePassword(credentialsId: "github", usernameVariable: 'username', passwordVariable: 'password')]) {
                            def data = ["auths": ["ghcr.io": ["username": username, "password": password]]]
                            writeJSON file: "docker-config.json", json: data
                            sh "cp docker-config.json /kaniko/.docker/config.json"
                            sh "/kaniko/executor --context . --dockerfile ./build.Dockerfile --destination ghcr.io/mgufrone/symfony-test:${GIT_BRANCH} --destination ghcr.io/mgufrone/symfony-test:${GIT_COMMIT}"
                        }
                    }
                }
            }
        }
        stage('Deployment') {
            steps {
                input message: 'Proceed to Deploy?', ok: 'Deploy', submitter: 'gufy,admin'
                container('helm') {
                    sh "helm upgrade --install symfony ./charts --set image.tag=${GIT_COMMIT}"
                }
            }
        }
    }
}
