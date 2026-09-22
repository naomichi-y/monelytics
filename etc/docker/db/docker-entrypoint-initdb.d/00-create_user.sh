#!/bin/bash
# 初回起動時にアプリ用のユーザとスキーマを作る。
#
# 値をここへ書かない。以前は同じ内容を .sql に置き、webapp のパスワードを
# IDENTIFIED BY へ直書きしていた。git 管理下のファイルだったため、2016 年から
# 平文が履歴に残り、本番で使っているパスワードがそのまま公開リポジトリに
# 載っていた。認証情報は git 管理外の db.env だけが持つ。
#
# .sql ではなく .sh にしてあるのは、MariaDB の entrypoint が .sql を変数展開
# せずにそのまま流すため。環境変数を読むにはシェルを経由する必要がある。
set -eu

: "${WEBAPP_PASSWORD:?etc/docker/db/db.env に WEBAPP_PASSWORD を設定すること}"

mariadb --protocol=socket -uroot -p"${MARIADB_ROOT_PASSWORD}" <<SQL
CREATE USER IF NOT EXISTS 'webapp'@'%' IDENTIFIED BY '${WEBAPP_PASSWORD}';

CREATE DATABASE IF NOT EXISTS monelytics DEFAULT CHARACTER SET utf8mb4;
CREATE DATABASE IF NOT EXISTS monelytics_testing DEFAULT CHARACTER SET utf8mb4;
CREATE DATABASE IF NOT EXISTS monelytics_e2e DEFAULT CHARACTER SET utf8mb4;

GRANT ALL ON monelytics.* TO 'webapp'@'%';
GRANT ALL ON monelytics_testing.* TO 'webapp'@'%';
GRANT ALL ON monelytics_e2e.* TO 'webapp'@'%';
SQL
