<?php
class YearlySummaryController extends BaseController {
  private $activity;

  /**
   * @see BaseController::__construct()
   */
  public function __construct(ActivityService $activity)
  {
    $this->activity = $activity;

    parent::__construct();
  }

  /**
   * 年別集計を表示する。
   */
  public function getIndex()
  {
    return View::make('yearly_summary/index');
  }

  /**
   * 検索条件を表示する。
   */
  public function getCondition()
  {
    $output_type = Input::get('output_type');
    $data = array(
      'output_type_monthly' => true,
      'output_type_yearly' => false
    );

    if ($output_type == YearlySummaryCondition::OUTPUT_TYPE_YEARLY) {
      $data['output_type_monthly'] = false;
      $data['output_type_yearly'] = true;
    }

    $data['date_list'] = $this->activity->getYearlyList(Auth::id());

    return View::make('yearly_summary/condition', $data);
  }

  /**
   * 一覧タブを表示する。
   */
  public function getList()
  {
    $fields = Input::only(
      'begin_year',
      'end_year',
      'output_type'
    );
    $condition = new YearlySummaryCondition($fields);

    $data = array();
    $data['summary'] = $this->activity->getYearlySummary(Auth::id(), $condition);

    return View::make('yearly_summary/list', $data);
  }
}

