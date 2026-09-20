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
 * 以前は head_tags が固定文字列を出しており、どの画面も同じ表題だった。
 * 履歴やタブの一覧から画面を見分けられない。
 */
test('画面ごとに表題が付く', async ({ page }) => {
  await login(page);

  const titles = new Set();

  for (const path of paths) {
    await page.goto(path);

    const title = await page.title();

    expect(title, path).toMatch(/^.+ - monelytics$/);
    titles.add(title);
  }

  // 全画面が同じ表題になっていないこと。
  expect(titles.size).toBe(paths.length);
});

/**
 * BS3 は float: right で並べており、先に書いた要素が右端に来ていた。BS5 の
 * flex では書いた順に左から並ぶため、入れ替えないと検索とアカウントが逆になる。
 */
test('ナビは検索の右にアカウントが来る', async ({ page }) => {
  await login(page);

  const search = await page.locator('.navbar-search').boundingBox();
  const account = await page
    .getByRole('button', { name: 'アカウント' })
    .boundingBox();

  expect(search.x + search.width).toBeLessThanOrEqual(account.x);
});

/**
 * 操作列のボタンは 1 行に収まること。余白を広げすぎると折り返し、行の高さが
 * 倍になって一覧が読みづらくなる。
 */
test('一覧の操作ボタンが折り返さない', async ({ page }) => {
  await login(page);
  await page.goto('/settings/activityCategory');

  const buttons = page.locator('tbody tr').first().locator('.btn');
  await expect(buttons).toHaveCount(3);

  const tops = await buttons.evaluateAll((list) => list.map((b) => Math.round(b.getBoundingClientRect().top)));

  expect(new Set(tops).size).toBe(1);
});

/**
 * Bootstrap 5 では .card が縦方向の flex コンテナになり、.row は col-* を
 * 持たない子にも width: 100% を当てる。置き換え前の .well と float 方式の
 * .row ではどちらも起きなかったため、直下に置いたボタンが横いっぱいに
 * 伸びる箇所が各所に生まれた。
 */
test('囲みや行の中のボタンが横いっぱいに伸びない', async ({ page }) => {
  await login(page);

  /** @returns {Promise<string[]>} */
  const findStretched = () => page.evaluate(() => {
    const out = [];
    const check = (parent, button) => {
      const parentWidth = parent.getBoundingClientRect().width;
      const width = button.getBoundingClientRect().width;

      if (parentWidth > 0 && width / parentWidth > 0.8) {
        out.push(`${button.textContent.trim()} (${Math.round(width)}/${Math.round(parentWidth)})`);
      }
    };

    document.querySelectorAll('.card').forEach((card) => {
      card.querySelectorAll(':scope > .btn, :scope > button').forEach((button) => check(card, button));
    });

    document.querySelectorAll('.row').forEach((row) => {
      Array.from(row.children).forEach((child) => {
        if (/\bcol(-|$)/.test(child.className) || !/\bbtn\b/.test(child.className)) {
          return;
        }

        check(row, child);
      });
    });

    return out;
  });

  const stretched = [];

  for (const path of paths) {
    await page.goto(path);
    (await findStretched()).forEach((entry) => stretched.push(`${path} : ${entry}`));

    // 検索条件はモーダルの中にあり、開かないと DOM に現れない。
    const opener = page.locator('#open_condition');

    if (await opener.count()) {
      await opener.click();
      await expect(page.locator('.modal.show')).toBeVisible();
      (await findStretched()).forEach((entry) => stretched.push(`${path} (検索条件) : ${entry}`));
    }
  }

  expect(stretched, stretched.join('\n')).toEqual([]);
});
