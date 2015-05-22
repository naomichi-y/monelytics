<?php
class MonthlyController extends BaseController {
  private $activity;
  private $activity_categor;

  /**
   * @see BaseController::__construct()
   */
  public function __construct(ActivityService $activity, ActivityCategoryService $activity_category)
  {
    $this->activity = $activity;
    $this->activity_category = $activity_category;

    parent::__construct();
  }

  /**
   * 月別集計を表示する。
   */
  public function getIndex()
  {
    $data = array();
    $data['month_list'] = $this->activity->getMonthList(Auth::id(), true);

    return View::make('summary/monthly/index', $data);
  }

  /**
   * 検索条件を表示する。
   */
  public function getCondition()
  {
    $data = array();
    $data['month_list'] = $this->activity->getMonthList(Auth::id(), true);

    return View::make('summary/monthly/condition', $data);
  }

  /**
   * 一覧タブを表示する。
   */
  public function getList()
  {
    $fields = Input::only(
      'date_month',
      'begin_date',
      'end_date'
    );

    $condition = new MonthlySummaryCondition($fields);

    $data = array();
    $data['summary'] = $this->activity->getMonthlySummary(Auth::id(), $condition);
    $data['base_link'] = '/summary/daily?' . $condition->buildQueryString();

    return View::make('summary/monthly/list', $data);
  }

  /**
   * カレンダーを表示する。
   */
  public function getCalendar()
  {
    $condition = new BaseDateCondition(Input::only('date_month'));

    $data = array();
    $data['calendar'] = $this->activity->getCalendar(Auth::id(), $condition);

    return View::make('summary/monthly/calendar', $data);
  }

  /**
   * 収支構成グラフを表示する。
   */
  public function getPieChart()
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
  public function getPieChartData()
  {
    $fields = Input::only(
      'date_month',
      'begin_date',
      'end_date',
      'balance_type'
    );
    $condition = new PieChartCondition($fields);

    $data = array();
    $data['constituents'] = $this->activity_category->getAmountConstituents(Auth::id(), $condition);

    return json_encode($data);
  }

  /**
   * ランキングデータを表示する。
   */
  public function getRanking()
  {
    $user_id = Auth::id();
    $fields = Input::only(
      'date_month',
      'begin_date',
      'end_date'
    );
    $condition = new RankingCondition($fields);

    $data = array();
    $data['location_rankings'] = $this->activity->getRankingByLocation($user_id, $condition);
    $data['date_range'] = $condition->getDateRange();
    $data['expense_rankings'] = $this->activity->getRankingByExpense($user_id, $condition);

    return View::make('summary/monthly/ranking', $data);
  }
}

