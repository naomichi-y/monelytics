// @ts-check
const { test, expect } = require('@playwright/test');
const { USER, login } = require('./helpers');

test.describe('プロフィール', () => {
  test('パスワードを変えるには現在のパスワードが要る', async ({ page }) => {
    await login(page);
    await page.goto('/user');

    await page.getByLabel('現在のパスワード').fill('wrong-password');
    await page.getByLabel('新しいパスワード', { exact: true }).fill('new-password');
    await page.getByLabel('新しいパスワード (再入力)').fill('new-password');
    await page.getByRole('button', { name: '更新' }).click();

    await expect(page.getByText('現在のパスワードが正しくありません。')).toBeVisible();

    // 画面に理由が出ただけで、実際には変わっていないことまで見る。
    // 検証を通したあとに弾いているため、通す側の順番を間違えると
    // 先にパスワードだけが書き換わる。
    await page.goto('/user/logout');
    await login(page);
  });

  test('項目名が折り返さない', async ({ page }) => {
    await login(page);
    await page.goto('/user');

    // 「新しいパスワード (再入力)」は 1 行に 167px 要る。項目名の列が
    // 他の画面と同じ col-md-4 だと 162px しかなく、「入力)」だけが次の行に
    // 落ちていた。幅は画面ごとに変わるため、px ではなく行数で見る。
    for (const name of ['名前', 'メールアドレス', '現在のパスワード', '新しいパスワード', '新しいパスワード (再入力)']) {
      const lines = await page.getByText(name, { exact: true }).evaluate((label) => {
        const range = document.createRange();
        range.selectNodeContents(label);

        return range.getClientRects().length;
      });

      expect(lines, `${name} が折り返している`).toBe(1);
    }
  });

  test('パスワードを変えないなら現在のパスワードは聞かれない', async ({ page }) => {
    await login(page);
    await page.goto('/user');

    // 名前とメールアドレスだけを直したい人に、毎回パスワードを
    // 入力させないこと。値はシードのまま送り、他のテストが見る
    // データを動かさない。
    await page.getByRole('button', { name: '更新' }).click();

    await expect(page.getByText('更新が完了しました。')).toBeVisible();
  });

  test('変更したパスワードで入り直せる', async ({ page }) => {
    await login(page);
    await page.goto('/user');

    const changed = 'e2e-password-2';

    await page.getByLabel('現在のパスワード').fill(USER.password);
    await page.getByLabel('新しいパスワード', { exact: true }).fill(changed);
    await page.getByLabel('新しいパスワード (再入力)').fill(changed);
    await page.getByRole('button', { name: '更新' }).click();
    await expect(page.getByText('更新が完了しました。')).toBeVisible();

    await page.goto('/user/logout');
    await page.goto('/user/login');
    await page.getByLabel('メールアドレス').fill(USER.email);
    await page.getByLabel('パスワード').fill(changed);
    await page.getByRole('button', { name: 'ログイン' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);

    // 後続のテストはシードのパスワードでログインする。変えたままにすると
    // このテストの順番次第で他が落ちるため、必ず戻す。
    await page.goto('/user');
    await page.getByLabel('現在のパスワード').fill(changed);
    await page.getByLabel('新しいパスワード', { exact: true }).fill(USER.password);
    await page.getByLabel('新しいパスワード (再入力)').fill(USER.password);
    await page.getByRole('button', { name: '更新' }).click();
    await expect(page.getByText('更新が完了しました。')).toBeVisible();

    await page.goto('/user/logout');
    await login(page);
  });
});
