<?php
namespace Tests\Libraries;

use Cache;

use App\Libraries\Calendar;
use Tests\TestCase;

class CalendarTest extends TestCase {
    /**
     * 内閣府の CSV をそのまま写した一覧。振替休日も国民の休日も、配布元は
     * 「休日」とだけ書く。
     *
     * 2026 年 5 月は 5/3 の憲法記念日が日曜に当たり、みどりの日とこどもの日を
     * 越えた 5/6 が振替休日になる。1998 年 5 月も 5/3 が日曜だが、5/4 は前後を
     * 祝日に挟まれてもいるため、2 つの条件が重なる。
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 取り込み済みとして扱わせ、試験中に配布元へ出て行かないようにする。
        Cache::put(Calendar::CACHE_KEY, [
            '1998-05-03' => ['憲法記念日'],
            '1998-05-04' => ['休日'],
            '1998-05-05' => ['こどもの日'],

            '2026-05-03' => ['憲法記念日'],
            '2026-05-04' => ['みどりの日'],
            '2026-05-05' => ['こどもの日'],
            '2026-05-06' => ['休日'],

            '2026-09-21' => ['敬老の日'],
            '2026-09-22' => ['休日'],
            '2026-09-23' => ['秋分の日'],
        ], 60);
    }

    /**
     * 祝日に挟まれた日は国民の休日 (祝日法 3 条 3 項)。2026/9/22 が敬老の日と
     * 秋分の日の間で「休日」とだけ出ていた。
     */
    public function testNamesNationalHoliday()
    {
        $this->assertSame(['2026-09-22' => ['国民の休日']], $this->unnamedOf('2026-09'));
    }

    /**
     * 日曜に当たった祝日の振替は振替休日 (祝日法 3 条 2 項)。間に祝日が続けば
     * 後ろへずれるため、連なりを遡って日曜の祝日に着くかどうかで見る。
     */
    public function testNamesSubstituteHoliday()
    {
        $this->assertSame(['2026-05-06' => ['振替休日']], $this->unnamedOf('2026-05'));
    }

    /**
     * 2 つの条件が重なる日は振替休日。3 条 3 項が振替休日を除いているため、
     * 国民の休日にしてはいけない。
     */
    public function testSubstituteHolidayWinsOverNationalHoliday()
    {
        $this->assertSame(['1998-05-04' => ['振替休日']], $this->unnamedOf('1998-05'));
    }

    /**
     * 名前の付いた祝日には触れない。
     */
    public function testKeepsNamedHolidays()
    {
        $holidays = Calendar::getHolidays('2026-09');

        $this->assertSame(['敬老の日'], $holidays['2026-09-21']);
        $this->assertSame(['秋分の日'], $holidays['2026-09-23']);
    }

    /**
     * 年で読む日付入力のカレンダーも同じ名前を受け取る。片方だけ直ると、
     * 同じ日が画面ごとに違う名前で出る。
     */
    public function testYearlyListUsesTheSameNames()
    {
        $holidays = Calendar::getHolidaysOfYear('2026');

        $this->assertSame(['国民の休日'], $holidays['2026-09-22']);
        $this->assertSame(['振替休日'], $holidays['2026-05-06']);
    }

    /**
     * @param string $target_month Y-m
     * @return array 名前を補った日だけ
     */
    private function unnamedOf($target_month)
    {
        return array_filter(
            Calendar::getHolidays($target_month),
            static fn ($names) => in_array($names[0], ['国民の休日', '振替休日'], true)
        );
    }
}
