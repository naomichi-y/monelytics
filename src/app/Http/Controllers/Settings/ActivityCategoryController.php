<?php
namespace Monelytics\Controllers\Settings;

use Auth;
use Input;
use Lang;
use Redirect;
use Session;
use View;

use Monelytics\Controllers;
use Monelytics\Services;
use Monelytics\Models;

class ActivityCategoryController extends ApplicationController {
  private $activity_category;

  /**
   * @see BaseController::__construct()
   */
  public function __construct(Services\ActivityCategoryService $activity_category)
  {
    parent::__construct();

    $this->activity_category = $activity_category;
  }

  /**
   * 科目カテゴリリストを表示する。
   */
  public function index()
  {
    $user_id = Auth::id();

    $data = array();
    $data['variable_categories'] = $this->activity_category->findAll($user_id, Models\ActivityCategory::COST_TYPE_VARIABLE);
    $data['constant_categories'] = $this->activity_category->findAll($user_id, Models\ActivityCategory::COST_TYPE_CONSTANT);

    return View::make('settings/activity_category/index', $data);
  }

  /**
   * 科目カテゴリの並び順を更新する。
   */
  public function sort()
  {
    $user_id = Auth::id();
    $ids = Input::get('ids');
    $j = sizeof($ids);

    for ($i = 0; $i < $j; $i++) {
      $this->activity_category->updateSortOrder($user_id, $ids[$i], $i + 1);
    }

    return Redirect::to('settings/activityCategory');
  }

  /**
   * 科目を新規入力する。
   */
  public function create()
  {
    return View::make('settings/activity_category/create');
  }

  /**
   * 科目を新規登録する。
   */
  public function store()
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
  public function edit($id)
  {
    $activity_category = $this->activity_category->find(Auth::id(), $id);

    $data = array();
    $data['id'] = $id;
    $data['activity_category'] = $activity_category;

    if ($activity_category->cost_type == Models\ActivityCategory::COST_TYPE_VARIABLE) {
      $data['cost_type_variable'] = true;
      $data['cost_type_constant'] = false;

    } else {
      $data['cost_type_variable'] = false;
      $data['cost_type_constant'] = true;
    }

    if ($activity_category->balance_type == Models\ActivityCategory::BALANCE_TYPE_INCOME) {
      $data['balance_type_income'] = true;
      $data['balance_type_expense'] = false;

    } else {
      $data['balance_type_income'] = false;
      $data['balance_type_expense'] = true;
    }

    return View::make('settings/activity_category/edit', $data);
  }

  /**
   * 科目を更新する。
   */
  public function update($id)
  {
    $fields = Input::only(
      'category_name',
      'content',
      'cost_type',
      'balance_type'
    );

    $data = array();
    $errors = array();

    if ($this->activity_category->update($id, Auth::id(), $fields, $errors)) {
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
  public function destroy($id)
  {
    $this->activity_category->delete(Auth::id(), $id);

    return Redirect::back()
      ->with('success', Lang::get('validation.custom.delete_success'));
  }
}
