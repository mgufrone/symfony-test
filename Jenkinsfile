pipeline {
  agent none
    environment {
        SONAR_HOST_URL = credentials('sonar-url')
        SONAR_LOGIN = credentials('sonar-token')
    }
  options { skipDefaultCheckout() }
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
                  checkout scm
                  script {
                    env.COMPOSER = env.GIT_BRANCH=="main" ? "composer.production.json" : "composer.json"
                  }
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
                      sh 'ls -la'
                      stash name: env.BUILD_TAG, includes: "**"
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
                                sh "cp composer.production.json composer.json"
                              sh "ls -la"
                              sh "/kaniko/executor --context . --dockerfile ./build.Dockerfile --destination ghcr.io/mgufrone/symfony-test:${env.GIT_BRANCH} --destination ghcr.io/mgufrone/symfony-test:${env.GIT_COMMIT}"
                            }
                        }
                    }
                    container('helm') {
                        sh "helm upgrade --install symfony ./charts --set image.tag=${env.GIT_COMMIT}"
                    }
                }
            }
        }
}
