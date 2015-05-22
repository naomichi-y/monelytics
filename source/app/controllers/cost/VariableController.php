<?php
class VariableController extends BaseController {
  private $activity;
  private $activity_category;

  /**
   * @see BaseController::__construct()
   */
  public function __construct(ActivityService $activity, ActivityCategoryService $activity_category)
  {
    parent::__construct();

    $this->activity = $activity;
    $this->activity_category = $activity_category;
  }

  /**
   * 変動収支を入力する。
   */
  public function getCreate()
  {
    $data = array();
    $data['input_size'] = 8;
    $data['activity_category_groups'] = $this->activity_category->getCategoryGroupList(Auth::id(), ActivityCategory::COST_TYPE_VARIABLE, true);

    return View::make('cost/variable/create', $data);
  }

  /**
   * 変動収支を登録する。
   */
  public function postCreate()
  {
    $fields = Input::only(
      'activity_date',
      'activity_category_group_id',
      'amount',
      'location',
      'content',
      'credit_flag',
      'special_flag'
    );
    $errors = array();

    if (!$this->activity->createVariableCosts(Auth::id(), $fields, $errors)) {
      return Redirect::back()
        ->withErrors($errors)
        ->withInput();
    }

    return Redirect::back()
      ->with('success', Lang::get('validation.custom.create_success'));
  }

  /**
   * 変動収支を編集する。
   */
  public function getEdit()
  {
    $user_id = Auth::id();
    $activity_id = Input::get('id');

    $data = array();
    $data['activity'] = $this->activity->find($user_id, $activity_id);
    $data['activity_category_groups'] = $this->activity_category->getCategoryGroupList($user_id);

    return View::make('cost/variable/edit', $data);
  }

  /**
   * 変動収支を更新する。
   */
  public function postUpdate()
  {
    $fields = Input::only(
      'id',
      'activity_date',
      'activity_category_group_id',
      'amount',
      'location',
      'content',
      'credit_flag',
      'special_flag'
    );
    $data = array();
    $errors = array();

    if ($this->activity->update($fields, $errors)) {
      $data['result'] = true;

      Session::flash('success', Lang::get('validation.custom.update_success'));

    } else {
      $data['result'] = false;
      $data['errors'] = $errors;
    }

    return json_encode($data);
  }

  /**
   * 変動収支を削除する。
   */
  public function postDelete()
  {
    $activity_id = Input::get('id');

    $this->activity->delete(Auth::id(), $activity_id);

    return Redirect::back()
      ->with('success', Lang::get('validation.custom.delete_success'));
  }
}
