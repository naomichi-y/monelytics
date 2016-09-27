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

class ActivityCategoryGroupController extends ApplicationController {
  private $activity_category;
  private $activity_categor_group;

  /**
   * @see BaseController::__construct()
   */
  public function __construct(Services\ActivityCategoryService $activity_category, Services\ActivityCategoryGroupService $activity_category_group)
  {
    parent::__construct();

    $this->activity_category = $activity_category;
    $this->activity_category_group = $activity_category_group;
  }

  /**
   * 科目リストを表示する。
   */
  public function index()
  {
    $user_id = Auth::id();
    $activity_category_id = Input::get('activity_category_id');

    $data = array();
    $data['activity_category_list'] = $this->activity_category->getCategoryList($user_id, true);
    $data['activity_category_groups'] = null;

    if ($activity_category_id) {
      $data['activity_category_groups'] = $this->activity_category_group->findAll($user_id, $activity_category_id);
    }

    return View::make('settings/activity_category_group/index', $data);
  }

  /**
   * 科目の並び順を更新する。
   */
  public function sort()
  {
    $user_id = Auth::id();
    $ids = Input::get('ids');
    $j = sizeof($ids);

    for ($i = 0; $i < $j; $i++) {
      $this->activity_category_group->updateSortOrder($user_id, $ids[$i], $i + 1);
    }

    return Redirect::to('settings/activityCategoryGroup');
  }

  /**
   * 科目を新規入力する。
   */
  public function create()
  {
    $data = array();
    $data['category_list'] = $this->activity_category->getCategoryList(Auth::id(), true);

    return View::make('settings/activity_category_group/create', $data);
  }

  /**
   * 科目を新規登録する。
   */
  public function store()
  {
    $fields = Input::only(
      'activity_category_id',
      'group_name',
      'content',
      'credit_flag'
    );

    $data = array();
    $errors = array();

    if ($this->activity_category_group->create(Auth::id(), $fields, $errors)) {
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
    $user_id = Auth::id();
    $activity_category_group = $this->activity_category_group->find($user_id, $id);

    $data = array();
    $data['id'] = $id;
    $data['category_list'] = $this->activity_category->getCategoryList($user_id, true);
    $data['activity_category_group'] = $activity_category_group;

    if ($activity_category_group->credit_flag == Models\Activity::CREDIT_FLAG_USE) {
      $data['credit_flag'] = true;
    } else {
      $data['credit_flag'] = false;
    }

    return View::make('settings/activity_category_group/edit', $data);
  }

  /**
   * 科目を更新する。
   */
  public function update($id)
  {
    $data = array();
    $errors = array();

    $fields = Input::only(
      'activity_category_id',
      'group_name',
      'content',
      'credit_flag'
    );
    $fields['user_id'] = Auth::id();

    if ($this->activity_category_group->update($id, $fields, $errors)) {
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
    $this->activity_category_group->delete(Auth::id(), $id);

    return Redirect::back()
      ->with('success', Lang::get('validation.custom.delete_success'));
  }
}
