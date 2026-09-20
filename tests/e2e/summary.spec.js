// @ts-check
const { test, expect } = require('@playwright/test');
const { login, formatMonth, reportTable } = require('./helpers');

/**
 * 集計画面はどれも、サーバが組んだ HTML の断片を $.get で差し込む作りになって
 * いる。壊れるのは JS 側でも PHP 側でもなく、その継ぎ目 (URL、要素の id、
 * クエリの引き継ぎ) なので、ここを画面越しに押さえる。
 */
test.describe('集計', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('月別集計のタブを切り替えると、その中身が読み込まれる', async ({ page }) => {
    await page.goto(`/summary/monthly?date_month=${formatMonth(new Date())}`);

    // 既定は集計表。科目名が出ていれば断片の差し込みまで届いている。
    await expect(reportTable(page).getByText('食料品').first()).toBeVisible();

    await page.getByRole('tab', { name: 'カレンダー' }).click();
    await expect(page.locator('#tabs')).toContainText('日');

    await page.getByRole('tab', { name: 'ランキング' }).click();
    await expect(page.locator('#tabs')).toContainText('E2E スーパー');
  });

  test('選んだタブはリロードしても残る', async ({ page }) => {
    await page.goto(`/summary/monthly?date_month=${formatMonth(new Date())}`);

    await page.getByRole('tab', { name: 'ランキング' }).click();
    await expect(page.locator('#tabs')).toContainText('E2E スーパー');

    await page.reload();

    // 選択状態はクッキーに入れている。リロードで集計表へ戻ってはいけない。
    await expect(page.getByRole('tab', { name: 'ランキング' })).toHaveAttribute('aria-selected', 'true');
  });

  test('集計表の金額から日別集計へ検索条件が引き継がれる', async ({ page }) => {
    const month = formatMonth(new Date());

    await page.goto(`/summary/monthly?date_month=${month}`);

    const foodRow = reportTable(page).getByRole('row').filter({ hasText: '食料品' });
    await foodRow.getByRole('link').first().click();

    await expect(page).toHaveURL(/\/summary\/daily\?/);

    // 科目とクレジット区分が URL に乗っていること。ここが壊れると、
    // 絞り込んだはずの一覧に関係のない行が出る。
    const url = new URL(page.url());
    expect(url.searchParams.get('date_month')).toBe(month);
    expect(url.searchParams.getAll('activity_category_group_id[]').length).toBeGreaterThan(0);

    // 引き継いだ条件で絞り込まれた結果が出ていること。
    const rows = page.locator('tr[data-id]');
    await expect(rows.first()).toContainText('食料品');
  });

  /**
   * jquery.tablefix は呼ばれた時点の幅をピクセルで書き込む。以前は
   * それきりで、読み込み後にウィンドウを広げると表だけが元の幅のまま
   * 残り、右側が大きく空いていた。
   */
  test('ウィンドウ幅を変えても集計表が枠いっぱいに保たれる', async ({ page }) => {
    await page.setViewportSize({ width: 1100, height: 800 });
    await page.goto(`/summary/monthly?date_month=${formatMonth(new Date())}`);

    const widths = () => page.evaluate(() => {
      const tables = document.querySelectorAll('#tab-container table');

      return {
        container: Math.round(document.querySelector('#tab-container').getBoundingClientRect().width),
        table: Math.round(tables[tables.length - 1].getBoundingClientRect().width),
      };
    });

    await expect.poll(async () => {
      const { container, table } = await widths();

      return container - table;
    }).toBeLessThan(10);

    await page.setViewportSize({ width: 1400, height: 800 });

    await expect.poll(async () => {
      const { container, table } = await widths();

      return container - table;
    }).toBeLessThan(10);
  });

  test('前月比の欄に増減が出る', async ({ page }) => {
    await page.goto(`/summary/monthly?date_month=${formatMonth(new Date())}`);

    const foodRow = reportTable(page).getByRole('row').filter({ hasText: '食料品' });

    // 値そのものは他のテストが登録した行で動くため、書式だけを見る。
    // 欄が空になる、記号が出ない、といった壊れ方を捕まえるのが目的。
    await expect(foodRow).toContainText(/[+\-±][\d.]+%/);
  });

  test('月別集計の構成グラフが描画される', async ({ page }) => {
    await page.goto(`/summary/monthly?date_month=${formatMonth(new Date())}`);

    await page.getByRole('tab', { name: '支出構成グラフ' }).click();

    const chart = page.locator('[id^="balance_type_"]').last();
    await expect(chart.locator('svg')).toBeVisible();
  });

  test('年別集計の推移グラフが描画される', async ({ page }) => {
    const year = new Date().getFullYear();

    await page.goto(`/summary/yearly?begin_year=${year - 2}&end_year=${year}&output_type=2`);

    await page.getByRole('tab', { name: '推移グラフ' }).click();

    // Highcharts が SVG を描くまで待つ。「データがありません。」で止まる場合は
    // JSON エンドポイントか描画のどちらかが壊れている。
    const chart = page.locator('#yearly_trend_chart');
    await expect(chart.locator('svg')).toBeVisible();
    await expect(chart).not.toContainText('データがありません。');
  });

  /**
   * Highcharts 13 は配色を light-dark() で選ぶ。OS が暗色設定のブラウザだと
   * グラフだけ黒くなり、明色のページの中で浮く。
   */
  test('ブラウザが暗色設定でもグラフは明色のまま', async ({ page }) => {
    await page.emulateMedia({ colorScheme: 'dark' });

    const year = new Date().getFullYear();

    await page.goto(`/summary/yearly?begin_year=${year - 2}&end_year=${year}&output_type=2`);
    await page.getByRole('tab', { name: '推移グラフ' }).click();

    const background = page.locator('#yearly_trend_chart .highcharts-background');
    await expect(background).toBeAttached();

    await expect
      .poll(() => background.evaluate((node) => getComputedStyle(node).fill))
      .toBe('rgb(255, 255, 255)');
  });

  test('年別集計の集計表に過去の年が並ぶ', async ({ page }) => {
    const year = new Date().getFullYear();

    await page.goto(`/summary/yearly?begin_year=${year - 2}&end_year=${year}&output_type=2`);

    await expect(page.locator('#tabs')).toContainText(String(year));
    await expect(page.locator('#tabs')).toContainText(String(year - 1));
  });
});
