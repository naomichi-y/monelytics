<?php
namespace Tests\Controllers;

use Cache;

use App\Libraries\Calendar;
use Tests\TestCase;

class HolidayControllerTest extends TestCase {
    protected function setUp(): void
    {
        parent::setUp();

        // 取り込み済みとして扱わせ、試験中に配布元へ出て行かないようにする。
        // 年での絞り込みを見るため、今年と、今年には決してならない年を混ぜる。
        Cache::put(Calendar::CACHE_KEY, [
            '2000-01-01' => ['元日'],
            '2000-01-10' => ['成人の日'],
            date('Y') . '-05-03' => ['憲法記念日'],
        ], 60);
    }

    public function testIndex()
    {
        $this->assertUserOnlyContent('GET', '/holidays');
    }

    /**
     * 指定された年のぶんだけを、日付と名称の対応で返す。
     */
    public function testIndexReturnsHolidaysOfTheYear()
    {
        $this->login();

        $this->call('GET', '/holidays', ['year' => '2000'])
            ->assertOk()
            ->assertExactJson([
                '2000-01-01' => '元日',
                '2000-01-10' => '成人の日',
            ]);

        $this->logout();
    }

    /**
     * 年の指定は利用者入力から来る。読めない値は今年として扱う。
     */
    public function testIndexFallsBackToThisYear()
    {
        $this->login();

        foreach (['', '20xx', '-1', '2026年', '99999'] as $year) {
            $this->call('GET', '/holidays', ['year' => $year])
                ->assertOk()
                ->assertExactJson([date('Y') . '-05-03' => '憲法記念日']);
        }

        $this->logout();
    }
}
