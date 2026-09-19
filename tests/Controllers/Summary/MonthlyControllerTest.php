<?php
namespace Tests\Controllers\Summary;

use Tests\TestCase;

class MonthlyControllerTest extends TestCase {
    public function testIndex()
    {
        $this->assertUserOnlyContent('GET', '/summary/monthly');
    }

    public function testCondition()
    {
        $this->assertUserOnlyContent('GET', '/summary/monthly/condition');
    }

    public function testReport()
    {
        $this->assertUserOnlyContent('GET', '/summary/monthly/report');
    }

    public function testCalendar()
    {
        $this->assertUserOnlyContent('GET', '/summary/monthly/calendar');
    }

    public function testPieChart()
    {
        $this->assertUserOnlyContent('GET', '/summary/monthly/pie-chart');
    }

    public function testPieChartData()
    {
        $this->assertUserOnlyContent('GET', '/summary/monthly/pie-chart-data');
    }

    public function testRanking()
    {
        $this->assertUserOnlyContent('GET', '/summary/monthly/ranking');
    }

    /**
     * 日別集計へのリンクは、クリックした先でも条件が読める形にする。
     *
     * 区切りを '&amp;' で組み立てると HTML への逃がしと二重になり、2 つ目
     * 以降のパラメータ名が amp;xxx になって読み捨てられる。
     */
    public function testLinksKeepQueryParameters()
    {
        $this->login();

        $targets = [
            '/summary/monthly/report' => [
                'date_month' => date('Y-m'),
                'begin_date' => date('Y-m-01'),
                'end_date' => date('Y-m-d'),
            ],
            '/summary/monthly/calendar' => [
                'date_month' => date('Y-m'),
            ],
        ];

        foreach ($targets as $uri => $parameters) {
            $content = $this->call('GET', $uri, $parameters)->getContent();

            $this->assertStringNotContainsString('&amp;amp;', $content, $uri);

            $this->assertArrayHasKey('end_date', $this->firstDailyLinkQuery($content), $uri);
        }

        $this->logout();
    }

    /**
     * 日別集計への最初のリンクを、ブラウザが送るクエリとして読み直す。
     *
     * @param string $content
     * @return array
     */
    private function firstDailyLinkQuery($content)
    {
        $this->assertSame(1, preg_match('/href="([^"]*summary\/daily[^"]*)"/', $content, $matches), '日別集計へのリンクがない');

        parse_str((string) parse_url(html_entity_decode($matches[1]), PHP_URL_QUERY), $query);

        return $query;
    }
}
