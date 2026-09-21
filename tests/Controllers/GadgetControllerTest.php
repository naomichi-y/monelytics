<?php
namespace Tests\Controllers;

use App\Models\Activity;
use Seeds\Test\ActivityCategoryItemTableSeeder;
use Tests\TestCase;

class GadgetControllerTest extends TestCase {
    public function testVariableExpense()
    {
        $this->assertUserOnlyContent('GET', '/gadget/variable-expense');
    }

    /**
     * 科目ごとの棒と、前月同時点との差額を出す。棒の長さと振り分けの正しさは
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
        $response->assertSee('変動支出合計');

        // 初期データの変動支出は 1,000 円が 2 件。前月は片方に 400 円だけ。
        $response->assertSee('2,000');
        $response->assertSee('+1,600');
        $response->assertSee('+600');

        $response->assertSee('class="bar"', false);
        $response->assertSee('までとの比較');
    }

    public function testActivityHistory()
    {
        $this->assertUserOnlyContent('GET', '/gadget/activity-history');
    }
}
