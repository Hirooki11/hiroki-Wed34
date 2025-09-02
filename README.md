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

## 2. ソースコードの配置

任意のディレクトリ上で`git clone git@github.com:Hirooki11/hiroki-Wed34.git`と入力する  

## 3. ビルド＆起動

    docker compose build  
    docker compose up  

上記のコマンドで起動できたら、ウェブブラウザでEC2インスタンスのホスト名またはIPアドレス(SSHでログインするときと同じもの)に接続する  

ブラウザのURLに`http://IPアドレス/bbs.php`と入力して開いてみる  
掲示板が表示されたら成功

## 4. テーブルの作成
