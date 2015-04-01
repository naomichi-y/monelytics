<?php
class ActivityCategoryController extends BaseController {
  private $activity_category;

  /**
   * @see BaseController::__construct()
   */
  public function __construct(ActivityCategoryService $activity_category)
  {
    parent::__construct();

    $this->activity_category = $activity_category;
  }

  /**
   * 科目カテゴリリストを表示する。
   */
  public function getIndex()
  {
    $user_id = Auth::id();

    $data = array();
    $data['variable_categories'] = $this->activity_category->findAll($user_id, ActivityCategory::COST_TYPE_VARIABLE);
    $data['constant_categories'] = $this->activity_category->findAll($user_id, ActivityCategory::COST_TYPE_CONSTANT);

    return View::make('activity_category/index', $data);
  }

  /**
   * 科目カテゴリの並び順を更新する。
   */
  public function postSort()
  {
    $user_id = Auth::id();
    $ids = Input::get('ids');
    $j = sizeof($ids);

    for ($i = 0; $i < $j; $i++) {
      $this->activity_category->updateSortOrder($user_id, $ids[$i], $i + 1);
    }

    return Redirect::back();
  }

  /**
   * 科目を新規入力する。
   */
  public function getCreate()
  {
    return View::make('activity_category/create');
  }

  /**
   * 科目を新規登録する。
   */
  public function postCreate()
  {
    $fields = Input::only(
      'category_name',
      'content',
      'cost_type',
      'balance_type'
    );

    $data = array();
    $errors = array();

    if ($this->activity_category->create(Auth::id(), $fields, $errors)) {
      $data['result'] = true;

      Session::flash('success', Lang::get('validation.custom.create_success'));

    } else {
      $data['result'] = false;
      $data['errors'] = $errors;
    }

    return json_encode($data);
  }

  /**
   * 科目を編集する。
   */
  public function getEdit()
  {
    $id = Input::get('id');

    $activity_category = $this->activity_category->find(Auth::id(), $id);

    $data = array();
    $data['activity_category'] = $activity_category;

    if ($activity_category->cost_type == ActivityCategory::COST_TYPE_VARIABLE) {
      $data['cost_type_variable'] = true;
      $data['cost_type_constant'] = false;

    } else {
      $data['cost_type_variable'] = false;
      $data['cost_type_constant'] = true;
    }

    if ($activity_category->balance_type == ActivityCategory::BALANCE_TYPE_INCOME) {
      $data['balance_type_income'] = true;
      $data['balance_type_expense'] = false;

    } else {
      $data['balance_type_income'] = false;
      $data['balance_type_expense'] = true;
    }

    return View::make('activity_category/edit', $data);
  }

  /**
   * 科目を更新する。
   */
  public function postUpdate()
  {
    $fields = Input::only(
      'id',
      'category_name',
      'content',
      'cost_type',
      'balance_type'
    );

    $data = array();
    $errors = array();

    if ($this->activity_category->update(Auth::id(), $fields, $errors)) {
      $data['result'] = true;

    } else {
      $data['result'] = false;
      $data['errors'] = $errors;
    }

    return json_encode($data);
  }

  /**
   * 科目を削除する。
   */
  public function postDelete()
  {
    $activity_category_group_id = Input::get('id');

    $this->activity_category->delete(Auth::id(), $activity_category_group_id);

    return Redirect::back()
      ->with('success', Lang::get('validation.custom.delete_success'));
  }
}
