// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * E2E の設定。
 *
 * 対象は compose の e2e プロファイルで立てた専用インスタンス (web-e2e)。
 * 本番用のコンテナとはデータベースも Redis の DB 番号も分かれている。
 * 起動とシードの手順は tests/e2e/README.md を参照。
 */
module.exports = defineConfig({
  testDir: './tests/e2e',
  outputDir: './tests/e2e/results',

  // データベースを共有するため、並列に走らせると互いの登録・削除が混ざる。
  fullyParallel: false,
  workers: 1,

  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,

  reporter: [
    ['list'],
    ['html', { outputFolder: './tests/e2e/report', open: 'never' }],
  ],

  use: {
    // コンテナ内からは web-e2e、ホストからは localhost:8081。
    baseURL: process.env.E2E_BASE_URL || 'http://localhost:8081',

    // 失敗したときだけ残す。成功時に毎回吐くとリポジトリが膨らむ。
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',

    // 画面は Agent::isDesktop() で日付入力の種類を切り替える。デスクトップの
    // UA で走らせないと、テキスト入力ではなく <input type="date"> が出る。
    viewport: { width: 1280, height: 900 },
    locale: 'ja-JP',
    timezoneId: 'Asia/Tokyo',
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
