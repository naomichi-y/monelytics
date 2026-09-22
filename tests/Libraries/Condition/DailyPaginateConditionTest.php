<?php
namespace Tests\Libraries\Condition;

use App\Libraries\Condition\DailyPaginateCondition;
use Tests\TestCase;

class DailyPaginateConditionTest extends TestCase {
    /**
     * 期間の指定が無いまま /summary/daily を開くと、以前は期間が一切効かず
     * 全期間が並んでいた。並びは発生日の降順なので 1 ページ目は最近の行で
     * 埋まり当月に見えるが、先頭に来るのは未来日の行で、当月のつもりの画面に
     * 翌年の収支が混ざっていた。帯の月セレクトが当月を出しているぶん、
     * 一覧だけが別の期間を見ていることに気付けない。
     */
    public function testDateRangeFallsBackToCurrentMonth()
    {
        $condition = new DailyPaginateCondition();
        $date_range = $condition->getDateRange();

        $this->assertSame(date('Y-m'), $condition->date_month);
        $this->assertSame(date('Y-m-01'), $date_range->begin_date);
        $this->assertSame(date('Y-m-t'), $date_range->end_date);
    }

    /**
     * 全期間は月セレクトの「未指定」で選べる。既定を当月にしたことで
     * そちらが塞がっていないこと。
     */
    public function testUnspecifiedMonthKeepsEveryPeriod()
    {
        $condition = new DailyPaginateCondition(['date_month' => 'all']);
        $date_range = $condition->getDateRange();

        $this->assertNull($date_range->begin_date);
        $this->assertNull($date_range->end_date);
    }

    /**
     * 既定を入れるのは、指定が 1 つも無いときだけ。日付範囲だけを指定した
     * 検索に当月を足すと、範囲の外が落ちる。
     */
    public function testExplicitPeriodIsNotOverwritten()
    {
        $condition = new DailyPaginateCondition([
            'begin_date' => '2026/01/01',
            'end_date' => '2026/01/31',
        ]);
        $date_range = $condition->getDateRange();

        $this->assertNull($condition->date_month);
        $this->assertSame('2026-01-01', $date_range->begin_date);
        $this->assertSame('2026-01-31', $date_range->end_date);
    }

    /**
     * 配列で送られた月は正規化で null に倒れる。既定値を生の入力で判定して
     * いると「指定あり」と読んでしまい、期間の無い状態が残る。
     */
    public function testArrayMonthFallsBackToCurrentMonthInsteadOfEveryPeriod()
    {
        $condition = new DailyPaginateCondition(['date_month' => ['2026-01']]);
        $date_range = $condition->getDateRange();

        $this->assertSame(date('Y-m-01'), $date_range->begin_date);
    }
}
