<?php
namespace App\Http\Controllers\Summary;

use Auth;
use Request;
use View;

use App\Libraries\Condition;
use App\Services;

class MonthlyController extends \App\Http\Controllers\Controller {
    private $activity;
    private $activity_categor;

    /**
     * @see BaseController::__construct()
     */
    public function __construct(Services\ActivityService $activity, Services\ActivityCategoryService $activity_category)
    {
        $this->activity = $activity;
        $this->activity_category = $activity_category;

        parent::__construct();
    }

    /**
     * 月別集計を表示する。
     */
    public function index()
    {
        $month_list = $this->activity->getMonthList(Auth::id(), true);

        // 日付範囲で絞っているかどうかで帯の出し方が変わる。範囲で見ている
        // ときは月を選んでいないので、セレクトも前月・翌月も出す先が無い。
        $condition = new Condition\BaseDateCondition(
            Request::only('date_month', 'begin_date', 'end_date')
        );

        // 期間の指定が無いときは当月。帯のセレクトと詳細検索が同じ値を出すよう、
        // 既定はここで 1 度だけ決める。以前は画面ごとに date('Y-m') を書いており、
        // 詳細検索だけ Condition の値 (指定なし) を見て「未指定」で開いていた。
        if (!$condition->hasDateRange() && !strlen((string) $condition->date_month)) {
            $condition->date_month = date('Y-m');
        }

        $data = [];
        $data['month_list'] = $month_list;
        $data['condition'] = $condition;
        $data['date_range'] = $condition->getDateRange();

        // 前月・翌月のボタンが指す先。押せるかどうかも画面ではなくここで
        // 決まる (記録のない向きへは進めない)。日付範囲で絞っている間は
        // どちらも押せないので、そのときの基準は使われない。
        $data['adjacent_months'] = $this->activity->getAdjacentMonths(
            $month_list,
            strlen((string) $condition->date_month) ? $condition->date_month : date('Y-m')
        );

        return View::make('summary/monthly/index', $data);
    }

    /**
     * 検索条件を表示する。
     */
    public function condition()
    {
        $data = [];
        $data['month_list'] = $this->activity->getMonthList(Auth::id(), true);

        // 日付範囲が入っているかの判定は Condition に持たせてある。範囲と月指定は
        // 同時に効かない (getDateRange が範囲を優先する) ので、範囲があるときは
        // 月のセレクトを最初から操作不可にして開く。JS だけで無効化すると、
        // 範囲を入れた状態で開き直した 1 瞬だけ操作できてしまう。
        $data['condition'] = new Condition\BaseDateCondition(
            Request::only('date_month', 'begin_date', 'end_date')
        );

        return View::make('summary/monthly/condition', $data);
    }

    /**
     * 一覧タブを表示する。
     */
    public function report()
    {
        $fields = Request::only(
            'date_month',
            'begin_date',
            'end_date'
        );

        $condition = new Condition\MonthlySummaryCondition($fields);

        $data = [];
        $data['summary'] = $this->activity->getMonthlySummary(Auth::id(), $condition);
        $data['previous'] = $this->activity->getPreviousMonthSummary(Auth::id(), $condition);
        $data['base_link'] = '/summary/daily?' . $condition->buildQueryString();

        return View::make('summary/monthly/report', $data);
    }

    /**
     * カレンダーを表示する。
     */
    public function calendar()
    {
        $condition = new Condition\BaseDateCondition(Request::only('date_month'));

        $data = [];
        $data['calendar'] = $this->activity->getCalendar(Auth::id(), $condition);

        return View::make('summary/monthly/calendar', $data);
    }

    /**
     * 収支構成グラフを表示する。
     */
    public function pieChart()
    {
        // 検索条件は描画側の pie-chart.blade.php が Request から直接読み、
        // pie-chart-data へ渡す。ここで組み立てる必要はない。
        return View::make('summary/monthly/pie-chart');
    }

    /**
     * 収支構成グラフのデータを生成する。
     */
    public function pieChartData()
    {
        $fields = Request::only(
            'date_month',
            'begin_date',
            'end_date',
            'balance_type'
        );
        $condition = new Condition\PieChartCondition($fields);

        $data = [];
        $data['constituents'] = $this->activity_category->getAmountConstituents(Auth::id(), $condition);

        return json_encode($data);
    }

    /**
     * ランキングデータを表示する。
     */
    public function ranking()
    {
        $user_id = Auth::id();
        $fields = Request::only(
            'date_month',
            'begin_date',
            'end_date'
        );
        $condition = new Condition\RankingCondition($fields);

        $data = [];
        $data['location_rankings'] = $this->activity->getRankingByLocation($user_id, $condition);
        $data['date_range'] = $condition->getDateRange();
        $data['expense_rankings'] = $this->activity->getRankingByExpense($user_id, $condition);

        return View::make('summary/monthly/ranking', $data);
    }
}

