pipeline {
  agent none
    environment {
        SONAR_HOST_URL = credentials('sonar-url')
        SONAR_LOGIN = credentials('sonar-token')
    }
    stages {
            stage('Build') {
                agent {
                    kubernetes {
                        inheritFrom "composer sonar"
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
                    success {
                        stash name: env.BUILD_TAG, includes: "${workspace}/**"
                    }
                }
            }
        stage('Pre-Deployment') {
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
                          "text": "Approval required for: *<${env.BUILD_URL}|${env.BUILD_TAG}>*"
                        ]
                      ],
                      [
                        "type": "actions",
                        "elements": [
                          [
                            "type": "button",
                            "text": [
                              "type": "plain_text",
                              "emoji": true,
                              "text": "Approve"
                            ],
                            "style": "primary",
                            "action_id": "approve:${env.BUILD_URL}",
                            "value": "approve:${env.BUILD_URL}",
                          ],
                          [
                            "type": "button",
                            "text": [
                              "type": "plain_text",
                              "emoji": true,
                              "text": "Abort"
                            ],
                            "style": "danger",
                            "action_id": "reject:${env.BUILD_URL}",
                            "value": "reject:${env.BUILD_URL}",
                          ]
                        ]
                      ]
                    ]
                    slackSend(channel: "#general", blocks: blocks, failOnError: true)
                }
                timeout(time: 5, unit: 'MINUTES') {
                    input message: 'Proceed to Deploy?', ok: 'Deploy'
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
                agent {
                    kubernetes {
                        inheritFrom "deployment"
                    }
                }
                steps {
                    unstash "${BUILD_TAG}"
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
                    container('kubectl') {
                        sh "kubectl wait --timeout=60s --for=condition=ready pod -l app=symfony"
                    }
                }
            }
        }
}
