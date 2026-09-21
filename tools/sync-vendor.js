/**
 * node_modules から public/assets/components/ へ配布物を写す。
 *
 * ビルドは通さず、これまでどおり <script> と <link> で直接読む。目的は
 * 「どの版を使っているか package.json で分かる」状態にすることで、版を
 * 上げるときは package.json を書き換えて npm install と npm run vendor:sync
 * を流す。
 *
 * 配置先にバージョン番号を含めるのは、Html::script が付ける ?v= と違って
 * ブラウザのキャッシュを確実に切り分けるため。
 */
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const pkg = JSON.parse(fs.readFileSync(path.join(root, 'package.json'), 'utf8'));
const dest = path.join(root, 'public/assets/components');

/**
 * @param {string} name package.json の dependencies のキー
 * @returns {string} 固定してあるバージョン
 */
function version(name) {
  const value = pkg.dependencies[name];

  if (!value) {
    throw new Error(`package.json に ${name} がない`);
  }

  // 版を固定して運用する。範囲指定だと配置先のパスが定まらない。
  if (!/^\d+\.\d+\.\d+$/.test(value)) {
    throw new Error(`${name} は完全なバージョンで固定すること (${value})`);
  }

  return value;
}

const targets = [
  { dir: 'jquery', version: version('jquery'), from: 'jquery/dist/jquery.min.js', to: 'jquery.min.js' },
  { dir: 'js-cookie', version: version('js-cookie'), from: 'js-cookie/dist/js.cookie.min.js', to: 'js.cookie.min.js' },
  // bundle 版には Popper が同梱されている。ドロップダウンに要る。
  { dir: 'bootstrap', version: version('bootstrap'), from: 'bootstrap/dist/js/bootstrap.bundle.min.js', to: 'js/bootstrap.bundle.min.js' },
  // 見た目は Bootswatch の sandstone テーマ。素の bootstrap.min.css ではない。
  { dir: 'bootstrap', version: version('bootswatch'), from: 'bootswatch/dist/sandstone/bootstrap.min.css', to: 'sandstone/bootstrap.min.css' },
  // Bootstrap 4 で glyphicon が外れたため、アイコンは別パッケージから入れる。
  { dir: 'bootstrap-icons', version: version('bootstrap-icons'), from: 'bootstrap-icons/font/bootstrap-icons.min.css', to: 'bootstrap-icons.min.css' },
  { dir: 'bootstrap-icons', version: version('bootstrap-icons'), from: 'bootstrap-icons/font/fonts', to: 'fonts' },
  { dir: 'jquery-ui', version: version('jquery-ui-dist'), from: 'jquery-ui-dist/jquery-ui.min.js', to: 'jquery-ui.min.js' },
  { dir: 'jquery-ui', version: version('jquery-ui-dist'), from: 'jquery-ui-dist/jquery-ui.min.css', to: 'jquery-ui.min.css' },
  { dir: 'jquery-ui', version: version('jquery-ui-dist'), from: 'jquery-ui-dist/images', to: 'images' },
  { dir: 'highcharts', version: version('highcharts'), from: 'highcharts/highcharts.js', to: 'js/highcharts.js' },
];

// 使っていない版のディレクトリを先に落とす。残すと、どれが配信されている
// のか読めなくなるうえ、古い版が web から取れたままになる。
const keep = {};

for (const target of targets) {
  keep[target.dir] = keep[target.dir] || new Set();
  keep[target.dir].add(target.version);
}

for (const [dir, versions] of Object.entries(keep)) {
  const base = path.join(dest, dir);

  if (!fs.existsSync(base)) {
    continue;
  }

  for (const entry of fs.readdirSync(base)) {
    if (!versions.has(entry)) {
      fs.rmSync(path.join(base, entry), { recursive: true, force: true });
      console.log(`removed ${path.relative(root, path.join(base, entry))}`);
    }
  }
}

for (const target of targets) {
  const source = path.join(root, 'node_modules', target.from);
  const destination = path.join(dest, target.dir, target.version, target.to);

  if (!fs.existsSync(source)) {
    throw new Error(`見つからない: ${target.from}`);
  }

  fs.mkdirSync(path.dirname(destination), { recursive: true });
  fs.cpSync(source, destination, { recursive: true });

  console.log(`${target.from} -> ${path.relative(root, destination)}`);
}
