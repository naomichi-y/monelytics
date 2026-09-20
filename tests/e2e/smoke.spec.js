// @ts-check
const { test, expect } = require('@playwright/test');
const { login, formatMonth } = require('./helpers');

/**
 * 全画面を一度ずつ開き、コンソールエラーと読み込み失敗がないことを見る。
 *
 * Bootstrap の版を上げたときに一番出やすいのは「特定の画面だけ JS が
 * 落ちる」「差し替え忘れた資産が 404 になる」で、個別のテストより
 * 巡回のほうが早く気付ける。
 */
const paths = [
  '/dashboard',
  '/summary/daily',
  '/summary/monthly',
  '/summary/yearly',
  '/cost/variable/create',
  '/cost/constant/create',
  '/settings/activityCategory',
  '/settings/activityCategoryGroup',
  '/user',
  '/contact',
];

test('全画面でコンソールエラーと読み込み失敗が出ない', async ({ page }) => {
  const problems = [];

  page.on('console', (message) => {
    if (message.type() === 'error') {
      problems.push(`${page.url()} : console ${message.text()}`);
    }
  });
  page.on('pageerror', (error) => {
    problems.push(`${page.url()} : pageerror ${error.message}`);
  });
  page.on('response', (response) => {
    if (response.status() >= 400) {
      problems.push(`${page.url()} : ${response.status()} ${response.url()}`);
    }
  });

  await login(page);

  for (const path of paths) {
    const month = formatMonth(new Date());
    await page.goto(path.startsWith('/summary/') ? `${path}?date_month=${month}` : path);
    await page.waitForLoadState('networkidle');
  }

  expect(problems, problems.join('\n')).toEqual([]);
});

/**
 * .card は縦方向の flex コンテナなので、直下に置いたボタンは既定で横いっぱいに
 * 伸びる。置き換え前の .well は素の block で、伸びなかった。
 */
test('囲みの中のボタンが横いっぱいに伸びない', async ({ page }) => {
  await login(page);

  const stretched = [];

  for (const path of paths) {
    await page.goto(path);

    const found = await page.evaluate(() => {
      const out = [];

      document.querySelectorAll('.card').forEach((card) => {
        const cardWidth = card.getBoundingClientRect().width;

        card.querySelectorAll(':scope > .btn, :scope > button').forEach((button) => {
          const width = button.getBoundingClientRect().width;

          if (cardWidth > 0 && width / cardWidth > 0.9) {
            out.push(`${button.textContent.trim()} (${Math.round(width)}/${Math.round(cardWidth)})`);
          }
        });
      });

      return out;
    });

    found.forEach((entry) => stretched.push(`${path} : ${entry}`));
  }

  expect(stretched, stretched.join('\n')).toEqual([]);
});
