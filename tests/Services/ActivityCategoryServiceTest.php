<?php
namespace Tests\Services;

use DB;

use App\Libraries\Condition\PieChartCondition;
use App\Models\Activity;
use App\Models\ActivityCategory;
use Seeds\Test\ActivityCategoryItemTableSeeder;
use Tests\TestCase;

class ActivityCategoryServiceTest extends TestCase {
    private $activity_category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activity_category = app('App\Services\ActivityCategoryService');
    }

    /**
     * 支出は DB 上マイナス。構成グラフには正で渡す。円グラフは負の値の扇を
     * 描けず、推移グラフとも向きが食い違うため。
     */
    public function testAmountConstituentsFlipsExpenseToPositive()
    {
        DB::table('activities')->truncate();

        $this->createActivity(ActivityCategoryItemTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE, -3000);

        $result = $this->getAmountConstituents(ActivityCategory::BALANCE_TYPE_EXPENSE);

        $this->assertSame([3000], array_column($result, 'amount'));
    }

    /**
     * 収入はそのまま。反転するのは支出だけ。
     */
    public function testAmountConstituentsKeepsIncomeAsIs()
    {
        DB::table('activities')->truncate();

        $this->createActivity(ActivityCategoryItemTableSeeder::TYPE_VARIABLE_INCOME_CREDIT_DISABLE, 250000);

        $result = $this->getAmountConstituents(ActivityCategory::BALANCE_TYPE_INCOME);

        $this->assertSame([250000], array_column($result, 'amount'));
    }

    /**
     * 返金が上回って純額が逆を向いた大項目は落とす。扇にできない。
     */
    public function testAmountConstituentsSkipsRefundedCategory()
    {
        DB::table('activities')->truncate();

        $group = ActivityCategoryItemTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE;
        $this->createActivity($group, -1000);
        $this->createActivity($group, 1500);

        $this->assertSame([], $this->getAmountConstituents(ActivityCategory::BALANCE_TYPE_EXPENSE));
    }

    /**
     * 大項目名に一意制約はない。名前をキーにすると同名が片方消えるため、
     * 一覧で返して両方を残す。
     */
    public function testAmountConstituentsKeepsCategoriesWithTheSameName()
    {
        DB::table('activities')->truncate();

        // シードの大項目はどれも 'test'。変動支出と固定支出の 2 つを使う。
        $this->createActivity(ActivityCategoryItemTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE, -1000);
        $this->createActivity(ActivityCategoryItemTableSeeder::TYPE_CONSTANT_EXPENSE_CREDIT_DISABLE, -2000);

        $result = $this->getAmountConstituents(ActivityCategory::BALANCE_TYPE_EXPENSE);

        $this->assertSame(['test', 'test'], array_column($result, 'name'));
        $this->assertSame([2000, 1000], array_column($result, 'amount'));
    }

    /**
     * @param int $activity_category_item_id
     * @param int $amount
     */
    private function createActivity($activity_category_item_id, $amount)
    {
        Activity::create([
            'user_id' => $this->getUser()->id,
            'activity_date' => date('Y-m-d'),
            'activity_category_item_id' => $activity_category_item_id,
            'amount' => $amount,
            'credit_flag' => Activity::CREDIT_FLAG_UNUSE,
        ]);
    }

    /**
     * @param int $balance_type
     * @return array
     */
    private function getAmountConstituents($balance_type)
    {
        $condition = new PieChartCondition([
            'date_month' => date('Y-m'),
            'balance_type' => $balance_type,
        ]);

        return $this->activity_category->getAmountConstituents($this->getUser()->id, $condition);
    }
}
