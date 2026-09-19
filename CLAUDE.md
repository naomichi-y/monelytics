# monelytics

家計簿の Web アプリ。PHP 8.5 / Laravel 13 / MariaDB 12.3 / Redis 8 を Docker Compose で動かす。
環境構築は [README.md](README.md) を参照。

```
docker compose exec -u webapp php php artisan test     # テスト
docker compose exec -u webapp php php artisan tinker   # 対話実行
```

## レビュー観点

過去に実際に不具合を出した箇所。コードレビュー時はここを必ず見ること。

### 同じ不具合が他にもないか

**最優先。** 不具合を 1 件直したら、同じパターンをリポジトリ全体で検索して一緒に潰す。
1 箇所だけ直して報告した結果、同種の不具合が別の画面に残っていたことが複数回ある。

- `&amp;` の二重エスケープ → `linkWithQueryString` を直したが `BaseCondition::buildQueryString` と
  `calendar.blade.php` の `link_to` に残っていた
- 表から列を削除 → 月別集計だけ数え直し、日別一覧と変動費登録の colgroup が壊れたまま

### リンク・URL

- `http_build_query` の区切りは `&`。HTML への逃がしは `Html::link` / `link_to` が行うため、
  `&amp;` を渡すと二重になり、2 つ目以降のパラメータ名が `amp;xxx` になって黙って無視される。
  絞り込み条件が落ちても画面はエラーにならないので気付きにくい。
- 自前で `<a href="...">` を組み立てる場合（`Html::sortLabel` など）は逆に `&amp;` が正しい。
  **どちらの層でエスケープするのか**を必ず確認する。
- クエリ文字列の値は HTML エスケープ（`e()`）ではなく URL エンコードを使う。

### 表

- 列を増減したら **colgroup / thead / tbody / データ無しの行 / tfoot の `colspan`** を全て数え直す。
  `<colgroup span="N">` と `<col>` の数え方が混在しているので、実際に描画して数えること。
- 列を消したら、対応するセル（空の `<td>` が残りがち）と幅指定も消す。

### アセット

- `public/assets/js` `public/assets/css` を変更したら `Html::versionedScript` / `Html::versionedStyle`
  を使う。nginx は `/assets` に `Cache-Control` を返さないため、素の `Html::script` では
  ブラウザが古いファイルを使い続ける。
- **JSON の応答形式を変えたら、それを読む JS と必ず同時にレビューする。**
  片方だけ変わるとキャッシュされた旧 JS が例外を投げ、画面が無言で壊れる。

### 表示

- 金額の桁区切りはカンマ。Highcharts 4 の既定は**空白**なので
  `Highcharts.numberFormat(v, 0, '.', ',')` と明示する。
- 支出は DB 上マイナス。グラフでは符号を反転して上向きに揃える（集計表はマイナスのまま）。
  同じ画面で向きが混在していないか確認する。
- 数値に見出しや単位が無いと、何との比較か読み手に伝わらない。裸の数字を置かない。
- 値が片側にしか出ない軸は、空いている側を描かない（`min: 0` など）。
- UI の状態保持は揃える。タブは `startTabs`、セレクトは `rememberSelect` でクッキーに持つ。
  片方だけ保持されると壊れているように見える。
- `col-*` は `.row` か `.form-horizontal .form-group` の中に置く。
  Bootstrap 3 の相殺マージンが効かないと列の内側余白の分だけずれる。

### PHP 8

- 任意項目は `?? null` で守る。未送信の配列キーへのアクセスは警告が例外化し、処理ごと落ちる。
- `count()` / `strlen()` に null を渡さない。null を返しうるメソッドは空配列を返すようにする。

### SQL

- `ONLY_FULL_GROUP_BY` が有効。SELECT する非集約列は全て GROUP BY に入れる。
- GROUP BY には**列の別名ではなく実際の列名**を書く（別名は受け付けられない）。
- MariaDB に `ANY_VALUE` は無い。1 件に畳むときは `MIN()` などを使う。

### 状態を持つオブジェクト

- モデルのプロパティ（`$rules` など）を検証のたびに書き換えない。
  同一インスタンスが使い回されると、後続の検証が前の設定で走る。
  用途ごとに異なるルールは引数で渡す。

## 検証

- 本番データを持つ稼働環境で作業する。DB を変更する前にダンプを取る。
- 動作確認で作成したアカウントやレコードは必ず削除し、件数が作業前と一致することを確認する。
- Blade を編集した直後にテストする場合は `php artisan view:clear` を実行する。
  ソースより新しいコンパイル済みビューが残っていると、古い内容のまま検証してしまう。
- 画面の変更は HTTP 経由で確認する。ブラウザが無いためデータと配信ファイルまでを確認し、
  **見た目は未確認である旨を明示する**。
