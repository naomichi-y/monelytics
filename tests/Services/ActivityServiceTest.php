<?php
namespace Tests\Services;

use DB;

use App\Libraries\Condition\MonthlySummaryCondition;
use App\Libraries\Condition\YearlySummaryCondition;
use App\Libraries\Condition\YearlyTrendCondition;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Services\ActivityService;
use Seeds\Test\ActivityCategoryGroupTableSeeder;
use Seeds\Test\ActivityCategoryTableSeeder;
use Tests\TestCase;

class ActivityServiceTest extends TestCase {
    private $activity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activity = app('App\Services\ActivityService');
    }

    public function testCreateVariableCosts()
    {
        $user = $this->getUser();
        $params = [
            'activity_date' => [date('Y-m-d')],
            'activity_category_group_id' => [ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_ENABLE],
            'amount' => [100],
            'location' => [''],
            'content' => [''],
            'credit_flag' => [Activity::CREDIT_FLAG_USE],
            'special_flag' => [Activity::SPECIAL_FLAG_USE]
        ];

        $before_count = Activity::count();
        $this->assertEquals($this->activity->createVariableCosts($user->id, $params), true);
        $this->assertEquals($before_count, Activity::count() - 1);

        $activity = Activity::limit(1)->orderBy('id', 'desc')->get()->first();
        $this->assertEquals($activity->amount, -100);

        $params['activity_category_group_id'] = [ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_INCOME_CREDIT_ENABLE];
        $params['amount'] = [-100];
        $this->assertEquals($this->activity->createVariableCosts($user->id, $params), true);
        $activity = Activity::limit(1)->orderBy('id', 'desc')->get()->first();
        $this->assertEquals($activity->amount, 100);
    }

    /**
     * 変動収支だけでなく固定収支の科目も比較する。
     */
    public function testMonthlyComparisonCoversVariableAndConstantCost()
    {
        $variable_expense = ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE;
        $constant_expense = ActivityCategoryGroupTableSeeder::TYPE_CONSTANT_EXPENSE_CREDIT_DISABLE;
        $variable_income = ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_INCOME_CREDIT_DISABLE;

        $result = $this->createComparisonFixture();

        // 支出は負で記録されている。使った額が増えたら正になるよう符号を揃える。
        $this->assertSame(200, $result['groups'][$variable_expense]);

        // 固定収支も対象にする (以前は変動収支だけを比較していた)。
        $this->assertSame(-50, $result['groups'][$constant_expense]);

        $this->assertSame(100, $result['groups'][$variable_income]);
    }

    /**
     * 科目ごとに加えて、収入合計・支出合計・合計も比較する。
     */
    public function testMonthlyComparisonReturnsTotals()
    {
        $result = $this->createComparisonFixture();

        // 収入 1,000 -> 2,000
        $this->assertSame(100, $result['totals']['income']);

        // 支出 2,000 -> 3,500。科目と同じく、使った額が増えたら正にする。
        $this->assertSame(75, $result['totals']['expense']);

        // 合計 -1,000 -> -1,500
        $this->assertSame(-50, $result['totals']['total']);
    }

    /**
     * 現金とクレジットに分かれた記録は、科目ごとに合算してから比べる。
     */
    public function testMonthlyComparisonSumsCashAndCredit()
    {
        $month = $this->monthBefore(2);
        $previous_month = $this->monthBefore(3);
        $group = ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_ENABLE;

        $this->createActivity($group, $previous_month . '-05', -1000, Activity::CREDIT_FLAG_UNUSE);
        $this->createActivity($group, $previous_month . '-05', -1000, Activity::CREDIT_FLAG_USE);
        $this->createActivity($group, $month . '-05', -2000, Activity::CREDIT_FLAG_UNUSE);
        $this->createActivity($group, $month . '-05', -1000, Activity::CREDIT_FLAG_USE);

        $result = $this->getMonthlyComparison($month);

        // 2,000 -> 3,000
        $this->assertSame(50, $result['groups'][$group]);
    }

    /**
     * 収入と支出の振り分けは科目の収支タイプではなく、集計表の表示と同じく
     * 現金・クレジットごとの小計の符号で決める。
     */
    public function testMonthlyComparisonSplitsTotalsBySubtotalSign()
    {
        $month = $this->monthBefore(2);
        $previous_month = $this->monthBefore(3);
        $group = ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_ENABLE;

        $this->createActivity($group, $previous_month . '-05', -1000, Activity::CREDIT_FLAG_USE);
        $this->createActivity($group, $previous_month . '-05', 1000, Activity::CREDIT_FLAG_UNUSE);

        // 支出の科目でも、現金の小計が返金で正になったら収入として数える。
        $this->createActivity($group, $month . '-05', -2000, Activity::CREDIT_FLAG_USE);
        $this->createActivity($group, $month . '-05', 3000, Activity::CREDIT_FLAG_UNUSE);

        $result = $this->getMonthlyComparison($month);

        // 収入 1,000 -> 3,000
        $this->assertSame(200, $result['totals']['income']);

        // 支出 1,000 -> 2,000
        $this->assertSame(100, $result['totals']['expense']);
    }

    /**
     * 前の期間に金額がなければ比率を出せないため、その項目は返さない。
     */
    public function testMonthlyComparisonSkipsItemWithoutPreviousAmount()
    {
        $month = $this->monthBefore(2);
        $group = ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE;

        $this->createActivity($group, $month . '-05', -1000);

        $result = $this->getMonthlyComparison($month);

        $this->assertArrayNotHasKey($group, $result['groups']);
        $this->assertSame([], $result['totals']);
    }

    /**
     * 過去の月は月末までを対象にし、前後の月の記録は混ぜない。
     */
    public function testMonthlyComparisonUsesWholeMonthForPastMonth()
    {
        $month = $this->monthBefore(2);
        $previous_month = $this->monthBefore(3);
        $next_month = $this->monthBefore(1);
        $group = ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE;

        $this->createActivity($group, $previous_month . '-01', -500);
        $this->createActivity($group, $this->lastDayOf($previous_month), -500);
        $this->createActivity($group, $month . '-01', -1000);
        $this->createActivity($group, $this->lastDayOf($month), -1000);

        // 翌月の記録が混ざると比率が変わる。
        $this->createActivity($group, $next_month . '-01', -9999);

        $result = $this->getMonthlyComparison($month);

        // 1,000 -> 2,000
        $this->assertSame(100, $result['groups'][$group]);
    }

    /**
     * 前の期間を決められない条件では比較しない。
     */
    public function testMonthlyComparisonReturnsEmptyForUncomparableCondition()
    {
        $month = $this->monthBefore(2);
        $empty = ['groups' => [], 'totals' => []];

        // 詳細検索で任意の日付が指定されている
        $this->assertSame($empty, $this->getMonthlyComparison($month, [
            'begin_date' => $month . '-01',
            'end_date' => $month . '-10',
        ]));

        // 未来の月
        $next_month = date('Y-m', strtotime(date('Y-m-01') . ' +1 month'));
        $this->assertSame($empty, $this->getMonthlyComparison($next_month));

        // 年月の形式ではない (「すべて」を選んだ場合)
        $this->assertSame($empty, $this->getMonthlyComparison('all'));
    }

    /**
     * 支出は符号を反転し、使った額が多いほど線が上に来るようにする。
     */
    public function testYearlyTrendDrawsExpenseUpward()
    {
        $this->prepareTrendFixture();

        $this->createActivity(ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE, '2020-05-01', -1000);
        $this->createActivity(ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_INCOME_CREDIT_DISABLE, '2020-05-01', 2000);

        $result = $this->getYearlyTrend(2020, 2020, YearlySummaryCondition::OUTPUT_TYPE_YEARLY);

        // 年単位のラベルは一度配列のキーにするため、数値文字列が int になる
        // (月単位の '2020/01' は数値ではないので文字列のまま)。
        $this->assertSame([2020], $result['labels']);
        $this->assertSame([
            ['name' => '変動支出', 'data' => [1000]],
            ['name' => '変動収入', 'data' => [2000]],
        ], $result['series']);
    }

    /**
     * 記録のない期間は 0 ではなく null にする。
     */
    public function testYearlyTrendUsesNullForPeriodWithoutRecord()
    {
        $this->prepareTrendFixture();

        $this->createActivity(ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE, '2020-05-01', -1000);
        $this->createActivity(ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_INCOME_CREDIT_DISABLE, '2021-05-01', 2000);

        $result = $this->getYearlyTrend(2020, 2021, YearlySummaryCondition::OUTPUT_TYPE_YEARLY);

        $this->assertSame([2020, 2021], $result['labels']);
        $this->assertSame([
            ['name' => '変動支出', 'data' => [1000, null]],
            ['name' => '変動収入', 'data' => [null, 2000]],
        ], $result['series']);
    }

    /**
     * 横軸の刻みは詳細検索の出力形式に従う。
     */
    public function testYearlyTrendFollowsOutputType()
    {
        $this->prepareTrendFixture();

        $group = ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE;
        $this->createActivity($group, '2020-01-15', -1000);
        $this->createActivity($group, '2020-03-15', -2000);

        // 月単位では記録のあった月だけを並べる。
        $result = $this->getYearlyTrend(2020, 2020, YearlySummaryCondition::OUTPUT_TYPE_MONTHLY);
        $this->assertSame(['2020/01', '2020/03'], $result['labels']);
        $this->assertSame([['name' => '変動支出', 'data' => [1000, 2000]]], $result['series']);

        // 年単位では 1 点にまとまる。
        $result = $this->getYearlyTrend(2020, 2020, YearlySummaryCondition::OUTPUT_TYPE_YEARLY);
        $this->assertSame([2020], $result['labels']);
        $this->assertSame([['name' => '変動支出', 'data' => [3000]]], $result['series']);
    }

    /**
     * 収支タイプを指定した場合は、その科目だけを対象にする。
     */
    public function testYearlyTrendFiltersByBalanceType()
    {
        $this->prepareTrendFixture();

        $this->createActivity(ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE, '2020-05-01', -1000);
        $this->createActivity(ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_INCOME_CREDIT_DISABLE, '2020-05-01', 2000);

        $result = $this->getYearlyTrend(2020, 2020, YearlySummaryCondition::OUTPUT_TYPE_YEARLY, ActivityCategory::BALANCE_TYPE_EXPENSE);

        $this->assertSame([['name' => '変動支出', 'data' => [1000]]], $result['series']);
    }

    /**
     * 期間を決められない場合と、記録がない場合はグラフを描かない。
     */
    public function testYearlyTrendReturnsEmptyWithoutDrawableData()
    {
        $this->prepareTrendFixture();

        $this->createActivity(ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE, '2020-05-01', -1000);

        $empty = ['labels' => [], 'series' => []];

        // 開始が終了より後
        $this->assertSame($empty, $this->getYearlyTrend(2021, 2020, YearlySummaryCondition::OUTPUT_TYPE_YEARLY));

        // 年が指定されていない
        $this->assertSame($empty, $this->getYearlyTrend(null, null, YearlySummaryCondition::OUTPUT_TYPE_YEARLY));

        // 記録のない年
        $this->assertSame($empty, $this->getYearlyTrend(2019, 2019, YearlySummaryCondition::OUTPUT_TYPE_YEARLY));
    }

    /**
     * 比較の基準となる 2 つの期間に記録を作り、比較結果を返す。
     *
     * 前の期間: 変動支出 -1,000 / 固定支出 -1,000 / 変動収入 1,000
     * 対象の期間: 変動支出 -3,000 / 固定支出 -500 / 変動収入 2,000
     *
     * @return array
     */
    private function createComparisonFixture()
    {
        $month = $this->monthBefore(2);
        $previous_month = $this->monthBefore(3);

        $variable_expense = ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE;
        $constant_expense = ActivityCategoryGroupTableSeeder::TYPE_CONSTANT_EXPENSE_CREDIT_DISABLE;
        $variable_income = ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_INCOME_CREDIT_DISABLE;

        $this->createActivity($variable_expense, $previous_month . '-05', -1000);
        $this->createActivity($constant_expense, $previous_month . '-05', -1000);
        $this->createActivity($variable_income, $previous_month . '-05', 1000);

        $this->createActivity($variable_expense, $month . '-05', -3000);
        $this->createActivity($constant_expense, $month . '-05', -500);
        $this->createActivity($variable_income, $month . '-05', 2000);

        return $this->getMonthlyComparison($month);
    }

    /**
     * 年を指定して調べるため、初期データ (今日の日付で作られる) を消す。
     * あわせて、系列名で見分けられるよう科目に別々の名前を付ける。
     */
    private function prepareTrendFixture()
    {
        DB::table('activities')->truncate();

        ActivityCategory::where('id', '=', ActivityCategoryTableSeeder::TYPE_VARIABLE_EXPENSE)
            ->update(['category_name' => '変動支出']);
        ActivityCategory::where('id', '=', ActivityCategoryTableSeeder::TYPE_VARIABLE_INCOME)
            ->update(['category_name' => '変動収入']);
    }

    /**
     * @param string $date_month 'all' など、年月以外も渡せる
     * @param array $fields 詳細検索の指定
     * @return array
     */
    private function getMonthlyComparison($date_month, array $fields = [])
    {
        $condition = new MonthlySummaryCondition(['date_month' => $date_month] + $fields);

        return $this->activity->getMonthlyComparison($this->getUser()->id, $condition);
    }

    /**
     * @param int|null $begin_year
     * @param int|null $end_year
     * @param int $output_type
     * @param int|null $balance_type
     * @return array
     */
    private function getYearlyTrend($begin_year, $end_year, $output_type, $balance_type = null)
    {
        $condition = new YearlyTrendCondition([
            'begin_year' => $begin_year,
            'end_year' => $end_year,
            'output_type' => $output_type,
            'balance_type' => $balance_type,
        ]);

        return $this->activity->getYearlyTrend($this->getUser()->id, $condition);
    }

    /**
     * @param int $activity_category_group_id
     * @param string $activity_date
     * @param int $amount
     * @param int $credit_flag
     * @return Activity
     */
    private function createActivity($activity_category_group_id, $activity_date, $amount, $credit_flag = Activity::CREDIT_FLAG_UNUSE)
    {
        return Activity::create([
            'user_id' => $this->getUser()->id,
            'activity_date' => $activity_date,
            'activity_category_group_id' => $activity_category_group_id,
            'amount' => $amount,
            'credit_flag' => $credit_flag,
        ]);
    }

    /**
     * 当月は「今日まで」で期間を切るため、境界を確かめる比較は過去の月で行う。
     * 月初を基準にするのは、31 日に -1 month を足すと月が飛ぶため。
     *
     * @param int $months
     * @return string
     */
    private function monthBefore($months)
    {
        return date('Y-m', strtotime(date('Y-m-01') . ' -' . $months . ' months'));
    }

    /**
     * @param string $date_month
     * @return string
     */
    private function lastDayOf($date_month)
    {
        return date('Y-m-t', strtotime($date_month . '-01'));
    }
}
