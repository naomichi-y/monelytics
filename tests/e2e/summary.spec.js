// @ts-check
const { test, expect } = require('@playwright/test');
const { login, formatMonth, reportTable } = require('./helpers');

/**
 * 集計画面はどれも、サーバが組んだ HTML の断片を $.get で差し込む作りになって
 * いる。壊れるのは JS 側でも PHP 側でもなく、その継ぎ目 (URL、要素の id、
 * クエリの引き継ぎ) なので、ここを画面越しに押さえる。
 */
/**
 * 推移グラフの Highcharts インスタンスを取り出して fn に渡す。
 *
 * @param {import('@playwright/test').Page} page
 * @param {Function} fn
 */
function chartEvaluate(page, fn) {
  return page.locator('#yearly_trend_chart').evaluate(
    (node, body) => {
      const chart = Highcharts.charts.filter(Boolean).find((entry) => entry.renderTo === node);

      return new Function('chart', `return (${body})(chart);`)(chart);
    },
    fn.toString()
  );
}

test.describe('集計', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('月別集計のタブを切り替えると、その中身が読み込まれる', async ({ page }) => {
    await page.goto(`/summary/monthly?date_month=${formatMonth(new Date())}`);

    // 既定は集計表。小項目名が出ていれば断片の差し込みまで届いている。
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

  /**
   * 以前使っていた jquery.cookie は path を指定せずに書いていた。その場合の
   * 保存先はブラウザが決め、URL の「最後の / まで」になる。つまり
   * /summary/monthly で書いたものは /summary に付く。js-cookie は "/" に書く。
   *
   * 両方が残るとブラウザはパスの長いほうを先に並べ、js-cookie は最初に
   * 見つけたものを返して打ち切るため、古い値が新しい値を隠し続ける。
   *
   * まっさらなブラウザでは起きないので、古い Cookie を自分で置いて確かめる。
   */
  test('移行前のパス付きクッキーが残っていてもタブは保持される', async ({ page, context }) => {
    const url = new URL(page.url());

    await context.addCookies([{
      name: 'monthly_summary-tab',
      value: '0',
      domain: url.hostname,
      path: '/summary',
    }]);

    await page.goto('/summary/monthly');
    await page.getByRole('tab', { name: 'ランキング' }).click();
    await expect(page.locator('#tabs')).toContainText('E2E スーパー');

    await page.reload();

    await expect(page.getByRole('tab', { name: 'ランキング' })).toHaveAttribute('aria-selected', 'true');
  });

  /**
   * カレンダーの 2 段の金額は、上が変動収支で下が固定収支。以前は段の位置で
   * しか区別できず、表の下に「※括弧内は固定収支」と書いてあったが、括弧は
   * 2015 年の最初のコミットから一度も付いていなかった。
   *
   * ラベルを付けたうえで注釈を外したので、両方をここで押さえる。金額の右端が
   * 揃うことまで見るのは、揃っていないと 2 段が別の列に見えるため。
   */
  test('カレンダーの金額に変動と固定のラベルが付く', async ({ page }) => {
    await page.goto(`/summary/monthly?date_month=${formatMonth(new Date())}`);
    await page.getByRole('tab', { name: 'カレンダー' }).click();

    // 集計表のタブは切り替えたあとも DOM に残るため、カレンダーにしかない
    // .amount-row を持つセルだけを対象にする。
    const cells = page.locator('#tabs td').filter({ has: page.locator('.amount-row') });
    await expect(cells.first().locator('.cost-type').first()).toHaveText('変動');

    // 固定収支のある日はシードの給与。その日のセルで 2 段を突き合わせる。
    const cell = cells.filter({ hasText: '固定' }).first();
    await expect(cell.locator('.cost-type')).toHaveText(['変動', '固定']);

    const rights = await cell.locator('.money').evaluateAll(
      (nodes) => nodes.map((node) => Math.round(node.getBoundingClientRect().right))
    );
    expect(new Set(rights).size).toBe(1);

    // 実態と食い違っていた注釈は消した。戻ってきていないこと。
    await expect(page.locator('#tabs')).not.toContainText('括弧');
  });

  test('集計表の金額から日別集計へ検索条件が引き継がれる', async ({ page }) => {
    const month = formatMonth(new Date());

    await page.goto(`/summary/monthly?date_month=${month}`);

    const foodRow = reportTable(page).getByRole('row').filter({ hasText: '食料品' });
    await foodRow.getByRole('link').first().click();

    await expect(page).toHaveURL(/\/summary\/daily\?/);

    // 小項目とクレジット区分が URL に乗っていること。ここが壊れると、
    // 絞り込んだはずの一覧に関係のない行が出る。
    const url = new URL(page.url());
    expect(url.searchParams.get('date_month')).toBe(month);
    expect(url.searchParams.getAll('activity_category_item_id[]').length).toBeGreaterThan(0);

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

    await expect(page.locator('#tab-container')).toBeVisible();

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

  /**
   * 縦スクロールバーが出ると、その幅ぶん表がはみ出して横スクロールバーまで
   * 現れる。中身は収まっているので月別では止める。年別は列が多くて本当に
   * 入りきらないため、動かせるままにする。
   */
  test('横スクロールは入りきらない表にだけ出る', async ({ page }) => {
    const scrollers = () => page.evaluate(() => Array.from(document.querySelectorAll('#tab-container div'))
      .filter((node) => /auto/.test(node.style.overflow) || /auto/.test(node.style.overflowY))
      .map((node) => ({
        overflowX: getComputedStyle(node).overflowX,
        overflow: node.scrollWidth - node.clientWidth,
      })));

    await page.goto(`/summary/monthly?date_month=${formatMonth(new Date())}`);
    await expect.poll(async () => (await scrollers())[0]?.overflowX).toBe('hidden');

    // 列が入りきらない状態は、画面を狭めて作る。シードの小項目数では
    // 年別集計でも通常の幅に収まってしまう。
    await page.setViewportSize({ width: 520, height: 800 });
    await page.goto(`/summary/monthly?date_month=${formatMonth(new Date())}`);
    await expect.poll(async () => (await scrollers())[0]?.overflowX).toBe('auto');
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
   * ツールチップは同じ目盛りの小項目を全て並べる。どれを指しているのか
   * 分かるよう、カーソルが捉えた小項目だけ濃い太字にし、残りは薄くしている。
   */
  test('推移グラフは指している小項目だけを立たせる', async ({ page }) => {
    const year = new Date().getFullYear();

    await page.goto(`/summary/yearly?begin_year=${year - 2}&end_year=${year}&output_type=2`);
    await page.getByRole('tab', { name: '推移グラフ' }).click();
    await expect(page.locator('#yearly_trend_chart svg')).toBeVisible();

    // 「すべて」にして、支出と収入の小項目を同じグラフへ並べる。行が 1 つしか
    // 出ないツールチップでは、濃さの違いを比べられない。
    await page.selectOption('#trend_balance_type', '');
    await expect(page.locator('#yearly_trend_chart svg')).toBeVisible();

    // 最も多くの小項目が値を持つ目盛りを選ぶ。
    const target = await chartEvaluate(page, (chart) => {
      const counts = chart.xAxis[0].categories.map(
        (label, index) => chart.series.filter((s) => s.points[index] && s.points[index].y !== null).length
      );
      const index = counts.indexOf(Math.max(...counts));
      const point = chart.series.find((s) => s.points[index] && s.points[index].y !== null).points[index];
      const box = chart.container.getBoundingClientRect();

      return { x: box.left + chart.plotLeft + point.plotX, y: box.top + chart.plotTop + point.plotY };
    });

    // 一度離れた場所を通す。いきなり点の上へ跳ぶと mousemove が届かない。
    await page.mouse.move(target.x - 60, target.y - 60);
    await page.mouse.move(target.x, target.y);

    await expect
      .poll(() => chartEvaluate(page, (chart) => (chart.hoverPoint ? chart.hoverPoint.series.name : null)))
      .not.toBeNull();

    // 小項目名を持つ行だけを見る。先頭の丸と日付は対象外。
    const rows = await chartEvaluate(page, (chart) => {
      const hovered = chart.hoverPoint.series.name;

      return Array.from(chart.tooltip.label.text.element.querySelectorAll('tspan'))
        .filter((node) => chart.series.some((s) => node.textContent.startsWith(s.name)))
        .map((node) => ({
          hovered: node.textContent.startsWith(hovered),
          weight: getComputedStyle(node).fontWeight,
          opacity: Number(getComputedStyle(node).fillOpacity),
        }));
    });

    expect(rows.length).toBeGreaterThan(1);

    const hovered = rows.find((row) => row.hovered);

    expect(hovered.weight).toBe('700');
    expect(hovered.opacity).toBe(1);

    for (const row of rows.filter((entry) => !entry.hovered)) {
      expect(row.weight).toBe('400');
      expect(row.opacity).toBeLessThan(1);
    }
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
