# インストール手順

## 1. DockerおよびDockerComposeのインストール

### サーバー上で以下のコマンドを実行し、Dockerをインストール

sudo yum install -y docker  
sudo systemctl start docker  
sudo systemctl enable docker  
 
### デフォルトのユーザー(ec2-user)でもsudoをつけずにdockerコマンドを実行できるように、dockerグループに追加

sudo usermod -a G docker ec2-user  

### Docker Composeのインストール

sudo mkdir -p /usr/local/lib/docker/cli-plugins/  
sudo curl -SL https://github.com/docker/compose/releases/download/v2.36.0/docker-compose-linux-x86_64 -o /usr/local/lib/docker/cli-plugins/docker-compose  
sudo chmod +x /usr/local/lib/docker/cli-plugins/docker-compose  
