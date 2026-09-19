// @ts-check
const { test, expect } = require('@playwright/test');
const { USER, login } = require('./helpers');

test.describe('認証', () => {
  test('正しい資格情報でログインでき、ダッシュボードに入れる', async ({ page }) => {
    await login(page);

    // ログアウトはアカウントのドロップダウンの中にある。
    await page.getByRole('link', { name: 'アカウント' }).click();
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

  test('未ログインで集計画面を開くとログイン画面へ送られる', async ({ page }) => {
    await page.goto('/summary/daily');

    await expect(page).toHaveURL(/\/user\/login$/);
  });

  test('ログアウトすると保護された画面に入れなくなる', async ({ page }) => {
    await login(page);

    await page.getByRole('link', { name: 'アカウント' }).click();
    await page.getByRole('link', { name: 'ログアウト' }).click();

    await page.goto('/summary/daily');
    await expect(page).toHaveURL(/\/user\/login$/);
  });
});
