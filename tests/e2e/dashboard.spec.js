// @ts-check
const { test, expect } = require('@playwright/test');
const { login } = require('./helpers');

/**
 * 変動支出の並びの、列ごとの実寸。
 *
 * @param {import('@playwright/test').Page} page
 */
function columns(page) {
  return page.locator('.variable-expense').evaluate((root) => {
    const cells = [...root.querySelector('tbody tr').children];

    return {
      table: root.querySelector('table').getBoundingClientRect().width,
      name: cells[0].getBoundingClientRect().width,
      bar: cells[1].getBoundingClientRect().width,
      amount: cells[2].getBoundingClientRect().width,
      difference: cells[3].getBoundingClientRect().width,
    };
  });
}

/**
 * 7 桁の額を入れたときに、各セルから何 px はみ出すか。
 *
 * シードの額は 4 桁で、そのままでは狭すぎる列でも収まってしまう。
 *
 * @param {import('@playwright/test').Page} page
 */
function overflowWithSevenDigits(page) {
  return page.locator('.variable-expense').evaluate((root) => {
    const unit = (value) => `<span class="money">${value} <span class="unit">円</span></span>`;
    const cells = [...root.querySelector('tbody tr').children];

    cells[2].innerHTML = unit('9,999,999');
    cells[3].innerHTML = unit('-9,999,999');

    return cells.map((cell) => Math.max(0, cell.scrollWidth - cell.clientWidth));
  });
}

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
   * 以前は場所と用途を両方 d-none で隠しており、スマホでは発生日・小項目・金額
   * しか出なかった。どこで何に使ったのかが分からないうえ、隠した列のぶん右が
   * 空いていたので、幅が足りなかったわけでもなかった。
   *
   * とはいえ 5 列をそのまま並べると 1 列あたり 4、5 文字しか残らないので、
   * 用途は列ごと畳んで場所の列へ入れる。
   *
   * 幅の取り合いは端末の幅で変わる。割合で配り直した版は 320px で金額が隣へ
   * はみ出して重なり、日付を 1 行に伸ばした版は表そのものが入れ物からはみ出て
   * 右端が切れた。どちらも 390px では起きない。狭いほうも一緒に測る。
   */
  test('スマホ幅でも場所と用途が読める', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.reload();

    const table = page.locator('#activity_history table');
    await expect(table).toBeVisible();

    // 見えている列は 4 つ。用途は場所の列へ畳まれる。
    const visible = table.locator('thead th:visible');
    // 見え方で確かめる。畳んだ側は display で消しているだけなので、
    // textContent で見ると隠れている文字まで拾ってしまう。
    await expect(visible).toHaveCount(4);
    await expect(visible.nth(3)).toHaveText('場所・用途', { useInnerText: true });

    // その 1 つのセルに場所と用途が両方入ること。シードの当月の食料品は
    // どちらも埋まっている。
    const merged = table.locator('tbody tr').filter({ hasText: '当月の食料品' }).locator('td').nth(3);
    await expect(merged).toHaveText('E2E スーパー / 当月の食料品', { useInnerText: true });

    // 表が入れ物に収まること。はみ出すと右端の列が読めない。
    for (const width of [320, 360, 390]) {
      await page.setViewportSize({ width, height: 844 });
      await page.reload();
      await table.waitFor();

      const fit = await page.locator('#activity_history').evaluate((box) => ({
        container: Math.round(box.getBoundingClientRect().width),
        table: Math.round(box.querySelector('table').getBoundingClientRect().width),
      }));

      expect(fit.table, `${width}px`).toBeLessThanOrEqual(fit.container);
    }
  });

  /**
   * 広い画面では場所と用途を別々の列に戻す。畳んだままだと、幅があるのに
   * 1 つのセルへ詰めることになる。
   */
  test('広い画面では場所と用途が別の列に戻る', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.reload();

    const table = page.locator('#activity_history table');
    const visible = table.locator('thead th:visible');

    await expect(visible).toHaveCount(5);
    await expect(visible.nth(3)).toHaveText('場所', { useInnerText: true });
    await expect(visible.nth(4)).toHaveText('用途', { useInnerText: true });

    const row = table.locator('tbody tr').filter({ hasText: '当月の食料品' });

    await expect(row.locator('td').nth(3)).toHaveText('E2E スーパー', { useInnerText: true });
    await expect(row.locator('td').nth(4)).toHaveText('当月の食料品', { useInnerText: true });
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
    const bars = panel.locator('.bar:not(.previous)');
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
   * 今月の棒の下に、先月の同じ時点の棒を敷く。同じものの別の時点なので、
   * 色は今月と同じ赤を薄めたものにして、太さと濃さだけで前後を出す。
   *
   * 長さの基準は今月と先月を通した最大額 (PHPUnit が見ている)。ここでは、
   * その基準で引いた棒が枠に収まっているかと、先月の記録がない小項目で
   * 行がでこぼこにならないかを見る。
   */
  test('今月の変動支出に先月の棒が重なる', async ({ page }) => {
    const panel = page.locator('#variable_expense');
    await expect(panel.locator('.bar').first()).toBeVisible();

    const shape = await panel.evaluate((node) => {
      const cell = node.querySelector('tbody td:nth-child(2)');
      const width = cell.getBoundingClientRect().width;

      return {
        rows: Array.from(node.querySelectorAll('tbody tr')).map((tr) => {
          const box = (selector) => tr.querySelector(selector).getBoundingClientRect();
          const current = box('.bar:not(.previous)');
          const previous = box('.bar.previous');

          return {
            currentHeight: Math.round(current.height),
            previousHeight: Math.round(previous.height),
            currentWidth: Math.round(current.width),
            previousWidth: Math.round(previous.width),
            rowHeight: Math.round(tr.getBoundingClientRect().height),
          };
        }),
        cellWidth: Math.round(width),
        currentColor: getComputedStyle(node.querySelector('.bar:not(.previous)')).backgroundColor,
        previousColor: getComputedStyle(node.querySelector('.bar.previous')).backgroundColor,
      };
    });

    // 先月の棒は今月より細い。
    for (const row of shape.rows) {
      expect(row.previousHeight).toBeLessThan(row.currentHeight);
      expect(row.currentWidth).toBeLessThanOrEqual(shape.cellWidth);
      expect(row.previousWidth).toBeLessThanOrEqual(shape.cellWidth);
    }

    // 先月の記録がない小項目でも行の高さは変わらない。幅 0 の棒を残して
    // あるため。畳むとその行だけ詰まり、並びがでこぼこに見える。
    expect(shape.rows.some((row) => row.previousWidth === 0)).toBe(true);
    expect(new Set(shape.rows.map((row) => row.rowHeight)).size).toBe(1);

    // 同じ赤を薄めた色。別の色にすると種類の違いに読める。
    expect(shape.currentColor).toBe('rgb(236, 87, 72)');
    expect(shape.previousColor).toBe('rgb(246, 179, 173)');

    await expect(panel.getByText('薄い棒と増減は')).toBeVisible();
  });

  /**
   * かんたん入力の送り先は cost/variable で、作られるのは変動収支。固定収支の
   * 小項目を選べてしまうと、選んだとおりに登録されない。
   */
  /**
   * 額と増減は右揃えで、7 桁でも padding 込み 89px あれば足りる。26% ずつ
   * では 187px を取って左が空くだけだったので、余りは棒へ回した。
   *
   * 切り替えは画面幅ではなくこの部品自身の幅で行う。ダッシュボードは画面が
   * 広がると多段組みになり、両者が一致しない (700px の画面で表は 639px、
   * 768px の画面では 439px)。画面幅で分けると広い画面のほうが狭くなる。
   */
  test('広く取れるときは棒を長くする', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto('/dashboard');
    await expect(page.locator('.variable-expense')).toBeVisible();

    const wide = await columns(page);

    // 棒が一番広い列であること。ここが読みたいものなので。
    expect(wide.bar).toBeGreaterThan(wide.name);
    expect(wide.bar).toBeGreaterThan(wide.amount + wide.difference);

    // 7 桁を入れてもセルから出ないこと。
    expect(await overflowWithSevenDigits(page)).toEqual([0, 0, 0, 0]);
  });

  /**
   * 狭いところでは配分を変えない。20% では 7 桁が入らず、桁の頭がセルから
   * はみ出す。
   */
  test('狭いときは額の幅を削らない', async ({ page }) => {
    await page.setViewportSize({ width: 414, height: 900 });
    await page.goto('/dashboard');
    await expect(page.locator('.variable-expense')).toBeVisible();

    const narrow = await columns(page);

    expect(narrow.amount / narrow.table).toBeGreaterThan(0.24);
    expect(await overflowWithSevenDigits(page)).toEqual([0, 0, 0, 0]);
  });

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
