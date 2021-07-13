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
        SONAR_HOST_URL = credentials('sonar-url')
        SONAR_LOGIN = credentials('sonar-token')
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
                        sh('sonar-scanner -Dsonar.login=$SONAR_LOGIN')
                    }
                }
            }
        }
        stage('Deployment') {
            when {
                anyOf {
                    branch "main"
                    buildingTag()
                }
            }
            steps {
                script {
                    def blocks = [
                                    [
                                            "type": "section",
                                            "text": [
                                                    "type": "mrkdwn",
                                                    "text": "Approval required for:\n*<fakeLink.toEmployeeProfile.com|Fred Enriquez - New device request>*"
                                            ]
                                    ],
                                    [
                                            "type"    : "actions",
                                            "elements": [
                                                    [
                                                            "type" : "button",
                                                            "text" : [
                                                                    "type" : "plain_text",
                                                                    "emoji": true,
                                                                    "text" : "Approve"
                                                            ],
                                                            "style": "primary",
                                                            "value": "approve"
                                                    ],
                                                    [
                                                            "type" : "button",
                                                            "text" : [
                                                                    "type" : "plain_text",
                                                                    "emoji": true,
                                                                    "text" : "Abort"
                                                            ],
                                                            "style": "danger",
                                                            "value": "deny"
                                                    ]
                                            ]
                                    ]
                    ]
                    slackSend(channel: "#general", blocks: blocks)
                }
                input message: 'Proceed to Deploy?', ok: 'Deploy'
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
                container('helm') {
                    sh "helm upgrade --install symfony ./charts --set image.tag=${GIT_COMMIT}"
                }
            }
        }
    }
}
