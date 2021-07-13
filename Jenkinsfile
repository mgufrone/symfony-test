import groovy.json.JsonOutput

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
    environment {
        SONAR_HOST_URL = "http://sonarqube-sonarqube.sonarqube:9000/"
        SONAR_LOGIN = "d1c6a9cdbe3511c2cf865081b1e0d653f5998ce5"
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
                        sh "SONAR_LOGIN=$SONAR_LOGIN sonar-scanner -X"
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
