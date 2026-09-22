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

  /**
   * 選んだタブは URL に残す。クッキーに入れていた頃は、同じ画面を 2 つ開くと
   * 後から切り替えたほうに引きずられ、URL を人に渡しても相手には別のタブが
   * 出ていた。
   */
  test('選んだタブが URL に残り、開き直しても同じタブが出る', async ({ page }) => {
    await page.goto(`/summary/monthly?date_month=${formatMonth(new Date())}`);

    await page.getByRole('tab', { name: 'ランキング' }).click();
    await expect(page.locator('#tabs')).toContainText('利用頻度ランキング');

    // 番号ではなく名前。並べ替えたときに別のタブを指さないこと、URL を見て
    // どのタブか読めることの両方を、ここで押さえる。
    expect(new URL(page.url()).searchParams.get('tab')).toBe('ranking');

    await page.reload();
    await expect(page.getByRole('tab', { name: 'ランキング' })).toHaveAttribute('aria-selected', 'true');

    // 渡された URL から直接開いても同じタブ。
    const url = page.url();
    await page.goto('/dashboard');
    await page.goto(url);
    await expect(page.getByRole('tab', { name: 'ランキング' })).toHaveAttribute('aria-selected', 'true');
  });

  /**
   * 知らないタブ名を渡されても落ちず、先頭のタブを出す。URL は人が書き換えも
   * するし、タブの名前を変えたあとの古いリンクも届く。
   */
  test('知らないタブ名は先頭のタブになる', async ({ page }) => {
    await page.goto(`/summary/monthly?date_month=${formatMonth(new Date())}&tab=nosuchtab`);

    await expect(page.getByRole('tab', { name: '集計表' })).toHaveAttribute('aria-selected', 'true');
    await expect(reportTable(page).getByText('食料品').first()).toBeVisible();
  });

  /**
   * 月を変える、詳細検索を掛ける、はどちらも GET のフォームで、送信時に
   * クエリを自分の入力欄から組み直す。タブを持たせないと、そのたびに
   * 集計表へ戻ってしまう。
   */
  test('月を変えても詳細検索を掛けてもタブが残る', async ({ page }) => {
    await page.goto(`/summary/monthly?date_month=${formatMonth(new Date())}`);

    await page.getByRole('tab', { name: 'カレンダー' }).click();
    await expect(page.locator('#tabs')).toContainText('日');

    // 月の選択は変更と同時に送信される。
    const previous = formatMonth(new Date(new Date().setDate(0)));
    await page.locator('#date_month').selectOption(previous);

    await expect(page).toHaveURL(new RegExp(`date_month=${previous}`));
    await expect(page.getByRole('tab', { name: 'カレンダー' })).toHaveAttribute('aria-selected', 'true');

    // 詳細検索のモーダルは ajax で後から差し込まれる。
    await page.getByText('詳細検索').click();
    await page.locator('#search_modal').getByRole('button', { name: '検索' }).click();

    await expect(page.getByRole('tab', { name: 'カレンダー' })).toHaveAttribute('aria-selected', 'true');

    // 前月・翌月も同じ。リンクで飛ばさずフォームで送っているのはこのため。
    await page.getByRole('button', { name: '前月' }).click();

    await expect(page.getByRole('tab', { name: 'カレンダー' })).toHaveAttribute('aria-selected', 'true');
  });

  /**
   * 月を送るボタンは、記録のある月の間だけを動く。セレクトは記録のある月しか
   * 選択肢に持たないため、暦の上での隣へ送ると選択が「未指定」へ落ちる。
   *
   * その向きに記録がなければ押せない。シードの最も新しい月は当月なので、
   * 開いた時点で翌月は押せず、前月へ送ると押せるようになる。
   */
  test('前月・翌月で記録のある月を行き来できる', async ({ page }) => {
    const current = formatMonth(new Date());
    const previous = formatMonth(new Date(new Date().setDate(0)));

    await page.goto(`/summary/monthly?date_month=${current}`);

    await expect(page.getByRole('button', { name: '翌月' })).toBeDisabled();

    await page.getByRole('button', { name: '前月' }).click();

    await expect(page).toHaveURL(new RegExp(`date_month=${previous}`));
    await expect(page.locator('#date_month')).toHaveValue(previous);
    await expect(page.getByRole('button', { name: '翌月' })).toBeEnabled();

    await page.getByRole('button', { name: '翌月' }).click();

    await expect(page.locator('#date_month')).toHaveValue(current);
  });

  /**
   * 以前使っていた jquery.cookie は path を指定せずに書いていた。その場合の
   * 保存先はブラウザが決め、URL の「最後の / まで」になる。つまり
   * /summary/yearly で書いたものは /summary に付く。js-cookie は "/" に書く。
   *
   * 両方が残るとブラウザはパスの長いほうを先に並べ、js-cookie は最初に
   * 見つけたものを返して打ち切るため、古い値が新しい値を隠し続ける。
   *
   * タブは URL に移したので、クッキーに残っているのはグラフの絞り込みだけ。
   * まっさらなブラウザでは起きないため、古い Cookie を自分で置いて確かめる。
   */
  test('移行前のパス付きクッキーが残っていてもグラフの絞り込みは保持される', async ({ page, context }) => {
    const year = new Date().getFullYear();
    const url = new URL(page.url());

    await context.addCookies([{
      name: 'yearly_summary-balance_type',
      value: '1',
      domain: url.hostname,
      path: '/summary',
    }]);

    await page.goto(`/summary/yearly?begin_year=${year - 2}&end_year=${year}&output_type=2`);
    await page.getByRole('tab', { name: '推移グラフ' }).click();

    await page.selectOption('#trend_balance_type', '2');
    await expect(page.locator('#yearly_trend_chart svg')).toBeVisible();

    await page.reload();
    await page.getByRole('tab', { name: '推移グラフ' }).click();

    await expect(page.locator('#trend_balance_type')).toHaveValue('2');
  });

  /**
   * タブの中身はどれも ajax。jQuery UI は切り替えた瞬間に空のパネルを見せて
   * 応答を待つため、集計表のように重いものだと、その間タブの枠が見出しだけの
   * 高さまで縮んでいた (564px から 67px)。届くとまた伸びるので下にあるものが
   * 上下に飛び、待っているのか壊れたのかも分からなかった。
   */
  test('タブの読み込み中は待っていることが出て、枠が縮まない', async ({ page }) => {
    // 応答を遅らせて、読み込んでいる最中を掴めるようにする。
    await page.route('**/summary/monthly/report*', async (route) => {
      await new Promise((resolve) => setTimeout(resolve, 1500));

      return route.continue();
    });

    await page.goto(`/summary/monthly?date_month=${formatMonth(new Date())}&tab=calendar`);

    const tabs = page.locator('#tabs');
    await expect(tabs).toBeVisible();

    const before = (await tabs.boundingBox()).height;

    await page.getByRole('tab', { name: '集計表' }).click();

    const loading = page.getByRole('status');
    await expect(loading).toBeVisible();
    await expect(loading).toContainText('読み込んでいます');

    // 待っている間も、切り替える前と同じ高さを保つ。
    const during = (await tabs.boundingBox()).height;
    expect(during).toBeGreaterThanOrEqual(before - 1);

    // 届いたら消える。確保した高さも外す。
    await expect(loading).toBeHidden();
    await expect(tabs).toContainText('小項目合計');
  });

  /**
   * 集計表は届いた時点では素の幅のまま置かれ、断片の script が
   * fixTableHeader を呼ぶまで組み直されない。年別集計の表は本来の幅が
   * 5000px を超えるため、その一瞬だけタブの枠を突き抜けて画面の外まで
   * 伸びていた。
   *
   * 組み直しは重く、その間ブラウザの他の処理が止まるので、画面の絵を
   * 連続で撮っても掴めない。差し込まれた瞬間の状態を見る。
   */
  test('組み直す前の集計表は描かれない', async ({ page }) => {
    // 本来の幅が箱に収まらない表を返す。シードの表は狭くて再現しない。
    await page.route('**/summary/yearly/report*', (route) => {
      const columns = Array.from({ length: 40 }, (_, i) => `<th>項目${i + 1}</th>`).join('');
      const rows = Array.from({ length: 20 }, () =>
        '<tr>' + Array.from({ length: 41 }, () => '<td>1,234,567 円</td>').join('') + '</tr>').join('');

      return route.fulfill({
        contentType: 'text/html; charset=utf-8',
        body: `<script>
            $(function() {
              $('#tab-container').fixTableHeader({ table: '#table-selector', fixRows: 1, fixCols: 1 });
            });
          </script>
          <div id="tab-container">
            <table class="table" id="table-selector">
              <thead><tr><th>年月</th>${columns}</tr></thead>
              <tbody>${rows}</tbody>
            </table>
          </div>`,
      });
    });

    const year = new Date().getFullYear();
    await page.goto(`/summary/yearly?begin_year=${year - 2}&end_year=${year}&output_type=1&tab=line-chart`);
    await expect(page.locator('#tabs')).toBeVisible();

    // 差し込みは script の実行と同じ task で終わるため、後から見に行っても
    // 間に合わない。変化した時点で控える。
    await page.evaluate(() => {
      window.__states = [];
      const tabs = document.querySelector('#tabs');

      new MutationObserver(() => {
        const container = document.querySelector('#tab-container');

        if (!container) {
          return;
        }

        const table = container.querySelector('table');

        window.__states.push({
          visibility: getComputedStyle(container).visibility,
          containerWidth: container.getBoundingClientRect().width,
          tableWidth: table ? table.getBoundingClientRect().width : 0,
          // 組み直すと表は tablefix の作る div の中へ入る。箱の直下に表が
          // 居るのは、まだ素のままということ。
          raw: container.firstElementChild ? container.firstElementChild.tagName === 'TABLE' : false,
        });
      }).observe(tabs, { childList: true, subtree: true });
    });

    await page.getByRole('tab', { name: '集計表' }).click();
    await expect(page.locator('#tab-container')).toBeVisible();

    const states = await page.evaluate(() => window.__states);
    expect(states.length).toBeGreaterThan(0);

    // 素のまま、かつ箱に収まらない幅で置かれている間は一度も見えていないこと。
    // 組み直したあとは div の中でスクロールするので、幅が超えていてよい。
    const raw = states.filter((state) => state.raw && state.tableWidth > state.containerWidth + 1);
    expect(raw.length).toBeGreaterThan(0);

    for (const state of raw) {
      expect(state.visibility).toBe('hidden');
    }

    // 組み直しを持たない画面でも隠したままにならないこと。
    await expect(page.locator('#tab-container')).toHaveCSS('visibility', 'visible');
  });

  /**
   * 帯の検索は日別集計へ送る。そちらにタブはないので、意味のない値を
   * クエリに残さないこと。
   */
  test('行き先の違うフォームにはタブを付けない', async ({ page }) => {
    await page.goto(`/summary/monthly?date_month=${formatMonth(new Date())}`);

    await page.getByRole('tab', { name: 'ランキング' }).click();
    await expect(page.locator('#tabs')).toContainText('利用頻度ランキング');

    await page.getByPlaceholder('キーワード').fill('E2E スーパー');
    await page.getByPlaceholder('キーワード').press('Enter');

    await expect(page).toHaveURL(/\/summary\/daily\?/);
    expect(new URL(page.url()).searchParams.has('tab')).toBe(false);
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
  /**
   * 横軸のラベルは、収まる数ではなく「24 個」という決め打ちで間引いていた。
   * 24 個入るかどうかは幅の側の話で、入らないときは黙って重なる。20 年分では
   * 右端の 2 つが 14px 食い込み、1280px 幅では 23 個すべてが地続きに見えていた。
   *
   * 端末ごとに幅が違うので、個数ではなく実際のすき間を測る。
   */
  test('推移グラフの横軸ラベルが重ならない', async ({ page }) => {
    // 20 年分を作る。シードは 2 年分しか持たないため応答を差し替える。
    await page.route('**/summary/yearly/line-chart-data*', (route) => {
      const labels = [];
      const data = [];
      let y = 2006;
      let m = 7;

      for (let i = 0; i < 243; i++) {
        labels.push(`${y}/${String(m).padStart(2, '0')}`);
        data.push(100000 + i * 100);

        if (++m > 12) {
          m = 1;
          y += 1;
        }
      }

      return route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({ labels, series: [{ name: '交際費', data }] }),
      });
    });

    await page.goto('/summary/yearly?begin_year=2006&end_year=2026&output_type=2');
    await page.getByRole('tab', { name: '推移グラフ' }).click();
    await expect(page.locator('#yearly_trend_chart svg')).toBeVisible();

    const gap = await page.locator('#yearly_trend_chart').evaluate((node) => {
      // 間引かれたラベルも要素としては残るので、見えているものだけを測る。
      const boxes = [...node.querySelectorAll('.highcharts-xaxis-labels text')]
        .filter((text) => {
          const style = getComputedStyle(text);

          return style.visibility !== 'hidden' && parseFloat(style.opacity) > 0.01;
        })
        .map((text) => text.getBoundingClientRect())
        .sort((a, b) => a.left - b.left);

      let worst = Infinity;

      for (let i = 1; i < boxes.length; i++) {
        worst = Math.min(worst, boxes[i].left - boxes[i - 1].right);
      }

      return { count: boxes.length, worst };
    });

    expect(gap.count).toBeGreaterThan(2);
    // 直したあとの実測は 14.3px。隣と地続きに見えない幅として 8px を下限にする。
    expect(gap.worst).toBeGreaterThan(8);
  });

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

  /**
   * 年別集計のキーワードは、詳細検索で指定してタブの URL へ乗り、集計表と
   * 推移グラフの両方に効く。どちらか片方へ渡し忘れると、同じ検索条件のまま
   * 表とグラフに別の小項目が並ぶ。当たり方そのものは PHPUnit が見ているので、
   * ここは画面から指定した値が両方のタブまで届くか。
   *
   * 小項目の見出しは検索条件に関わらず全て並ぶ (利用者の持ち物の一覧なので)。
   * 絞り込めたかどうかは合計で見る。シードで場所が「E2E スーパー」なのは
   * 食料品だけなので、収入の側は 0 円になる。
   */
  test('年別集計をキーワードで絞り込める', async ({ page }) => {
    const year = new Date().getFullYear();

    const totals = () => reportTable(page)
      .locator('tfoot td')
      .evaluateAll((cells) => cells.slice(-3).map((cell) => cell.innerText.trim()));

    await page.goto(`/summary/yearly?begin_year=${year - 2}&end_year=${year}&output_type=2`);
    await reportTable(page).waitFor();

    const [, incomeBefore] = await totals();
    expect(incomeBefore).not.toBe('0 円');

    await page.getByText('詳細検索').click();
    await page.getByLabel('場所・用途').fill('E2E スーパー');

    // 帯の検索ボタンも「検索」なので、モーダルの中へ絞る。
    await page.locator('#search_modal').getByRole('button', { name: '検索' }).click();

    await expect(page).toHaveURL(/keyword=/);
    await reportTable(page).waitFor();

    const [expenseAfter, incomeAfter] = await totals();
    expect(incomeAfter).toBe('0 円');
    expect(expenseAfter).not.toBe('0 円');

    // 推移グラフも同じ条件で読む。系列は大項目なので、残るのは生活費だけ。
    await page.getByRole('tab', { name: '推移グラフ' }).click();
    await expect(page.locator('#yearly_trend_chart svg')).toBeVisible();

    const series = await chartEvaluate(page, (chart) => chart.series.map((entry) => entry.name));
    expect(series).toEqual(['生活費']);

    // 詳細検索を開き直すと、指定した語が残っていること。
    await page.getByText('詳細検索').click();
    await expect(page.getByLabel('場所・用途')).toHaveValue('E2E スーパー');
  });
});
