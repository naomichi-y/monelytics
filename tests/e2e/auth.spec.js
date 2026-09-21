// @ts-check
const { test, expect } = require('@playwright/test');
const { USER, login } = require('./helpers');

test.describe('認証', () => {
  test('正しい資格情報でログインでき、ダッシュボードに入れる', async ({ page }) => {
    await login(page);

    // ログアウトはアカウントのドロップダウンの中にある。BS5 の開閉トグルは
    // <a> のままだが role="button" を持つため、リンクとしては引けない。
    await page.getByRole('button', { name: 'アカウント' }).click();
    await expect(page.getByRole('link', { name: 'ログアウト' })).toBeVisible();
  });

  test('誤ったパスワードはログイン画面に戻され、理由が表示される', async ({ page }) => {
    await page.goto('/user/login');

    await page.getByLabel('メールアドレス').fill(USER.email);
    await page.getByLabel('パスワード').fill('wrong-password');
    await page.getByRole('button', { name: 'ログイン' }).click();

    await expect(page).toHaveURL(/\/user\/login$/);
    await expect(page.getByText('ログインに失敗しました。')).toBeVisible();
  });

  test('ログイン状態を保持すると、セッションが切れても入り直せる', async ({ page, context }) => {
    await page.goto('/user/login');

    await page.getByLabel('メールアドレス').fill(USER.email);
    await page.getByLabel('パスワード').fill(USER.password);
    await page.getByLabel('ログイン状態を保持する').check();
    await page.getByRole('button', { name: 'ログイン' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);

    // セッション Cookie だけを捨てる。有効期限切れやブラウザを閉じた状態と
    // 同じで、残るのは remember_web_* の記憶 Cookie だけになる。
    const kept = (await context.cookies()).filter((cookie) => !/session/i.test(cookie.name));
    expect(kept.some((cookie) => cookie.name.startsWith('remember_web_'))).toBe(true);

    await context.clearCookies();
    await context.addCookies(kept);

    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/dashboard$/);
  });

  test('保持しなければ、セッションが切れるとログイン画面へ戻される', async ({ page, context }) => {
    await login(page);

    const kept = (await context.cookies()).filter((cookie) => !/session/i.test(cookie.name));
    await context.clearCookies();
    await context.addCookies(kept);

    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/user\/login$/);
  });

  test('未ログインで集計画面を開くとログイン画面へ送られる', async ({ page }) => {
    await page.goto('/summary/daily');

    await expect(page).toHaveURL(/\/user\/login$/);
  });

  test('ログアウトすると保護された画面に入れなくなる', async ({ page }) => {
    await login(page);

    await page.getByRole('button', { name: 'アカウント' }).click();
    await page.getByRole('link', { name: 'ログアウト' }).click();

    await page.goto('/summary/daily');
    await expect(page).toHaveURL(/\/user\/login$/);
  });
});
