pipeline {
    agent {
        kubernetes {
            inheritFrom "composer deployment"
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
    parameters {
        string(name: "BRANCH", defaultValue: "main", description: "Branch to Build")
    }
    stages {
        stage('Setup') {
            steps {
                git credentialsId: "github", url: "https://github.com/mgufrone/symfony-test", branch: params.BRANCH
            }
        }
        stage('Build') {
            steps {
                container('composer') {
                    sh "cp /app/.env .env"
                    sh "composer install"
                }
                container('kaniko') {
                    script {
                        withCredentials([usernamePassword(credentialsId: "github", usernameVariable: 'username', passwordVariable: 'password')]) {
                            def data = ["auths": ["ghcr.io": ["username": username, "password": password]]]
                            writeJSON file: "docker-config.json", json: data
                            sh "cp docker-config.json /kaniko/.docker/config.json"
                            sh "/kaniko/executor --context . --dockerfile ./build.Dockerfile --destination ghcr.io/mgufrone/symfony-test:${params.BRANCH}"
                        }
                    }
                }
            }
        }
        stage('Deployment') {
            steps {
                container('helm') {
                    sh "helm upgrade --install symfony ./charts"
                }
            }
        }
    }
}
