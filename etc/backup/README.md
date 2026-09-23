# バックアップ

MariaDB を日次で論理ダンプし、gzip してローカルと OCI Object Storage の
両方に保存する。30 日より古い世代は双方から自動で削除される。

- `backup.sh` … バックアップ本体。cron から日次で呼ばれる。
- `backup.env` … 設定。git 管理外。`backup.env.example` をコピーして作る。

| | |
|---|---|
| ローカル保存先 | `/home/ubuntu/backups/monelytics/` |
| バケット | `monelytics-backup` (名前空間 `nrrd1cjxbmgv`, ap-tokyo-1, プライベート) |
| 実行時刻 | 毎日 19:00 UTC = **04:00 JST**（サーバのタイムゾーンは UTC） |
| ログ | `/home/ubuntu/backups/monelytics/backup.log` |
| 保持 | 30 日 |

## 認証情報

2 種類あり、どちらもこの repo には入らない。

**DB のパスワード** … 持たない。`docker exec` でコンテナ内の
`$MARIADB_ROOT_PASSWORD` を参照するため、ホスト側にも複製されない。

**Object Storage** … OCI CLI の API キー。`~/.oci/config` (600) と
秘密鍵 `~/.oci/oci_api_key.pem` (600)。公開鍵はコンソールの
「ユーザー設定 → APIキー」に登録済み。

```ini
[DEFAULT]
user=ocid1.user.oc1..aaaa...
fingerprint=23:62:7c:4c:fe:1c:b8:88:91:2d:39:65:ec:ad:ed:89
key_file=/home/ubuntu/.oci/oci_api_key.pem
tenancy=ocid1.tenancy.oc1..aaaa...
region=ap-tokyo-1
```

## 動作確認

```sh
./etc/backup/backup.sh                                          # 手で一度流す
oci os object list --bucket-name monelytics-backup --all        # 中身を見る
tail -20 /home/ubuntu/backups/monelytics/backup.log             # ログを見る
```

## 復元

### ダンプを取得する

ローカルに残っていればそれを使う。消えていれば Object Storage から引く。

```sh
oci os object list --bucket-name monelytics-backup --all
oci os object get --bucket-name monelytics-backup \
  --name mysql/monelytics-YYYYMMDD-HHMMSS.sql.gz \
  --file monelytics-YYYYMMDD-HHMMSS.sql.gz
```

### 検証してから戻す

いきなり本番へ流さず、別スキーマに入れて中身を確かめる。

```sh
gzip -dc monelytics-YYYYMMDD-HHMMSS.sql.gz | docker exec -i monelytics_db sh -c \
  'mariadb -u root -p"$MARIADB_ROOT_PASSWORD" -e "CREATE DATABASE restore_probe;" \
   && mariadb -u root -p"$MARIADB_ROOT_PASSWORD" restore_probe'

docker exec monelytics_db sh -c \
  'mariadb -u root -p"$MARIADB_ROOT_PASSWORD" -e "SELECT COUNT(*) FROM restore_probe.users;"'
```

期待どおりなら本番へ適用する。**先に現状のダンプを取っておくこと。**

```sh
./etc/backup/backup.sh   # 現状を退避

gzip -dc monelytics-YYYYMMDD-HHMMSS.sql.gz | docker exec -i monelytics_db sh -c \
  'mariadb -u root -p"$MARIADB_ROOT_PASSWORD" monelytics'

docker exec monelytics_db sh -c \
  'mariadb -u root -p"$MARIADB_ROOT_PASSWORD" -e "DROP DATABASE restore_probe;"'
```

セッションは Redis にあるため、復元後はログアウトさせるのが安全。

```sh
docker exec monelytics_redis redis-cli FLUSHDB
```

## 任意: API キーをやめる

インスタンスプリンシパルに切り替えると、サーバ上から恒久的な認証情報が消える。
動的グループとポリシーの作成には IAM の権限が要るため、コンソールか
管理者の手元から実行する。

```sh
oci iam dynamic-group create \
  --name monelytics-backup-dg \
  --description "monelytics.me のバックアップ用" \
  --matching-rule "ALL {instance.id = 'ocid1.instance.oc1.ap-tokyo-1.anxhiljrn5v5beycidst3grrl3vdlvmghcddrmsvmz3pajotonqsm2oagtxa'}"

oci iam policy create \
  --compartment-id ocid1.tenancy.oc1..aaaaaaaafhy6ywmkygzauykmrur2vrevvxm5by3p3lopw2riarhxicimvb3q \
  --name monelytics-backup-policy \
  --description "バックアップ用インスタンスにバケットの読み書きを許す" \
  --statements '["Allow dynamic-group monelytics-backup-dg to read buckets in tenancy where target.bucket.name = '"'"'monelytics-backup'"'"'",
                 "Allow dynamic-group monelytics-backup-dg to manage objects in tenancy where target.bucket.name = '"'"'monelytics-backup'"'"'"]'
```

反映後、`backup.sh` の `oci` 呼び出しに `--auth instance_principal` を加え、
疎通を確認してから `~/.oci` とコンソール上の API キーを削除する。

## 費用

Always Free の枠に収まる。8.8 MB/回 × 30 日 = 約 264 MB で、
Object Storage の無料枠 20 GB に対して 1.3 % ほど。
API リクエストも月 100 回程度で、無料枠 50,000 回/月に対して誤差の範囲。
