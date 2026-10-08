<?php
namespace Tests\Controllers;

use App\Models\Activity;
use Html;
use Seeds\Test\ActivityCategoryItemTableSeeder;
use Tests\TestCase;

class GadgetControllerTest extends TestCase {
    public function testVariableExpense()
    {
        $this->assertUserOnlyContent('GET', '/gadget/variable-expense');
    }

    /**
     * 小項目ごとの棒と、前月同時点との差額を出す。棒の長さと振り分けの正しさは
     * ActivityServiceTest が押さえているので、ここは画面まで届いているか。
     */
    public function testVariableExpenseShowsBarsAndDifference()
    {
        $previous_month = date('Y-m', strtotime(date('Y-m-01') . ' -1 month'));

        // 初期データは今日の分しかない。比べる先がないと差額が出ない。
        Activity::create([
            'user_id' => $this->getUser()->id,
            'activity_date' => $previous_month . '-01',
            'activity_category_item_id' => ActivityCategoryItemTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_ENABLE,
            'amount' => -400,
            'credit_flag' => Activity::CREDIT_FLAG_UNUSE
        ]);

        $this->login();
        $response = $this->call('GET', '/gadget/variable-expense');
        $this->logout();

        $response->assertOk();
        $response->assertSee('合計');

        // 初期データの変動支出は 1,000 円が 2 件。前月は片方に 400 円だけ。
        $response->assertSee('2,000');
        $response->assertSee('+1,600');
        $response->assertSee('+600');

        $response->assertSee('class="bar"', false);
        $response->assertSee('までの額');
    }

    /**
     * 合計と小項目は今月の額と差だけを並べていて、2 つの数字のどちらが何かが
     * 読めず、差の元になった先月の額も分からなかった。見出しを付け、先月の額
     * も出す。見出しは合計と小項目で 1 つに揃える (2 度並べると被って見えた)。
     */
    public function testVariableExpenseTotalShowsPreviousMonth()
    {
        $previous_month = date('Y-m', strtotime(date('Y-m-01') . ' -1 month'));

        Activity::create([
            'user_id' => $this->getUser()->id,
            'activity_date' => $previous_month . '-01',
            'activity_category_item_id' => ActivityCategoryItemTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_ENABLE,
            'amount' => -400,
            'credit_flag' => Activity::CREDIT_FLAG_UNUSE
        ]);

        $this->login();
        $response = $this->call('GET', '/gadget/variable-expense');
        $this->logout();

        $response->assertOk();

        // 見出しは合計の上の 1 行だけ。その下の合計に今月 2,000 円、先月 400 円、
        // 差 +1,600 円が見出しの順に並ぶ。
        $response->assertSeeInOrder([
            '今月', '先月', '先月との差',
            '合計', Html::amount(2000), Html::amount(400), Html::withUnit('+1,600')
        ], false);
        $this->assertSame(1, substr_count($response->getContent(), '先月との差</th>'));

        // 小項目の行にも先月の額を出す。先月 400 円の小項目は 1,000 円 / 400 円 / +600 円。
        $response->assertSeeInOrder(['</thead>', Html::amount(1000), Html::amount(400), Html::withUnit('+600')], false);
    }

    public function testActivityHistory()
    {
        $this->assertUserOnlyContent('GET', '/gadget/activity-history');
    }
}
