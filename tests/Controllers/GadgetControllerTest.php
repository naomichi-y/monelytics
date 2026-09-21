<?php
namespace Tests\Controllers;

use App\Models\Activity;
use Seeds\Test\ActivityCategoryGroupTableSeeder;
use Tests\TestCase;

class GadgetControllerTest extends TestCase {
    public function testActivityStatus()
    {
        $this->assertUserOnlyContent('GET', '/gadget/activity-status');
    }

    /**
     * 実額だけでは使いすぎか読めないため、先月の同じ時点との増減率を添える。
     * 金額は今月まるごと、増減率は今日までと基準が違うので、比べた期間も
     * 一緒に出す。
     */
    public function testActivityStatusShowsComparisonWithPreviousMonth()
    {
        $previous_month = date('Y-m', strtotime(date('Y-m-01') . ' -1 month'));

        // 初期データは今日の分しかない。比べる先がないと増減率は出ない。
        Activity::create([
            'user_id' => $this->getUser()->id,
            'activity_date' => $previous_month . '-01',
            'activity_category_group_id' => ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE,
            'amount' => -1000,
            'credit_flag' => Activity::CREDIT_FLAG_UNUSE
        ]);

        $this->login();
        $response = $this->call('GET', '/gadget/activity-status');
        $this->logout();

        $response->assertOk();
        $response->assertSee('%');
        $response->assertSee('までとの比較です。');
    }

    public function testActivityGraph()
    {
        $this->assertUserOnlyContent('GET', '/gadget/activity-graph');
    }

    public function testActivityHistory()
    {
        $this->assertUserOnlyContent('GET', '/gadget/activity-history');
    }
}
