<?php
namespace Tests\Controllers\Summary;

use App\Libraries\Condition\DailyPaginateCondition;
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
}
