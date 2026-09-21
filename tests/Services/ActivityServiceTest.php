<?php
namespace Tests\Services;

use DB;

use App\Libraries\Condition\DailyPaginateCondition;
use App\Libraries\Condition\MonthlySummaryCondition;
use App\Libraries\Condition\RankingCondition;
use App\Libraries\Condition\YearlySummaryCondition;
use App\Libraries\Condition\YearlyTrendCondition;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityCategoryGroup;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        $this->assertSame(200.0, $result['groups'][$variable_expense]);

        // 固定収支も対象にする (以前は変動収支だけを比較していた)。
        $this->assertSame(-50.0, $result['groups'][$constant_expense]);

        $this->assertSame(100.0, $result['groups'][$variable_income]);
    }

    /**
     * 科目ごとに加えて、収入合計・支出合計・合計も比較する。
     */
    public function testMonthlyComparisonReturnsTotals()
    {
        $result = $this->createComparisonFixture();

        // 収入 1,000 -> 2,000
        $this->assertSame(100.0, $result['totals']['income']);

        // 支出 2,000 -> 3,500。科目と同じく、使った額が増えたら正にする。
        $this->assertSame(75.0, $result['totals']['expense']);

        // 合計 -1,000 -> -1,500
        $this->assertSame(-50.0, $result['totals']['total']);
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
        $this->assertSame(50.0, $result['groups'][$group]);
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
        $this->assertSame(200.0, $result['totals']['income']);

        // 支出 1,000 -> 2,000
        $this->assertSame(100.0, $result['totals']['expense']);
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
        $this->assertSame(100.0, $result['groups'][$group]);
    }

    /**
     * 比べた 2 つの期間を返す。当月は今日で切り、前月も同じ日数で切る。
     */
    public function testMonthlyComparisonReturnsComparedPeriod()
    {
        $result = $this->getMonthlyComparison(date('Y-m'));

        $previous_begin_date = date('Y-m-01', strtotime(date('Y-m-01') . ' -1 month'));
        $day = min((int) date('j'), (int) date('t', strtotime($previous_begin_date)));

        $this->assertSame([
            'begin_date' => date('Y-m-01'),
            'end_date' => date('Y-m-d'),
            'previous_begin_date' => $previous_begin_date,
            'previous_end_date' => date('Y-m-', strtotime($previous_begin_date)) . sprintf('%02d', $day)
        ], $result['period']);
    }

    /**
     * 過去の月は、比べた期間も月末まで。
     */
    public function testMonthlyComparisonReturnsWholePeriodForPastMonth()
    {
        $month = $this->monthBefore(2);
        $previous_month = $this->monthBefore(3);

        $result = $this->getMonthlyComparison($month);

        $this->assertSame([
            'begin_date' => $month . '-01',
            'end_date' => $this->lastDayOf($month),
            'previous_begin_date' => $previous_month . '-01',
            'previous_end_date' => $this->lastDayOf($previous_month)
        ], $result['period']);
    }

    /**
     * 変動支出だけを科目グループごとに集め、前月同時点との差額を添える。
     */
    public function testVariableExpenseComparisonReturnsDifferencePerGroup()
    {
        $group = ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE;
        $previous_month = date('Y-m', strtotime(date('Y-m-01') . ' -1 month'));

        DB::table('activities')->truncate();

        $this->createActivity($group, date('Y-m-d'), -3000);
        $this->createActivity($group, $previous_month . '-01', -2000);

        $result = $this->activity->getVariableExpenseComparison($this->getUser()->id, 5);

        // 支出は負で記録されている。使った額として正で返す。
        $this->assertSame(3000, $result['groups'][0]['amount']);
        $this->assertSame(2000, $result['groups'][0]['previous_amount']);
        $this->assertSame(1000, $result['groups'][0]['difference']);

        $this->assertSame(3000, $result['total']['amount']);
        $this->assertSame(1000, $result['total']['difference']);
    }

    /**
     * 固定支出と収入は外す。どちらも月のうち決まった日にまとめて記録される
     * ため、月の途中で前月と比べても使いすぎの目安にならない。
     */
    public function testVariableExpenseComparisonLeavesOutConstantCostAndIncome()
    {
        DB::table('activities')->truncate();

        $this->createActivity(ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE, date('Y-m-d'), -1000);
        $this->createActivity(ActivityCategoryGroupTableSeeder::TYPE_CONSTANT_EXPENSE_CREDIT_DISABLE, date('Y-m-d'), -80000);
        $this->createActivity(ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_INCOME_CREDIT_DISABLE, date('Y-m-d'), 250000);
        $this->createActivity(ActivityCategoryGroupTableSeeder::TYPE_CONSTANT_INCOME_CREDIT_DISABLE, date('Y-m-d'), 250000);

        $result = $this->activity->getVariableExpenseComparison($this->getUser()->id, 5);

        $this->assertSame(
            [ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE],
            array_column($result['groups'], 'activity_category_group_id')
        );
        $this->assertSame(1000, $result['total']['amount']);
    }

    /**
     * 並びは今月使った額の多い順。落とした科目も合計には残す。
     */
    public function testVariableExpenseComparisonKeepsDroppedGroupsInTotal()
    {
        DB::table('activities')->truncate();

        $this->createActivity(ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE, date('Y-m-d'), -1000);
        $this->createActivity(ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_ENABLE, date('Y-m-d'), -3000);

        $result = $this->activity->getVariableExpenseComparison($this->getUser()->id, 1);

        $this->assertSame(
            [ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_ENABLE],
            array_column($result['groups'], 'activity_category_group_id')
        );

        // 棒は 1 本でも、合計は落とした科目を含める。
        $this->assertSame(4000, $result['total']['amount']);

        // 棒が全部ではないことを画面が言えるように、絞る前の数も返す。
        $this->assertSame(2, $result['group_count']);
    }

    /**
     * 今月の記録がない科目グループは棒を持てないので返さない。前月に使って
     * いた分は合計の差額に残る。
     */
    public function testVariableExpenseComparisonSkipsGroupWithoutCurrentRecord()
    {
        $group = ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE;
        $previous_month = date('Y-m', strtotime(date('Y-m-01') . ' -1 month'));

        DB::table('activities')->truncate();

        $this->createActivity($group, $previous_month . '-01', -2000);

        $result = $this->activity->getVariableExpenseComparison($this->getUser()->id, 5);

        $this->assertSame([], $result['groups']);
        $this->assertSame(0, $result['total']['amount']);
        $this->assertSame(-2000, $result['total']['difference']);
    }

    /**
     * 返金が上回って純額がプラスになった科目は外す。棒の長さが負になり、
     * 支出の並びに混ぜると読めないため。
     */
    public function testVariableExpenseComparisonSkipsRefundedGroup()
    {
        $group = ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE;

        DB::table('activities')->truncate();

        $this->createActivity($group, date('Y-m-d'), -1000);
        $this->createActivity($group, date('Y-m-d'), 1500);

        $result = $this->activity->getVariableExpenseComparison($this->getUser()->id, 5);

        $this->assertSame([], $result['groups']);
        $this->assertSame(0, $result['total']['amount']);
    }

    /**
     * 比べる期間は前月比と同じ切り方をする。当月は今日まで、前月も同じ日数。
     */
    public function testVariableExpenseComparisonCutsBothPeriodsAtTheSameDay()
    {
        $previous_begin_date = date('Y-m-01', strtotime(date('Y-m-01') . ' -1 month'));
        $day = min((int) date('j'), (int) date('t', strtotime($previous_begin_date)));

        $result = $this->activity->getVariableExpenseComparison($this->getUser()->id, 5);

        $this->assertSame([
            'begin_date' => date('Y-m-01'),
            'end_date' => date('Y-m-d'),
            'previous_begin_date' => $previous_begin_date,
            'previous_end_date' => date('Y-m-', strtotime($previous_begin_date)) . sprintf('%02d', $day)
        ], $result['period']);
    }

    /**
     * 前の期間を決められない条件では比較しない。
     */
    public function testMonthlyComparisonReturnsEmptyForUncomparableCondition()
    {
        $month = $this->monthBefore(2);
        $empty = ['groups' => [], 'totals' => [], 'period' => []];

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
     * ランキングは上位だけを見る画面。件数を絞らないと順位が読めない。
     */
    public function testRankingsStopAtTheConditionLimit()
    {
        $group = ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE;

        DB::table('activities')->truncate();

        for ($i = 0; $i < 15; $i++) {
            $activity = $this->createActivity($group, date('Y-m-d'), -1000 - $i);
            $activity->location = 'LOCATION-' . $i;
            $activity->save();
        }

        $condition = new RankingCondition(['date_month' => date('Y-m')]);

        $this->assertSame(10, $condition->limit);
        $this->assertCount($condition->limit, $this->activity->getRankingByExpense($this->getUser()->id, $condition));
        $this->assertCount($condition->limit, $this->activity->getRankingByLocation($this->getUser()->id, $condition));
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

    /**
     * 合計のように元の額が大きいと 1% 未満の増減になりやすい。整数に丸めると
     * 0 になり、増減がないのと見分けが付かなくなる。
     */
    public function testMonthlyComparisonKeepsChangeUnderOnePercent()
    {
        $month = $this->monthBefore(2);
        $group = ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE;

        // 前月 -100,000 に対し当月 -100,200。増減は 0.2%。
        $this->createActivity($group, $this->monthBefore(3) . '-05', -100000);
        $this->createActivity($group, $month . '-05', -100200);

        $result = $this->getMonthlyComparison($month);

        $this->assertSame(0.2, $result['groups'][$group]);
    }

    /**
     * ID は利用者が自由に送れるため、他人の収支を掴めてはいけない。
     */
    public function testUpdateRejectsOtherUsersActivity()
    {
        $other_user = $this->createOtherUser();
        $activity = $this->createActivity(
            ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE,
            date('Y-m-d'),
            -1000
        );

        $fields = [
            'activity_date' => date('Y-m-d'),
            'activity_category_group_id' => $activity->activity_category_group_id,
            'amount' => 99999,
        ];

        try {
            $this->activity->update($other_user->id, $activity->id, $fields);
            $this->fail('他人の収支レコードが更新できてしまった');

        } catch (ModelNotFoundException $e) {
            // 期待どおり
        }

        $this->assertSame(-1000, Activity::find($activity->id)->amount);
    }

    /**
     * 付け替え先の科目グループも自分のものに限る。
     */
    public function testUpdateRejectsOtherUsersCategoryGroup()
    {
        $user = $this->getUser();
        $other_user = $this->createOtherUser();
        $other_group = $this->createCategoryGroupFor($other_user->id);

        $activity = $this->createActivity(
            ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE,
            date('Y-m-d'),
            -1000
        );

        $fields = [
            'activity_date' => date('Y-m-d'),
            'activity_category_group_id' => $other_group->id,
            'amount' => 1000,
        ];

        try {
            $this->activity->update($user->id, $activity->id, $fields);
            $this->fail('他人の科目グループへ付け替えられてしまった');

        } catch (ModelNotFoundException $e) {
            // 期待どおり
        }

        $this->assertSame(
            ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE,
            Activity::find($activity->id)->activity_category_group_id
        );
    }

    /**
     * 自分のレコードはこれまでどおり更新できる。
     */
    public function testUpdateAcceptsOwnActivity()
    {
        $user = $this->getUser();
        $activity = $this->createActivity(
            ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE,
            date('Y-m-d'),
            -1000
        );

        $fields = [
            'activity_date' => date('Y-m-d'),
            'activity_category_group_id' => $activity->activity_category_group_id,
            'amount' => 2000,
        ];

        $this->assertTrue($this->activity->update($user->id, $activity->id, $fields));

        // 支出の科目なので負に揃えられる。
        $this->assertSame(-2000, Activity::find($activity->id)->amount);
    }

    /**
     * 金額は整数だけを受け付ける。'1.5' は丸められ、'1e5' は 100000 と
     * 解釈されるため、どちらも入力した額と保存される額が食い違う。
     */
    public function testCreateVariableCostsRejectsNonIntegerAmount()
    {
        foreach (['1.5', '1e5', 'abc'] as $amount) {
            $before_count = Activity::count();

            $this->assertFalse(
                $this->activity->createVariableCosts($this->getUser()->id, $this->variableParams($amount)),
                sprintf('金額 %s が通ってしまった', $amount)
            );
            $this->assertSame($before_count, Activity::count());
        }
    }

    /**
     * amount カラム (int) の範囲を超える額は、保存時の例外ではなく
     * 検証で弾く。
     */
    public function testCreateVariableCostsRejectsOutOfRangeAmount()
    {
        $before_count = Activity::count();

        $this->assertFalse(
            $this->activity->createVariableCosts($this->getUser()->id, $this->variableParams('3000000000'))
        );
        $this->assertSame($before_count, Activity::count());

        // 上限ちょうどは通す。
        $this->assertTrue(
            $this->activity->createVariableCosts($this->getUser()->id, $this->variableParams('2147483647'))
        );
    }

    /**
     * 検索語の '%' と '_' は LIKE のワイルドカードとしてではなく、
     * 文字そのものとして扱う。
     */
    public function testKeywordSearchTreatsWildcardsAsLiterals()
    {
        $group_id = ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE;

        $this->createActivity($group_id, date('Y-m-d'), -100)->update(['location' => 'ABPROBE']);
        $this->createActivity($group_id, date('Y-m-d'), -200)->update(['location' => 'A_PROBE']);

        // '_' が 1 文字ワイルドカードとして効いていた頃は 2 件に一致した。
        $this->assertSame(['A_PROBE'], $this->searchLocations('A_PROBE'));

        // '%' だけの検索で全件が出ていた。
        $this->assertSame([], $this->searchLocations('%'));
    }

    /**
     * output_type を渡さないときの刻みは、推移グラフと同じ月単位にする。
     * 片方だけ年単位だと、同じ検索条件で表とグラフの目盛りがずれる。
     */
    public function testYearlySummaryGroupsByMonthByDefault()
    {
        $year = (int) date('Y');

        $summary = $this->getYearlySummary($year, $year, null);
        $trend = $this->getYearlyTrend($year, $year, null);

        $this->assertSame(array_keys($summary['data']), array_values($trend['labels']));
        $this->assertMatchesRegularExpression('#\A\d{4}/\d{2}\z#', array_key_first($summary['data']));
    }

    /**
     * 開始年が終了年より後の範囲は、推移グラフと同じく空で返す。
     */
    public function testYearlySummaryReturnsNothingForReversedRange()
    {
        $year = (int) date('Y');

        $summary = $this->getYearlySummary($year, $year - 1, YearlySummaryCondition::OUTPUT_TYPE_YEARLY);

        $this->assertSame([], $summary['data']);
        $this->assertSame([], $this->getYearlyTrend($year, $year - 1, YearlySummaryCondition::OUTPUT_TYPE_YEARLY)['labels']);
    }

    /**
     * 年が数値でないときも、日付にならない文字列を組み立てずに空で返す。
     */
    public function testYearlySummaryReturnsNothingForNonNumericYear()
    {
        $summary = $this->getYearlySummary('abc', 'def', YearlySummaryCondition::OUTPUT_TYPE_YEARLY);

        $this->assertSame([], $summary['data']);
    }

    /**
     * @param string $amount
     * @return array
     */
    private function variableParams($amount)
    {
        return [
            'activity_date' => [date('Y-m-d')],
            'activity_category_group_id' => [ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE],
            'amount' => [$amount],
            'location' => [''],
            'content' => [''],
        ];
    }

    /**
     * @param string $keyword
     * @return array 一致した場所の一覧
     */
    private function searchLocations($keyword)
    {
        $condition = new DailyPaginateCondition(['keyword' => $keyword]);
        $paginate = $this->activity->getDailyPaginate($this->getUser()->id, $condition);

        $locations = [];

        foreach ($paginate as $activity) {
            $locations[] = $activity->location;
        }

        sort($locations);

        return $locations;
    }

    /**
     * @param int|string|null $begin_year
     * @param int|string|null $end_year
     * @param int|null $output_type
     * @return array
     */
    private function getYearlySummary($begin_year, $end_year, $output_type)
    {
        $condition = new YearlySummaryCondition([
            'begin_year' => $begin_year,
            'end_year' => $end_year,
            'output_type' => $output_type,
        ]);

        return $this->activity->getYearlySummary($this->getUser()->id, $condition);
    }

    /**
     * @return User
     */
    private function createOtherUser()
    {
        return User::create([
            'email' => 'other@monelytics.me',
            'password' => 'dummy',
            'nickname' => 'other',
            'type' => User::TYPE_GENERAL,
        ]);
    }

    /**
     * @param int $user_id
     * @return ActivityCategoryGroup
     */
    private function createCategoryGroupFor($user_id)
    {
        return ActivityCategoryGroup::create([
            'activity_category_id' => ActivityCategoryTableSeeder::TYPE_VARIABLE_EXPENSE,
            'user_id' => $user_id,
            'group_name' => 'other',
            'credit_flag' => ActivityCategoryGroup::CREDIT_FLAG_DISABLE,
            'sort_order' => 1,
        ]);
    }
}
