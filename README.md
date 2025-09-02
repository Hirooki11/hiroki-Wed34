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

## 2. Git インストール

    sudo yum install git -y

初期設定

    git config --global init.defaultBranch main

  名前とメールアドレスを設定する
  メールアドレスはGitHubに登録しているものと同一のものにする

## 3. ソースコードの配置

    git clone git@github.com:Hirooki11/hiroki-Wed34.git

## 4. ビルド＆起動

    docker compose build
    docker compose up

上記のコマンドで起動できたら、ウェブブラウザでEC2インスタンスのホスト名またはIPアドレス(SSHでログインするときと同じもの)に接続する  

ブラウザのURLに`http://IPアドレス/bbs.php`と入力して開いてみる  
掲示板が表示されたら成功

## 5. テーブルの作成

作成したDockerコンテナ内のMySQLサーバーにmysqlコマンドで接続する

    docker compose exec mysql mysql example_db

掲示板の投稿を保存するテーブルを作成する  

電子掲示板(= BBS)の投稿(= entry)を保存するため、`bbs_entries'というテーブル名にします

    CREATE TABLE `bbs_entries` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `body` TEXT NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ); 
