#!/bin/bash
#
# db コンテナが接続を受け付けるまで待つ。
#
# up の直後はまだ初期化中で、docker-entrypoint-initdb.d のスキーマ作成が
# 終わるまで数秒から十数秒かかる。待たずに artisan を叩くと接続に失敗する。
#
# webapp で monelytics_e2e に繋げた時点で、利用者・スキーマ・権限を作る
# 00-create_user.sh が流れ終わっている (最後の一文が権限付与のため)。
set -euo pipefail

# 認証情報は git 管理外の db.env だけが持つ。以前は初期化 SQL の IDENTIFIED BY
# から読んでいたが、その SQL は本番のパスワードを git の履歴へ残す原因になり、
# 環境変数を読む .sh に置き換わって消えた。参照元を直し忘れたため、set -e が
# 読めないファイルへの sed で止まり、テストまで届かなくなっていた。
password="$(sed -n 's/^WEBAPP_PASSWORD=//p' etc/docker/db/db.env)"

: "${password:?etc/docker/db/db.env から WEBAPP_PASSWORD を読めなかった}"

for _ in $(seq 1 60); do
  if docker compose exec -T db mariadb -uwebapp -p"$password" -e 'select 1' monelytics_e2e >/dev/null 2>&1; then
    exit 0
  fi

  sleep 2
done

echo 'データベースが起動しなかった' >&2
docker compose logs --no-color --tail 100 db >&2

exit 1
