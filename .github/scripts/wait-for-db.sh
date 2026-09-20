#!/bin/bash
#
# db コンテナが接続を受け付けるまで待つ。
#
# up の直後はまだ初期化中で、docker-entrypoint-initdb.d のスキーマ作成が
# 終わるまで数秒から十数秒かかる。待たずに artisan を叩くと接続に失敗する。
#
# webapp で monelytics_e2e に繋げた時点で、利用者・スキーマ・権限を作る SQL が
# 流れ終わっている (この SQL の最後の一文が権限付与のため)。
set -euo pipefail

password="$(sed -n "s/.*IDENTIFIED BY '\([^']*\)'.*/\1/p" etc/docker/db/docker-entrypoint-initdb.d/00-create_user.sql)"

for _ in $(seq 1 60); do
  if docker compose exec -T db mariadb -uwebapp -p"$password" -e 'select 1' monelytics_e2e >/dev/null 2>&1; then
    exit 0
  fi

  sleep 2
done

echo 'データベースが起動しなかった' >&2
docker compose logs --no-color --tail 100 db >&2

exit 1
