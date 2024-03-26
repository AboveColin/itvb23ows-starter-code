FROM jenkins/jenkins:latest
USER root
RUN apt-get update && apt-get install -y lsb-release && apt-get clean
RUN curl -fsSLo /usr/share/keyrings/docker-archive-keyring.asc \
  https://download.docker.com/linux/debian/gpg
RUN echo "deb [arch=$(dpkg --print-architecture) \
  signed-by=/usr/share/keyrings/docker-archive-keyring.asc] \
  https://download.docker.com/linux/debian \
  $(lsb_release -cs) stable" > /etc/apt/sources.list.d/docker.list

RUN apt-get update && apt-get install -y docker-ce-cli php php-mbstring php-xml php-bcmath php-mysqli composer nodejs npm && apt-get clean

USER jenkins
RUN jenkins-plugin-cli --plugins "blueocean docker-workflow"