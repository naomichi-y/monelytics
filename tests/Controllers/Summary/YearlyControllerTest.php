<?php
namespace Tests\Controllers\Summary;

use App\Libraries\Condition\YearlySummaryCondition;
use Tests\TestCase;

class YearlyControllerTest extends TestCase {
    public function testIndex()
    {
        $this->assertUserOnlyContent('GET', '/summary/yearly');
    }

    public function testCondition()
    {
        $this->assertUserOnlyContent('GET', '/summary/yearly/condition');
    }

    public function testReport()
    {
        $this->assertUserOnlyContent('GET', '/summary/yearly/report');
    }

    public function testLineChart()
    {
        $this->assertUserOnlyContent('GET', '/summary/yearly/line-chart');
    }

    public function testLineChartData()
    {
        $this->assertUserOnlyContent('GET', '/summary/yearly/line-chart-data');
    }

    /**
     * 戻り値はそのままグラフの入力になるため、labels と series を返す。
     */
    public function testLineChartDataReturnsChartInput()
    {
        $this->login();

        $response = $this->call('GET', '/summary/yearly/line-chart-data', [
            'begin_year' => date('Y'),
            'end_year' => date('Y'),
            'output_type' => YearlySummaryCondition::OUTPUT_TYPE_YEARLY,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'labels',
            'series' => [
                ['name', 'data'],
            ],
        ]);

        $this->logout();
    }
}
