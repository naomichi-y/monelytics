<?php
namespace Tests\Controllers;

use Seeds\Test\ActivityCategoryGroupTableSeeder;
use Tests\TestCase;

class DashboardControllerTest extends TestCase {
    public function testIndex()
    {
      $this->assertUserOnlyContent('GET', '/dashboard');
    }

    /**
     * かんたん入力の送り先は cost/variable で、作られるのは変動収支。
     * 固定収支の科目を選べてしまうと、選んだとおりに登録されない。
     */
    public function testIndexOffersVariableCostGroupsOnly()
    {
        $this->login();
        $response = $this->call('GET', '/dashboard');
        $this->logout();

        $response->assertOk();

        $variable = [
            ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_ENABLE,
            ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE,
            ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_INCOME_CREDIT_ENABLE,
            ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_INCOME_CREDIT_DISABLE
        ];

        // 科目名はシードでどれも同じなので、選択肢は値で見分ける。
        preg_match('/<select[^>]*activity_category_group_id.*?<\/select>/s', $response->getContent(), $matches);
        $this->assertNotEmpty($matches, 'かんたん入力に科目の選択欄がない');

        preg_match_all('/<option value="(\d+)"/', $matches[0], $options);
        $offered = array_map('intval', $options[1]);

        $this->assertNotEmpty($offered, '選べる科目が 1 つもない');
        $this->assertSame([], array_diff($offered, $variable), '固定収支の科目が混ざっている');
    }
}
