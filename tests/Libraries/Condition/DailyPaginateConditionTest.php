<?php
namespace Tests\Libraries\Condition;

use App\Libraries\Condition\DailyPaginateCondition;
use Tests\TestCase;

class DailyPaginateConditionTest extends TestCase {
    /**
     * 金額はクエリ文字列から来る。'1.5' や '1e5' を numeric で受けると、int 列と
     * 食い違う額で絞ることになる (変動費の登録で同じ形の値が別の額で保存されて
     * いた)。整数に読めないものは指定なしへ倒し、検索全体は止めない。
     */
    public function testAmountAcceptsOnlyNonNegativeIntegers()
    {
        $this->assertAmountRange(['1000', '2000'], [1000, 2000]);
        $this->assertAmountRange([' 1000 ', '02000'], [1000, 2000]);

        foreach (['', '1.5', '1e5', '-1000', 'abc', ['1000'], null] as $value) {
            $this->assertAmountRange([$value, $value], [null, null]);
        }
    }

    /**
     * 上下を逆に入れたら入れ替える。そのまま比べると必ず 0 件になり、
     * どこが悪いのかが画面から読めない。
     */
    public function testReversedAmountRangeIsSwapped()
    {
        $this->assertAmountRange(['5000', '1000'], [1000, 5000]);
    }

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

    /**
     * @param array $input [min_amount, max_amount]
     * @param array $expected [min_amount, max_amount]
     */
    private function assertAmountRange(array $input, array $expected)
    {
        $condition = new DailyPaginateCondition([
            'min_amount' => $input[0],
            'max_amount' => $input[1],
        ]);

        $this->assertSame($expected, [$condition->min_amount, $condition->max_amount], var_export($input, true));
    }
}
