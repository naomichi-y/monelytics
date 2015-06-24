<?php
namespace Monelytics\Services;

use Monelytics\Models;

class ActivityCategoryGroupService
{
  private $activity;
  private $activity_category_group;

  /**
   * コンストラクタ。
   *
   * @param Models\ActivityService $activity
   * @param Models\ActivityCategoryGroup $activity_category_group
   */
  public function __construct(Models\Activity $activity, Models\ActivityCategoryGroup $activity_category_group)
  {
    $this->activity = $activity;
    $this->activity_category_group = $activity_category_group;
  }

  /**
   * 科目カテゴリグループの表示順序を更新する。
   *
   * @param int $user_id
   * @param int $id
   * @param int $sort_order
   */
  public function updateSortOrder($user_id, $id, $sort_order)
  {
    $this->activity_category_group->where('id', '=', $id)
      ->where('user_id', '=', $user_id)
      ->update(array('sort_order' => $sort_order));
  }

  /**
   * 科目カテゴリグループの最終表示順序を取得する。
   *
   * @param int $user_id
   * @param int $activity_category_id
   * @return int
   */
  public function getLastSortOrder($user_id, $activity_category_id)
  {
    $builder = $this->activity_category_group->where('user_id', '=', $user_id)
      ->where('activity_category_id', '=', $activity_category_id)
      ->orderBy('sort_order', 'desc');

    $result = $builder->first();

    if ($result) {
      return $result->sort_order;
    }

    return 0;
  }

  /**
   * 科目カテゴリグループを登録する。
   *
   * @param int $user_id
   * @param array $fields
   * @param array &$errors
   * @return bool
   */
  public function create($user_id, array $fields, array &$errors = array())
  {
    $result = false;

    if ($this->activity_category_group->validate($fields)) {
      $fields['user_id'] = $user_id;
      $fields['sort_order'] = $this->getLastSortOrder($user_id, $fields['activity_category_id']) + 1;

      $this->activity_category_group->create($fields);

      $result = true;

    } else {
      $errors = $this->activity_category_group->getErrors();
    }

    return $result;
  }

  /**
   * 科目カテゴリグループのデータを取得する。
   *
   * @param int $user_id
   * @param int $activity_category_group_id
   * @return ActivityCategoryGroup
   */
  public function find($user_id, $activity_category_group_id)
  {
    return $this->activity_category_group->where('user_id', '=', $user_id)
      ->findOrFail($activity_category_group_id);
  }

  /**
   * ユーザに紐づく科目カテゴリグループのIDリストを取得する。
   *
   * @param int $user_id
   * @return array
   */
  public function findIds($user_id)
  {
    $builder = $this->activity_category_group->where('user_id', '=', $user_id)
      ->orderBy('sort_order', 'asc');

    return $builder->list('id', 'id');
  }

  /**
   * ユーザに紐づく全ての科目カテゴリグループデータを取得する。
   *
   * @param int $user_id
   * @param int $activity_cztegory_id
   * @return Collection
   */
  public function findAll($user_id,  $activity_category_id)
  {
    $builder = $this->activity_category_group->where('user_id', '=', $user_id)
      ->where('activity_category_id', '=', $activity_category_id)
      ->orderBy('sort_order', 'asc');

    return $builder->get();
  }

  /**
   * 科目カテゴリグループデータを更新する。
   *
   * @param int $id
   * @param array $fields
   * @param array &$errors
   * @return bool
   */
  public function update($id, $fields, array &$errors = array())
  {
    $result = false;

    if ($this->activity_category_group->validate($fields)) {
      $this->activity_category_group->where('id', '=', $id)
      ->where('user_id', '=', $fields['user_id'])
      ->update($fields);

      $result = true;

    } else {
      $errors = $this->activity_category_group->getErrors();
    }

    return $result;
  }

  /**
   * 科目カテゴリグループデータを削除する。
   *
   * @param int $user_id
   * @param int $activity_category_group_id
   */
  public function delete($user_id, $activity_category_group_id)
  {
    $activity_category_group = $this->activity_category_group->where('id', '=', $activity_category_group_id)
      ->where('user_id', '=', $user_id)
      ->get()
      ->first();
    $activity_category_group->delete();
  }
}
