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
    const width = (cell) => cell.getBoundingClientRect().width;

    return {
      table: width(root.querySelector('table')),
      name: width(cells[0]),
      bar: width(cells[1]),
      current: width(cells[2]),
      previous: width(cells[3]),
      difference: width(cells[4]),
    };
  });
}

/**
 * 合計と小項目の数字を全て 7 桁にしたときに、各セルと表が何 px はみ出すか。
 *
 * シードの額は 4、5 桁で、そのままでは狭すぎる列でも収まってしまう。
 * 数字の列は中身の幅で決まるため、1 行だけ書き換えても列の幅は測れない。
 *
 * @param {import('@playwright/test').Page} page
 */
function overflowWithSevenDigits(page) {
  return page.locator('.variable-expense').evaluate((root) => {
    const unit = (value) => `<span class="money">${value} <span class="unit">円</span></span>`;

    for (const row of root.querySelectorAll('.total, tbody tr')) {
      const numbers = row.querySelectorAll('.number');

      numbers[0].innerHTML = unit('9,999,999');
      numbers[1].innerHTML = unit('9,999,999');
      numbers[2].innerHTML = unit('-9,999,999');
    }

    const table = root.querySelector('table');
    const content = root.getBoundingClientRect().right - parseFloat(getComputedStyle(root).paddingRight);

    return {
      cells: [...root.querySelectorAll('td, th')].map((cell) => Math.max(0, cell.scrollWidth - cell.clientWidth)).filter((px) => px > 0),
      table: Math.max(0, Math.round(table.getBoundingClientRect().right - content)),
    };
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

    await expect(panel.getByText('までの額')).toBeVisible();
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
            // 行ではなく棒のセルの中身で測る。1 行目だけは合計の線との間を
            // 空けてあり、行の高さで比べるとそこが違って見える。
            rowHeight: (() => {
              const td = tr.children[1];
              const style = getComputedStyle(td);

              return td.getBoundingClientRect().height - parseFloat(style.paddingTop) - parseFloat(style.paddingBottom);
            })(),
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
    // 端数 (22.5px と 22px) は丸めで 1 ずれるので、1px までは同じとみなす。
    const heights = shape.rows.map((row) => row.rowHeight);
    expect(Math.max(...heights) - Math.min(...heights)).toBeLessThanOrEqual(1);

    // 同じ赤を薄めた色。別の色にすると種類の違いに読める。
    expect(shape.currentColor).toBe('rgb(236, 87, 72)');
    expect(shape.previousColor).toBe('rgb(246, 179, 173)');

    await expect(panel.getByText('(薄い棒も同じ)')).toBeVisible();
  });

  /**
   * 数字の列が並ぶのに見出しが無く、どれが今月でどれが差なのかが読めなかった。
   * 見出しは合計の上の 1 行だけに置き、合計と小項目の数字をその下に揃える。
   * 小項目にも見出しを付けたときは、同じ見出しが 2 度並んで被って見えた。
   *
   * 列の右端を揃える。数字は右寄せなので、ずれると隣の列の見出しに見える。
   */
  test('今月の変動支出の見出しは 1 行で、合計と小項目の数字がその下に揃う', async ({ page }) => {
    const panel = page.locator('#variable_expense');
    await expect(panel.locator('.variable-expense')).toBeVisible();

    await expect(panel.locator('th[scope="col"]')).toHaveText(['今月', '先月', '先月との差']);

    const edges = await panel.evaluate((node) => {
      // セルではなく文字の右端で比べる。セルは padding の分だけ外にある。
      const right = (el) => {
        const range = document.createRange();
        range.selectNodeContents(el);

        return Math.round(range.getBoundingClientRect().right);
      };
      const rows = [node.querySelector('.total'), ...node.querySelectorAll('tbody tr')];

      return Array.from(node.querySelectorAll('th[scope="col"]')).map((th, i) => [
        right(th),
        ...rows.map((tr) => right(tr.querySelectorAll('.number')[i].querySelector('.money'))),
      ]);
    });

    for (const column of edges) {
      expect(new Set(column).size).toBe(1);
    }

    // 小項目の行にも先月の額が円付きで出る。
    for (const row of await panel.locator('tbody tr').all()) {
      await expect(row.locator('.number')).toHaveText([/円$/, /円$/, /円$/]);
    }
  });

  /**
   * 合計は今月だけ字が大きい。上で揃えると先月と差が今月の上側に、下で
   * 揃えると下側に寄り、同じ行の値に見えにくかった。縦の中央で揃える。
   */
  test('変動支出の合計は縦の中央で揃う', async ({ page }) => {
    const total = page.locator('#variable_expense .total');
    await expect(total).toBeVisible();

    const middles = await total.locator('.money').evaluateAll((els) => els.map((el) => {
      const box = el.getBoundingClientRect();

      return Math.round(box.top + box.height / 2);
    }));

    expect(middles).toHaveLength(3);
    expect(Math.max(...middles) - Math.min(...middles)).toBeLessThanOrEqual(1);
  });

  /**
   * 名前の列は名前の幅だけ取る。表幅の 30% に固定していたときは、短い名前
   * でも名前と棒の間が 150px 以上空いた。逆に余白を取らないと棒が名前に
   * 接して見えた。
   */
  test('小項目名と棒の間は空きすぎず詰まりすぎない', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto('/dashboard');
    await expect(page.locator('.variable-expense')).toBeVisible();

    const gaps = await page.locator('.variable-expense tbody tr').evaluateAll((rows) => rows.map((tr) => {
      const name = tr.querySelector('.group-name a').getBoundingClientRect();
      const bar = tr.querySelector('.bar').getBoundingClientRect();

      return Math.round(bar.left - name.right);
    }));

    // 列の幅は最も長い名前で決まるため、最も近い行で測る。
    expect(Math.min(...gaps)).toBeGreaterThanOrEqual(24);
    expect(Math.min(...gaps)).toBeLessThanOrEqual(48);
  });

  /**
   * かんたん入力の送り先は cost/variable で、作られるのは変動収支。固定収支の
   * 小項目を選べてしまうと、選んだとおりに登録されない。
   */
  /**
   * 数字の列は中身の幅だけ取り、余りは棒へ回す。読みたいのは小項目どうしの
   * 多い少ないで、そこが一番狭い列だった。
   */
  test('広く取れるときは棒を長くする', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto('/dashboard');
    await expect(page.locator('.variable-expense')).toBeVisible();

    const wide = await columns(page);

    // 棒が一番広い列であること。ここが読みたいものなので。
    for (const other of [wide.name, wide.current, wide.previous, wide.difference]) {
      expect(wide.bar).toBeGreaterThan(other);
    }

    expect(await overflowWithSevenDigits(page)).toEqual({ cells: [], table: 0 });
  });

  /**
   * 狭いところでは、数字が 3 列並んでも 7 桁が枠に収まること。以前は数字の
   * 列を 26% ずつ固定していて、3 列にすると名前と棒に 22% しか残らなかった。
   * 長い小項目名は省略して、数字を押し出さない。
   *
   * 部品の幅で切り替えるため、700px の画面 (多段組みにならず部品が広い) でも
   * 見る (@see 「広く取れるときは棒を長くする」)。
   */
  for (const width of [414, 700]) {
    test(`狭くても 7 桁と長い小項目名が枠に収まる (${width}px)`, async ({ page }) => {
      await page.setViewportSize({ width, height: 900 });
      await page.goto('/dashboard');
      await expect(page.locator('.variable-expense')).toBeVisible();

      await page.locator('.variable-expense tbody .group-name a').first().evaluate((a) => {
        a.textContent = '小項目名は三十二文字まで付けられるのでとても長い名前を付けた場合';
      });

      expect(await overflowWithSevenDigits(page)).toEqual({ cells: [], table: 0 });
      expect((await columns(page)).bar).toBeGreaterThan(0);
    });
  }

  test('かんたん入力の小項目は変動収支だけ', async ({ page }) => {
    const options = page.locator('#activity_category_item_id option');

    await expect(options.filter({ hasText: '食料品' })).toHaveCount(1);
    await expect(options.filter({ hasText: '臨時ボーナス' })).toHaveCount(1);

    // 家賃と給与は固定収支。
    await expect(options.filter({ hasText: '家賃' })).toHaveCount(0);
    await expect(options.filter({ hasText: '給与' })).toHaveCount(0);
  });

  /**
   * 2 つの部品はどちらも ajax で取りに行く。以前は素の $.get で、応答が
   * 届くまで枠が空のままだった。読み込んでいるのか、失敗したのか、そもそも
   * 出るものが無いのかが画面から区別できない。集計のタブは同じ理由で
   * 待っていることを出しており (@see summary.spec.js の「グラフを取りに
   * 行っている間もタブは空にならない」)、そちらへ揃えた。
   */
  test('部品を取りに行っている間も枠は空にならない', async ({ page }) => {
    // 応答を遅らせないと、届いたあとの状態しか観測できない。
    await page.route('**/gadget/**', async (route) => {
      await new Promise((resolve) => setTimeout(resolve, 1000));

      await route.continue();
    });

    await page.goto('/dashboard');

    for (const id of ['#variable_expense', '#activity_history']) {
      await expect(page.locator(`${id} .tab-loading`)).toHaveText('読み込んでいます…');
    }

    // 届いたら読み込み中の表示は残らない。
    await expect(page.locator('#variable_expense .variable-expense')).toBeVisible();
    await expect(page.locator('#activity_history table')).toBeVisible();
    await expect(page.locator('.tab-loading')).toHaveCount(0);
  });

  /**
   * 失敗したときに空へ戻さない。戻すと読み込む前と同じ見た目になり、
   * 記録が無いのと区別が付かない。片方が落ちても、もう片方は出る。
   */
  test('部品が取れなかったときは読み込めなかったと出る', async ({ page }) => {
    await page.route('**/gadget/variable-expense', (route) => route.fulfill({ status: 500, body: '' }));

    await page.goto('/dashboard');

    await expect(page.locator('#variable_expense .panel-error')).toHaveText('読み込めませんでした。');
    await expect(page.locator('#activity_history table')).toBeVisible();
  });

  test('かんたん入力から登録できる', async ({ page }) => {
    await page.locator('#activity_category_item_id').selectOption({ label: '食料品' });
    await page.locator('#amount').fill('4321');
    await page.locator('#location').fill('E2E-DASHBOARD');
    await page.getByRole('button', { name: '登録' }).click();

    await expect(page.getByText('登録が完了しました。')).toBeVisible();
  });
});
