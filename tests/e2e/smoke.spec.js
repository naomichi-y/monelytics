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

/**
 * 項目名は入力欄の左に右寄せで並ぶこと。Bootstrap 3 の .form-horizontal が
 * 768px 以上で行っていた配置で、5 には同じ仕組みがない。左寄せのままだと
 * ラベルと入力欄の対応が読み取りにくい。
 */
test('入力画面の項目名が入力欄の側へ右寄せで並ぶ', async ({ page }) => {
  await login(page);
  await page.goto('/dashboard');

  // 一覧の見出しにも同じ文言があるため、入力欄に紐づくラベルで引く。
  const label = page.locator('label[for="activity_date"]');
  const input = page.locator('#activity_date');

  await expect(label).toHaveCSS('text-align', 'right');

  const [labelBox, inputBox] = [await label.boundingBox(), await input.boundingBox()];

  // 行の中で縦にも揃っていること (ラベルだけ上に付かない)。
  expect(Math.abs((labelBox.y + labelBox.height / 2) - (inputBox.y + inputBox.height / 2)))
    .toBeLessThanOrEqual(3);
});

/**
 * 編集モーダルは 600px。Bootstrap 5 の既定は 500px で、日付や金額の欄まで
 * 同じ幅に詰まって並ぶ。
 */
test('編集モーダルの幅が 600px ある', async ({ page }) => {
  await login(page);
  await page.goto(`/summary/daily?date_month=${formatMonth(new Date())}`);

  await page.getByRole('button', { name: '編集' }).first().click();

  const dialog = page.locator('.modal.show .modal-dialog');
  await expect(dialog).toBeVisible();

  expect((await dialog.boundingBox()).width).toBe(600);
});

/**
 * 見出しの横に置くボタンは、隣の月の選択より小さいこと。sandstone 5 は .btn へ
 * 文字の大きさと行の高さを直接書くため、--bs-btn-font-size だけでは縮まない。
 */
test('見出し横のボタンが月の選択より小さい', async ({ page }) => {
  await login(page);
  await page.goto('/summary/daily');

  const button = await page.locator('#open_condition').boundingBox();
  const select = await page.locator('#date_month').boundingBox();

  expect(button.height).toBeLessThan(select.height);
});

/**
 * トップページの紹介文は白抜きで、parallax.js が敷く背景写真があって初めて
 * 読める。1.4.2 は $(document).on('ready', ...) で自動初期化するが、jQuery 3 で
 * この呼び出し方は削除されており、放っておくと本文が真っ白な画面に消える。
 * ログインしていないときだけ出る画面のため、巡回のほうでは通らない。
 */
test('トップページの紹介文に背景が敷かれる', async ({ page }) => {
  await page.goto('/');

  await expect(page.getByRole('heading', { name: "Let's get started" })).toBeVisible();

  // 背景は body の裏に固定で敷かれる img として作られる。
  const slider = page.locator('.parallax-mirror .parallax-slider').first();
  await expect(slider).toHaveAttribute('src', /\/assets\/images\/parallax\//);
});

/**
 * 狭い画面では responsive_table.js が見出しを各行に複製し、表を
 * 「見出し / 値」の 2 列に畳む。jQuery 3 で削除された .context を読んでいた
 * ため処理は途中で例外になり、見出しの付かない値だけの表になっていた。
 *
 * 画面幅だけを狭める。日付入力の種類はサーバが UA で決めるため、ここでは
 * デスクトップのままにしておく。
 */
/**
 * 日付入力のカレンダー。祝日は内閣府の一覧を /holidays から読むが、配布元へ
 * 出られないときも土日の色分けだけは効く。jquery-ui-dist の base テーマも
 * 落としてあり、日付は枠のない文字として並ぶ。
 */
test('カレンダーで土日に印が付き、日付に枠を描かない', async ({ page }) => {
  await login(page);
  await page.goto('/cost/variable/create');

  await page.locator('.date-picker').first().click();

  const calendar = page.locator('.ui-datepicker').first();
  await expect(calendar).toBeVisible();

  await expect(calendar.locator('td.calendar-sunday')).not.toHaveCount(0);
  await expect(calendar.locator('td.calendar-saturday')).not.toHaveCount(0);

  const day = calendar.locator('td a').first();
  await expect(day).toHaveCSS('border-top-width', '0px');
});

test.describe('狭い画面の一覧', () => {
  test.use({ viewport: { width: 390, height: 844 } });

  test('行ごとに見出しが付き、見出しと値が 4 対 6 で並ぶ', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));

    await login(page);
    await page.goto('/summary/daily');

    expect(errors, errors.join('\n')).toEqual([]);

    const table = page.locator('table').first();
    const rows = await table.locator('tbody tr').count();

    // 8 列ぶんの見出しが行数だけ並ぶ。
    expect(rows).toBeGreaterThan(1);
    await expect(table.locator('thead th')).toHaveCount(8 * rows);

    // 列幅の指定 (colgroup) が残っていると、どちらも 50px ほどに潰れる。
    const head = await table.locator('thead').boundingBox();
    const body = await table.locator('tbody').boundingBox();

    expect(head.width / (head.width + body.width)).toBeCloseTo(0.4, 1);
  });

  /**
   * 見出しの揃えは件をまたいで同じであること。元からある 1 件目の見出しは
   * text-center を持ち、複製した 2 件目以降は持たない。BS5 の text-* は
   * !important 付きのため、responsive_table.css の左寄せに勝ってしまい、
   * 1 件目だけ中央に寄る。
   */
  test('件ごとに見出しの揃えが変わらない', async ({ page }) => {
    await login(page);
    await page.goto('/summary/daily');

    const aligns = await page.locator('table thead th').evaluateAll(
      (cells) => [...new Set(cells.map((cell) => getComputedStyle(cell).textAlign))]
    );

    expect(aligns).toEqual(['left']);
  });
});
