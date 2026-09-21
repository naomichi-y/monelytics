<?php
namespace Tests\Libraries\Condition;

use App\Libraries\Condition\MonthlySummaryCondition;
use Tests\TestCase;

class BaseConditionTest extends TestCase {
    /**
     * 戻り値はリンクの URL として使われ、HTML への逃がしは Html::link 側が
     * 行う。ここで '&amp;' を入れると二重になり、2 つ目以降のパラメータ名が
     * amp;xxx になって読み捨てられる。
     */
    public function testBuildQueryStringSeparatesWithRawAmpersand()
    {
        $condition = new MonthlySummaryCondition([
            'date_month' => '2026-09',
            'begin_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ]);

        $query = $condition->buildQueryString();

        $this->assertStringNotContainsString('&amp;', $query);

        parse_str($query, $parsed);
        $this->assertSame([
            'date_month' => '2026-09',
            'begin_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ], $parsed);
    }

    /**
     * 指定のない条件は URL に載せない。
     */
    public function testBuildQueryStringSkipsUnsetCondition()
    {
        $condition = new MonthlySummaryCondition(['date_month' => '2026-09']);

        $this->assertSame('date_month=2026-09', $condition->buildQueryString());
    }
}
