<?php
namespace App\Http\Controllers\Summary;

use Auth;
use Request;
use View;

use App\Libraries\Condition;
use App\Services;

class YearlyController extends \App\Http\Controllers\Controller {
    private $activity;

    /**
     * @see BaseController::__construct()
     */
    public function __construct(Services\ActivityService $activity)
    {
        $this->activity = $activity;

        parent::__construct();
    }

    /**
     * 年別集計を表示する。
     */
    public function index()
    {
        // タブの中身は ajax で読むため、その URL へ検索条件をそのまま渡す。
        // 既定値をここで埋めるのは、画面側で組み直すと集計表と推移グラフで
        // 食い違うため。
        $fields = Request::only('begin_year', 'end_year', 'output_type', 'keyword') + [
            'begin_year' => date('Y'),
            'end_year' => date('Y'),
            'output_type' => Condition\YearlySummaryCondition::OUTPUT_TYPE_MONTHLY,
        ];

        return View::make('summary/yearly/index', ['condition' => new Condition\YearlySummaryCondition($fields)]);
    }

    /**
     * 検索条件を表示する。
     */
    public function condition()
    {
        $output_type = Request::input('output_type');
        $data = [
            'output_type_monthly' => true,
            'output_type_yearly' => false
        ];

        if ($output_type == Condition\YearlySummaryCondition::OUTPUT_TYPE_YEARLY) {
            $data['output_type_monthly'] = false;
            $data['output_type_yearly'] = true;
        }

        $data['date_list'] = $this->activity->getYearlyList(Auth::id());

        return View::make('summary/yearly/condition', $data);
    }

    /**
     * 推移グラフタブを表示する。
     */
    public function lineChart()
    {
        return View::make('summary/yearly/line-chart');
    }

    /**
     * 推移グラフのデータを生成する。
     */
    public function lineChartData()
    {
        $fields = Request::only(
            'begin_year',
            'end_year',
            'output_type',
            'balance_type',
            'keyword'
        );
        $condition = new Condition\YearlyTrendCondition($fields);

        // 戻り値がそのままグラフの入力 (labels と series) になるため包み直さない。
        return response()->json($this->activity->getYearlyTrend(Auth::id(), $condition));
    }

    /**
     * 一覧タブを表示する。
     */
    public function report()
    {
        $fields = Request::only(
            'begin_year',
            'end_year',
            'output_type',
            'keyword'
        );
        $condition = new Condition\YearlySummaryCondition($fields);

        $data = [];
        $data['summary'] = $this->activity->getYearlySummary(Auth::id(), $condition);

        return View::make('summary/yearly/report', $data);
    }
}

