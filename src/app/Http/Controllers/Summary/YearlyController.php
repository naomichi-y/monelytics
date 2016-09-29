<?php
namespace App\Http\Controllers\Summary;

use Auth;
use Input;
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
        return View::make('summary/yearly/index');
    }

    /**
     * 検索条件を表示する。
     */
    public function condition()
    {
        $output_type = Input::get('output_type');
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
     * 一覧タブを表示する。
     */
    public function report()
    {
        $fields = Input::only(
            'begin_year',
            'end_year',
            'output_type'
        );
        $condition = new Condition\YearlySummaryCondition($fields);

        $data = [];
        $data['summary'] = $this->activity->getYearlySummary(Auth::id(), $condition);

        return View::make('summary/yearly/report', $data);
    }
}

