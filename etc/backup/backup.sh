#!/usr/bin/env bash
#
# MariaDB の日次バックアップ。論理ダンプを gzip し、ローカルと
# OCI Object Storage の両方に保存する。
# RETENTION_DAYS より古い世代は両方から削除する。
#
# 設定は同ディレクトリの backup.env に置く (backup.env.example を参照)。
# DB のパスワードはコンテナの環境変数をそのまま使うため、ホスト側には持たない。
# Object Storage の認証は ~/.oci/config の API キーによる。
#
# cron からの実行例:
#   0 19 * * * /home/ubuntu/app/monelytics.me/etc/backup/backup.sh
#
set -euo pipefail

readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly CONFIG_FILE="${SCRIPT_DIR}/backup.env"

log() { printf '%s [%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$1" "${*:2}"; }
die() { log ERROR "${*}"; exit 1; }

[[ -f "$CONFIG_FILE" ]] || die "設定ファイルがない: ${CONFIG_FILE} (backup.env.example をコピーする)"
# shellcheck source=/dev/null
source "$CONFIG_FILE"

: "${BACKUP_DIR:?backup.env に BACKUP_DIR がない}"
: "${RETENTION_DAYS:?backup.env に RETENTION_DAYS がない}"
: "${DB_CONTAINER:?backup.env に DB_CONTAINER がない}"
: "${DB_NAME:?backup.env に DB_NAME がない}"
OS_BUCKET="${OS_BUCKET:-}"
OS_PREFIX="${OS_PREFIX:-mysql}"
OCI_BIN="${OCI_BIN:-/home/ubuntu/bin/oci}"
# cron には HOME 以外ほとんど渡らないため、設定ファイルの場所は明示する。
export OCI_CLI_CONFIG_FILE="${OCI_CLI_CONFIG_FILE:-/home/ubuntu/.oci/config}"
export SUPPRESS_LABEL_WARNING=True

mkdir -p "$BACKUP_DIR"

# 多重起動の防止。前回の実行が残っていれば黙って降りる。
exec 9>"${BACKUP_DIR}/.lock"
flock -n 9 || die "前回のバックアップがまだ動いている"

readonly STAMP="$(date '+%Y%m%d-%H%M%S')"
readonly FILENAME="${DB_NAME}-${STAMP}.sql.gz"
readonly DEST="${BACKUP_DIR}/${FILENAME}"
readonly TMP="${BACKUP_DIR}/.${FILENAME}.part"
trap 'rm -f "$TMP"' EXIT

log INFO "バックアップ開始: ${FILENAME}"

# --- ダンプ ------------------------------------------------------------
# --single-transaction は InnoDB をロックせずに一貫したスナップショットを取る。
docker exec -e DUMP_DB="$DB_NAME" "$DB_CONTAINER" \
  sh -c 'exec mariadb-dump -u root -p"$MARIADB_ROOT_PASSWORD" \
           --single-transaction --quick --routines --events \
           --default-character-set=utf8mb4 "$DUMP_DB"' \
  | gzip -9 > "$TMP"

# --- 検証 --------------------------------------------------------------
# gzip として壊れていないこと、かつダンプが最後まで書かれていることを確かめる。
# mariadb-dump は正常終了時に末尾へ "Dump completed" を書くので、
# 途中で切れたダンプをここで弾ける。
gzip -t "$TMP" || die "gzip が壊れている"
gzip -dc "$TMP" | tail -c 200 | grep -q 'Dump completed' \
  || die "ダンプが途中で終わっている (末尾マーカーがない)"

mv "$TMP" "$DEST"
trap - EXIT
log INFO "ローカル保存: ${DEST} ($(du -h "$DEST" | cut -f1))"

# --- アップロード ------------------------------------------------------
if [[ -z "$OS_BUCKET" ]]; then
  log WARN "OS_BUCKET が未設定のためアップロードを省略した"
else
  "$OCI_BIN" os object put --bucket-name "$OS_BUCKET" \
    --name "${OS_PREFIX}/${FILENAME}" --file "$DEST" --force --no-multipart \
    >/dev/null || die "アップロードに失敗した"
  log INFO "アップロード: ${OS_BUCKET}/${OS_PREFIX}/${FILENAME}"
fi

# --- 世代削除 ----------------------------------------------------------
# ファイル名に埋めた日付で判定する。YYYYMMDD は辞書順と時系列が一致するため
# 文字列比較でよい。タイムスタンプ依存より意図が明確で、コピーしても壊れない。
readonly CUTOFF="$(date -d "${RETENTION_DAYS} days ago" '+%Y%m%d')"
log INFO "${CUTOFF} より前の世代を削除する (保持 ${RETENTION_DAYS} 日)"

# 名前から YYYYMMDD を取り出す。取れなければ空を返し、判定では消さない側に倒す。
date_of() {
  local name="${1##*/}"
  name="${name#"${DB_NAME}-"}"
  printf '%s' "${name%%-*}"
}

removed_local=0
for path in "${BACKUP_DIR}/${DB_NAME}-"*.sql.gz; do
  [[ -e "$path" ]] || continue
  d="$(date_of "$path")"
  if [[ -n "$d" && "$d" < "$CUTOFF" ]]; then
    rm -f "$path"
    removed_local=$((removed_local + 1))
  fi
done
log INFO "ローカル削除: ${removed_local} 件"

if [[ -n "$OS_BUCKET" ]]; then
  removed_remote=0
  keys="$("$OCI_BIN" os object list --bucket-name "$OS_BUCKET" \
            --prefix "${OS_PREFIX}/${DB_NAME}-" --all --output json 2>/dev/null \
          | jq -r '.data[]?.name // empty')"
  while IFS= read -r key; do
    [[ -n "$key" ]] || continue
    d="$(date_of "$key")"
    if [[ -n "$d" && "$d" < "$CUTOFF" ]]; then
      "$OCI_BIN" os object delete --bucket-name "$OS_BUCKET" --name "$key" --force \
        >/dev/null && removed_remote=$((removed_remote + 1))
    fi
  done <<< "$keys"
  log INFO "リモート削除: ${removed_remote} 件"
fi

log INFO "バックアップ完了"
