<?php
class ConstantController extends BaseController {
  private $activity;

  /**
   * @see BaseController::__construct()
   */
  public function __construct(UserService $user, ActivityService $activity)
  {
    parent::__construct();

    $this->activity = $activity;
  }

  /**
   * 固定収支を入力する。
   */
  public function getCreate()
  {
    $user_id = Auth::id();
    $target_month = Input::get('date_month');

    $data = array();
    $data['date_months'] = $this->activity->getConstantCostMonthlyList($user_id);

    if ($target_month === null) {
      $target_month = date('Y-m');
    }

    $data['constant_costs'] = $this->activity->getConstantCosts($user_id, $target_month);
    $data['selected_date_month'] = $target_month;

    return View::make('cost/constant/create', $data);
  }

  /**
   * 固定収支を登録・更新する
   */
  public function postCreate()
  {
    $fields = Input::only(
      'activity_date',
      'amount',
      'content',
      'credit_flag'
    );
    $errors = array();

    if (!$this->activity->createOrUpdateConstantCosts(Auth::id(), $fields, $errors)) {
      return Redirect::back()
        ->withErrors($errors)
        ->withInput();
    }

    return Redirect::back()
      ->with('success', Lang::get('validation.custom.update_success'));
  }

  /**
   * 固定収支レコードを削除する。
   */
  public function postDelete()
  {
    $id = Input::get('id');
    $this->activity->delete(Auth::id(), $id);

    return Redirect::back()
      ->with('success', Lang::get('validation.custom.delete_success'));
  }
}
