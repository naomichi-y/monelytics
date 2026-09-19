// @ts-check
const { test, expect } = require('@playwright/test');
const { login, createVariableCost, rowByMarker, marker, formatMonth } = require('./helpers');

test.describe('変動収支', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('登録すると日別集計に出る', async ({ page }) => {
    const value = marker('E2E-CREATE');

    await createVariableCost(page, { groupName: '食料品', amount: 1234, marker: value });

    await expect(page.getByText('登録が完了しました。')).toBeVisible();

    await page.goto(`/summary/daily?date_month=${formatMonth(new Date())}`);

    const row = rowByMarker(page, value);
    await expect(row).toHaveCount(1);

    // 支出の科目なので、入力した正の額が負で記録される。
    await expect(row).toContainText('-1,234');
    await expect(row).toContainText('食料品');
  });

  test('編集モーダルで金額を変えると一覧に反映される', async ({ page }) => {
    const value = marker('E2E-EDIT');

    await createVariableCost(page, { groupName: '食料品', amount: 500, marker: value });
    await page.goto(`/summary/daily?date_month=${formatMonth(new Date())}`);

    const row = rowByMarker(page, value);
    await expect(row).toContainText('-500');

    await row.getByRole('button', { name: '編集' }).click();

    // モーダルはサーバから取得した HTML を差し込んで表示される。
    const modal = page.locator('.modal').filter({ hasText: '編集' }).last();
    await expect(modal.getByLabel('金額')).toBeVisible();

    await modal.getByLabel('金額').fill('-900');
    await modal.getByRole('button', { name: '更新' }).click();

    // 更新に成功すると画面ごと読み直される。
    await expect(rowByMarker(page, value)).toContainText('-900');
  });

  test('削除すると一覧から消える', async ({ page }) => {
    const value = marker('E2E-DELETE');

    await createVariableCost(page, { groupName: '日用品', amount: 777, marker: value });
    await page.goto(`/summary/daily?date_month=${formatMonth(new Date())}`);

    await expect(rowByMarker(page, value)).toHaveCount(1);

    await rowByMarker(page, value).getByRole('button', { name: '削除' }).click();

    const modal = page.locator('#delete-modal');
    await expect(modal).toBeVisible();
    await modal.getByRole('button', { name: '削除' }).click();

    await expect(page.getByText('削除が完了しました。')).toBeVisible();
    await expect(rowByMarker(page, value)).toHaveCount(0);
  });

  /**
   * 金額の検証は PHP 側にも単体テストがあるが、ここでは画面から送って
   * 差し戻されるところまでを見る。
   *
   * 小数を試さないのは、<input type="number"> の既定の step が 1 で、
   * ブラウザが送信そのものを止めてしまうため。画面から到達できるのは
   * 桁あふれの側だけになる。
   */
  test('保存できない桁の金額は差し戻される', async ({ page }) => {
    const value = marker('E2E-INVALID');

    await createVariableCost(page, { groupName: '食料品', amount: 3000000000, marker: value });

    await expect(page.getByText('範囲である必要があります')).toBeVisible();

    await page.goto(`/summary/daily?date_month=${formatMonth(new Date())}`);
    await expect(rowByMarker(page, value)).toHaveCount(0);
  });
});
