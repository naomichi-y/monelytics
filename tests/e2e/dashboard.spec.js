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

    // 先の日付で登録したものを出さないことわりは、表と同じ断片で返る。
    // どちらが欠けても、今記録したものが出ない理由が画面から読めない。
    await expect(page.locator('#activity_history')).toContainText('発生日が本日までのもの');

    // 隣の「今月の変動支出」と同じ件数にして、2 つの欄の下端を揃えている。
    await expect(table.locator('tbody tr')).toHaveCount(5);
  });

  /**
   * 収入と固定支出は月のうち決まった日にまとめて記録されるため、月の途中で
   * 前月と比べても使いすぎの目安にならない。画面には変動支出だけを出す。
   * 額の正しさは PHPUnit に任せ、ここは ajax の断片が画面まで届いているか。
   */
  test('今月の変動支出が小項目ごとの棒で並ぶ', async ({ page }) => {
    const panel = page.locator('#variable_expense');
    await expect(panel.locator('.variable-expense')).toBeVisible();

    // シードの当月の変動支出は食料品と日用品。給与と家賃は混ぜない。
    await expect(panel.getByRole('link', { name: '食料品' })).toBeVisible();
    await expect(panel.getByRole('link', { name: '日用品' })).toBeVisible();
    await expect(panel.getByText('給与')).toHaveCount(0);
    await expect(panel.getByText('家賃')).toHaveCount(0);

    // 棒は小項目の数だけ並び、最も多い小項目が横いっぱいになる。
    const bars = panel.locator('.bar');
    expect(await bars.count()).toBeGreaterThan(1);

    const widths = await bars.evaluateAll((els) => els.map((e) => e.getBoundingClientRect().width));
    expect(widths[0]).toBeGreaterThan(widths[1]);

    // 差額は符号付き。増減率ではなく額で出す。
    for (const text of await panel.locator('.difference').allInnerTexts()) {
      expect(text).toMatch(/^[+\-\u00b1][0-9]/);
    }

    await expect(panel.getByText('までとの比較')).toBeVisible();
  });

  /**
   * かんたん入力の送り先は cost/variable で、作られるのは変動収支。固定収支の
   * 小項目を選べてしまうと、選んだとおりに登録されない。
   */
  test('かんたん入力の小項目は変動収支だけ', async ({ page }) => {
    const options = page.locator('#activity_category_item_id option');

    await expect(options.filter({ hasText: '食料品' })).toHaveCount(1);
    await expect(options.filter({ hasText: '臨時ボーナス' })).toHaveCount(1);

    // 家賃と給与は固定収支。
    await expect(options.filter({ hasText: '家賃' })).toHaveCount(0);
    await expect(options.filter({ hasText: '給与' })).toHaveCount(0);
  });

  test('かんたん入力から登録できる', async ({ page }) => {
    await page.locator('#activity_category_item_id').selectOption({ label: '食料品' });
    await page.locator('#amount').fill('4321');
    await page.locator('#location').fill('E2E-DASHBOARD');
    await page.getByRole('button', { name: '登録' }).click();

    await expect(page.getByText('登録が完了しました。')).toBeVisible();
  });
});
