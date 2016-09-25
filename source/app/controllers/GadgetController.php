<?php
namespace Monelytics\Controllers;

use Agent;
use Auth;
use View;

use Monelytics\Services;

class GadgetController extends BaseController {
  private $activity;

  /**
   * @see BaseController::__construct()
   */
  public function __construct(Services\ActivityService $activity)
  {
    parent::__construct();

    $this->activity = $activity;
  }

  /**
   * 今月の収支状況を表示する。
   */
  public function activityStatus()
  {
    $data = array();
    $data['status'] = $this->activity->getBalanceOfPaymentStatus(Auth::id());

    return View::make('gadget/activity_status', $data);
  }

  /**
   * アクティビティグラフを表示する。
   */
  public function activityGraph()
  {
    $week_day = 7;
    $week_size = 37;
    $week_header_width = 23;
    $rect_size = 18;
    $padding_size = 1;
    $data_start_x = $week_header_width;
    $data_start_y = 0;
    $adjust_x = 6;
    $adjust_y = 6;

    if (Agent::isMobile()) {
      $week_size = 7;
      $rect_size = 31;
    }

    $rect_total_width = ($week_size + 1) * $rect_size;
    $padding_total_width = $week_size  * $padding_size;
    $svg_width = $week_header_width + $rect_total_width + $padding_total_width + $adjust_x;

    $rect_total_height = $week_day * $rect_size;
    $padding_total_height = ($week_day - 1) * $padding_size;
    $svg_height = $rect_total_height + $padding_total_height + $adjust_y;

    $data = array();
    $data['svg_width'] = $svg_width;
    $data['svg_height'] = $svg_height;
    $data['days'] = array('M', '', 'W', '', 'F', '', 'S');
    $data['week_size'] = $week_size;
    $data['rect_size'] = $rect_size;
    $data['padding_size'] = $padding_size;
    $data['data_start_x'] = $data_start_x;
    $data['data_start_y'] = $data_start_y;
    $data['latest_activities'] = $this->activity->getHeats(Auth::id(), $week_size);

    return View::make('gadget/activity_graph', $data);
  }

  /**
   * 最近の収支履歴を表示する。
   */
  public function activityHistory()
  {
    $data = array();
    $data['histories'] = $this->activity->getHistories(Auth::id(), 4);

    return View::make('gadget/activity_history', $data);
  }
}
