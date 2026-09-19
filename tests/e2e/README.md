# E2E テスト

画面越しに動かして、サーバが返す HTML と JS の継ぎ目が壊れていないかを見る。
PHP 側のロジックは `tests/` の PHPUnit が押さえているので、ここでは重ねない。

## 実行

本番用のコンテナとは別に、E2E 専用のアプリ一式を立てる。データベースも
Redis の DB 番号も分かれているため、本番のデータには触れない。

```
docker compose --profile e2e up -d
docker compose exec php-e2e php artisan migrate:fresh --seed --seeder='Seeds\E2eSeeder' --force
docker compose exec playwright npm install
docker compose exec playwright npx playwright test
```

失敗したときの記録:

```
docker compose exec playwright npx playwright show-report tests/e2e/report
```

## 構成

| | |
|---|---|
| `web-e2e` / `php-e2e` | ポート 8081。`monelytics_e2e` スキーマを見る |
| `playwright` | ブラウザ同梱の公式イメージ。ホストに node を入れずに動かす |
| `Seeds\E2eSeeder` | 当月・前月・前々月と過去 2 年分のデータを作る |

流量制限は `RATE_LIMIT_*` を compose で緩めてある。本番と同じ値だと、
繰り返しログインするテスト自身が 429 で弾かれるため。

## セレクタの方針

Bootstrap のクラス名 (`.btn`, `.col-md-4`, `.well`) を目印にしない。3 から 5 へ
上げたときにテストが一斉に落ち、何が壊れたのか分からなくなる。

使うのはこの順:

1. `getByLabel()` / `getByRole()` — 見た目を変えても残る
2. 画面に出る文言
3. アプリ自身が付けている属性 (`data-id`, `name`, `id`)

## 引っかかりやすいところ

- **月別集計の集計表は 4 つに複製されている。** ヘッダ固定の
  `jquery.tablefix` がテーブルを丸ごと複製して重ねるため、素直に探すと
  固定ヘッダの下に隠れた複製を掴み、クリックがヘッダに吸われる。
  本体は `helpers.js` の `reportTable()` で取る。
- **登録・編集・削除のテストは同じデータベースを共有する。** シードの行を
  書き換えず、自分で作った行だけを対象にすること。`marker()` で一意の
  目印を付けて識別する。
- **金額の小数はブラウザが送信を止める。** `<input type="number">` の
  既定の step が 1 のため。画面から到達できるのは桁あふれの側だけ。
