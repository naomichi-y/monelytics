<?php
namespace Tests\Controllers\Summary;

use App\Libraries\Condition\DailyPaginateCondition;
use App\Models\Activity;
use Seeds\Test\ActivityCategoryGroupTableSeeder;
use Tests\TestCase;

class DailyControllerTest extends TestCase {
    public function testIndex()
    {
        $this->assertUserOnlyContent('GET', '/summary/daily');
    }

    public function testCondition()
    {
        $this->assertUserOnlyContent('GET', '/summary/daily/condition');
    }

    /**
     * 並び替えの列は見出しに出しているものだけを受け付ける。以前は利用者の
     * 入力をそのまま orderBy へ渡しており、存在しない列名で 500 になっていた。
     */
    public function testIndexIgnoresUnknownSortField()
    {
        $this->login();

        foreach (['amount) --', 'password', ''] as $sort_field) {
            $this->call('GET', '/summary/daily', ['sort_field' => $sort_field, 'sort_type' => 'asc'])
                ->assertOk();
        }

        $this->logout();
    }

    /**
     * 見出しにある列はこれまでどおり並び替えに使える。
     */
    public function testIndexAcceptsListedSortFields()
    {
        $this->login();

        foreach (DailyPaginateCondition::SORT_FIELDS as $sort_field) {
            $this->call('GET', '/summary/daily', ['sort_field' => $sort_field, 'sort_type' => 'desc'])
                ->assertOk();
        }

        $this->logout();
    }

    /**
     * ページ送りは「前へ / 次へ」の 2 つだけを Bootstrap 5 の印付きで出す。
     *
     * Laravel の既定は Tailwind 版で、移行前から残っていた Bootstrap 3 版の
     * 雛形は page-item / page-link を付けないため、5 では飾りのない文字列が
     * 詰まって並んでいた。5 版の既定の雛形は番号も出すが、この画面は件数が
     * 多く並べても選べないため、番号なしの雛形を指定している。
     */
    public function testIndexPagination()
    {
        $this->login();

        $this->createActivities(DailyPaginateCondition::DEFAULT_LIMIT + 1);

        $content = $this->call('GET', '/summary/daily')->assertOk()->getContent();

        $this->assertSame(1, substr_count($content, 'class="pagination"'));
        $this->assertSame(2, substr_count($content, 'page-item'), '番号は出さない');
        $this->assertStringContainsString('class="page-link"', $content);
        $this->assertStringContainsString('次へ', $content);

        $this->logout();
    }

    /**
     * 1 ページに収まらない件数にするための埋め草。
     */
    private function createActivities(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            Activity::create([
                'user_id' => 1,
                'activity_date' => date('Y-m-d'),
                'activity_category_group_id' => ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE,
                'amount' => -100,
                'credit_flag' => Activity::CREDIT_FLAG_UNUSE,
                'special_flag' => Activity::SPECIAL_FLAG_UNUSE,
            ]);
        }
    }
}
