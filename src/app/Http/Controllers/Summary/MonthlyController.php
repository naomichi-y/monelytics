<?php
namespace App\Http\Controllers\Summary;

use Auth;
use Input;
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
    $data = [];
    $data['month_list'] = $this->activity->getMonthList(Auth::id(), true);

    return View::make('summary/monthly/index', $data);
  }

  /**
   * 検索条件を表示する。
   */
  public function condition()
  {
    $data = [];
    $data['month_list'] = $this->activity->getMonthList(Auth::id(), true);

    return View::make('summary/monthly/condition', $data);
  }

  /**
   * 一覧タブを表示する。
   */
  public function report()
  {
    $fields = Input::only(
      'date_month',
      'begin_date',
      'end_date'
    );

    $condition = new Condition\MonthlySummaryCondition($fields);

    $data = [];
    $data['summary'] = $this->activity->getMonthlySummary(Auth::id(), $condition);
    $data['base_link'] = '/summary/daily?' . $condition->buildQueryString();

    return View::make('summary/monthly/report', $data);
  }

  /**
   * カレンダーを表示する。
   */
  public function calendar()
  {
    $condition = new Condition\BaseDateCondition(Input::only('date_month'));

    $data = [];
    $data['calendar'] = $this->activity->getCalendar(Auth::id(), $condition);

    return View::make('summary/monthly/calendar', $data);
  }

  /**
   * 収支構成グラフを表示する。
   */
  public function pieChart()
  {
    $fields = Input::only(
      'date_month',
      'begin_date',
      'end_date',
      'balance_type'
    );

    return View::make('summary/monthly/pie-chart');
  }

  /**
   * 収支構成グラフのデータを生成する。
   */
  public function pieChartData()
  {
    $fields = Input::only(
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
    $fields = Input::only(
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

