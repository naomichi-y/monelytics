// @ts-check
const { expect } = require('@playwright/test');

/**
 * シードで作られる利用者。database/seeds/E2e/UserTableSeeder.php と揃える。
 */
const USER = {
  email: 'e2e@monelytics.test',
  password: 'e2e-password',
};

/**
 * ログインする。
 *
 * セレクタにはラベルと役割だけを使う。Bootstrap のクラス名を使うと、
 * 3 から 5 へ上げたときにテストが一斉に落ちて、何が壊れたのか分からなくなる。
 *
 * @param {import('@playwright/test').Page} page
 */
async function login(page) {
  await page.goto('/user/login');

  await page.getByLabel('メールアドレス').fill(USER.email);
  await page.getByLabel('パスワード').fill(USER.password);
  await page.getByRole('button', { name: 'ログイン' }).click();

  await expect(page).toHaveURL(/\/dashboard$/);
}

/**
 * 画面の日付入力が受け取る形式 (yyyy/mm/dd)。
 *
 * デスクトップ扱いのときは jQuery UI の datepicker が付いたテキスト入力に
 * なるため、<input type="date"> の yyyy-mm-dd ではない。
 *
 * @param {Date} date
 * @returns {string}
 */
function formatDate(date) {
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${date.getFullYear()}/${month}/${day}`;
}

/**
 * @param {Date} date
 * @returns {string} yyyy-mm 形式。検索条件のクエリに使う。
 */
function formatMonth(date) {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

/**
 * テストごとに一意な目印。
 *
 * 登録・編集・削除のテストは同じデータベースを共有するため、シードの行や
 * 他のテストが作った行と取り違えないよう、場所の欄に入れて識別する。
 *
 * @param {string} prefix
 * @returns {string}
 */
function marker(prefix) {
  return `${prefix}-${Date.now()}`;
}

/**
 * 変動収支を 1 件登録し、その目印を返す。
 *
 * 編集・削除のテストは、シードの行ではなく自分で作った行を対象にする。
 * シードを書き換えると、あとに走るテストの前提が崩れるため。
 *
 * @param {import('@playwright/test').Page} page
 * @param {{ groupName: string, amount: number|string, marker: string }} params
 */
async function createVariableCost(page, params) {
  await page.goto('/cost/variable/create');

  await page.locator('[name="activity_date[0]"]').fill(formatDate(new Date()));
  await page.locator('[name="activity_category_group_id[0]"]').selectOption({ label: params.groupName });
  await page.locator('[name="amount[0]"]').fill(String(params.amount));
  await page.locator('[name="location[0]"]').fill(params.marker);

  await page.getByRole('button', { name: '登録' }).click();
}

/**
 * 日別集計で、目印を持つ行を返す。
 *
 * data-id はアプリ自身が付けている属性で、画面の見た目を変えても残る。
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} value
 */
function rowByMarker(page, value) {
  return page.locator('tr[data-id]').filter({ hasText: value });
}

/**
 * 月別集計の集計表のうち、実際に操作できる複製を返す。
 *
 * ヘッダ固定の jquery.tablefix がテーブルを 4 つに複製して重ねているため、
 * 素直に探すと固定ヘッダの下に隠れた複製を掴んでしまい、クリックが
 * ヘッダに吸われる。本体 (overflow: auto のもの) は最後に置かれる。
 *
 * @param {import('@playwright/test').Page} page
 */
function reportTable(page) {
  return page.locator('#tab-container table').last();
}

module.exports = {
  USER,
  login,
  formatDate,
  formatMonth,
  marker,
  createVariableCost,
  rowByMarker,
  reportTable,
};
