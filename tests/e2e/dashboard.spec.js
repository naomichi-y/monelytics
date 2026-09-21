// @ts-check
const { test, expect } = require('@playwright/test');
const { login } = require('./helpers');

test.describe('ダッシュボード', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  /**
   * 狭い画面で隠す列は hidden-xs で指定されていた。Bootstrap 5 は表示形式まで
   * クラスに書くため、td や th に table-row を当てるとセルが行として扱われ、
   * 列が縦に潰れて値が表の外へはみ出す。
   */
  test('最近の収支履歴が 5 列で並ぶ', async ({ page }) => {
    const table = page.locator('#activity_history table');
    await expect(table).toBeVisible();

    const headers = table.locator('thead th');
    await expect(headers).toHaveCount(5);

    // 見出しが横一列に並んでいること。縦に潰れると上端が揃わない。
    const tops = await headers.evaluateAll((cells) => cells.map((c) => Math.round(c.getBoundingClientRect().top)));
    expect(new Set(tops).size).toBe(1);

    // 本文のセルも同様に 1 行へ収まること。
    const firstRow = table.locator('tbody tr').first();
    await expect(firstRow.locator('td')).toHaveCount(5);

    const cellTops = await firstRow.locator('td').evaluateAll((cells) => cells.map((c) => Math.round(c.getBoundingClientRect().top)));
    expect(new Set(cellTops).size).toBe(1);
  });

  /**
   * 今月の収支状況は実額しか出しておらず、使いすぎかどうかが読めなかった。
   * 増減率は ajax で取り込む断片に載るので、率そのものの正しさは PHPUnit に
   * 任せ、ここでは画面まで届いているかだけを見る。
   */
  test('今月の収支状況に前月比が並ぶ', async ({ page }) => {
    const status = page.locator('#activity_status');
    await expect(status.locator('.activity-status')).toBeVisible();

    // 収入・支出・残高の 3 つに付く。
    const rates = status.locator('.comparison');
    await expect(rates).toHaveCount(3);

    for (const text of await rates.allInnerTexts()) {
      expect(text).toMatch(/^[+\-\u00b1][0-9]/);
    }

    // 金額は今月まるごと、増減率は今日までと基準が違う。どこまでを比べたのか
    // 書いていないと読み違える。
    await expect(status.getByText('までとの比較です。')).toBeVisible();
  });

  test('かんたん入力から登録できる', async ({ page }) => {
    await page.locator('#activity_category_group_id').selectOption({ label: '食料品' });
    await page.locator('#amount').fill('4321');
    await page.locator('#location').fill('E2E-DASHBOARD');
    await page.getByRole('button', { name: '登録' }).click();

    await expect(page.getByText('登録が完了しました。')).toBeVisible();
  });
});
