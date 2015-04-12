<?php
class ActivityCategoryService
{
  private $activity_category;

  /**
   * コンストラクタ。
   *
   * @param ActivityCategory $activity_category
   */
  public function __construct(ActivityCategory $activity_category)
  {
    $this->activity_category = $activity_category;
  }

  /**
   * ユーザに紐づく全ての科目カテゴリデータを取得する。
   *
   * @param int $user_id
   * @param int $cost_type
   * @return Collection
   */
  public function findAll($user_id, $cost_type)
  {
    $builder = $this->activity_category->with('activityCategoryGroup')
      ->where('user_id', '=', $user_id)
      ->where('cost_type', '=', $cost_type)
      ->orderBy('sort_order', 'asc');

    return $builder->get();
  }

  /**
   * 科目カテゴリの表示順序を更新する。
   *
   * @param int $user_id
   * @param int $id
   * @param int $sort_order
   */
  public function updateSortOrder($user_id, $id, $sort_order)
  {
    $this->activity_category->where('id', '=', $id)
      ->where('user_id', '=', $user_id)
      ->update(array('sort_order' => $sort_order));
  }

  /**
   * 科目カテゴリの最終表示順序を取得する。
   *
   * @param int $user_id
   * @return int
   */
  public function getLastSortOrder($user_id)
  {
    $builder = $this->activity_category->where('user_id', '=', $user_id)
      ->orderBy('sort_order', 'desc');

    $result = $builder->first();

    if ($result) {
      return $result->sort_order;
    }

    return 0;
  }

  /**
   * 科目カテゴリを登録する。
   *
   * @param int $user_id
   * @param array $fields
   * @param array &$errors
   * @return bool
   */
  public function create($user_id, array $fields, array &$errors = array())
  {
    $result = false;

    if ($this->activity_category->validate($fields)) {
      $fields['user_id'] = $user_id;
      $fields['sort_order'] = $this->getLastSortOrder($user_id) + 1;

      $this->activity_category->create($fields);

      $result = true;

    } else {
      $errors = $this->activity_category->getErrors();
    }

    return $result;
  }

  /**
   * ユーザに紐づく科目カテゴリのリストを取得する。
   *
   * @param int $user_id
   * @param bool $header:w
   * @return array
   */
  public function getCategoryList($user_id, $header = false)
  {
    $builder = $this->activity_category->where('user_id', '=', $user_id)
      ->orderBy('cost_type', 'asc')
      ->orderBy('sort_order', 'asc');
    $collection = $builder->get();
    $array = array();
    $group_names = array(
      ActivityCategory::COST_TYPE_VARIABLE => '変動収支',
      ActivityCategory::COST_TYPE_CONSTANT => '固定収支'
    );

    foreach ($collection as $data) {
      $array[$group_names[$data->cost_type]][$data->id] = $data->category_name;
    }

    if ($header) {
      $array = array('' => '科目カテゴリの指定') + $array;
    }

    return $array;
  }

  /**
   * 科目カテゴリグループのリストを取得する。
   *
   * @param int $user_id
   * @param int $cost_type
   * @return array
   */
  public function getCategoryGroupList($user_id, $cost_type = null, $header = false, $assoc = false)
  {
    $builder = $this->activity_category->where('user_id', '=', $user_id);

    if ($cost_type !== null) {
      $builder->where('cost_type', '=', $cost_type);
    }

    $activity_categories = $builder->orderBy('cost_type', 'asc')
      ->orderBy('sort_order', 'asc')
      ->get();
    $result = array();

    foreach ($activity_categories as $activity_category) {
      $builder = $activity_category->activityCategoryGroup()
        ->where('user_id', '=', $user_id)
        ->orderBy('sort_order', 'asc');

      $activity_category_groups = $builder->get();
      $array = array();

      foreach ($activity_category_groups as $activity_category_group) {
        $array[$activity_category_group->id] = $activity_category_group->group_name;
      }

      if (sizeof($array)) {
        $result[$activity_category->category_name] = $array;
      }
    }

    if ($header) {
      $result = array('' => '科目の指定') + $result;
    }

    return $result;
  }

  /**
   * 科目カテゴリに紐づく科目カテゴリグループを連想配列形式で取得する。
   *
   * @param int $user_id
   * @return array
   */
  public function getCategoryGroupData($user_id)
  {
    $activity_categories = $this->activity_category->where('user_id', '=', $user_id)
      ->orderBy('cost_type', 'asc')
      ->orderBy('sort_order', 'asc')
      ->get();
    $result = array();

    foreach ($activity_categories as $activity_category) {
      $builder = $activity_category->activityCategoryGroup()
        ->where('user_id', '=', $user_id)
        ->orderBy('sort_order', 'asc');
      $activity_category_groups = array();

      foreach ($builder->get() as $activity_category_group) {
        $activity_category_groups[$activity_category_group->id] = $activity_category_group->group_name;
      }

      if (sizeof($activity_category_groups)) {
        $result[$activity_category->cost_type][] = array(
          'activity_category_id' => $activity_category->id,
          'activity_category_name' => $activity_category->category_name,
          'activity_category_groups' => $activity_category_groups
        );
      }
    }

    return $result;
  }

  /**
   * 科目カテゴリデータを取得する。
   *
   * @param int $user_id
   * @param int $activity_category_group_id
   * @return ActivityCategoryGroup
   */
  public function find($user_id, $activity_category_group_id)
  {
    $builder = $this->activity_category->where('id', '=', $activity_category_group_id)
      ->where('user_id', '=', $user_id);

    return $builder->first();
  }

  /**
   * 収支データにおける科目カテゴリ別の割合比率を取得する。
   *
   * @param int $user_id
   * @param int $balance_type
   * @return array
   */
  public function getAmountConstituents($user_id, PieChartCondition $condition)
  {
    $date_range = $condition->getDateRange();
    $builder = DB::table('activities AS a')
      ->select(DB::raw('ac.category_name, SUM(a.amount) AS category_amount'))
      ->join('activity_category_groups AS acg', 'a.activity_category_group_id', '=', 'acg.id')
      ->join('activity_categories AS ac', 'acg.activity_category_id', '=', 'ac.id')
      ->where('a.user_id', '=', $user_id);

      if (strlen($date_range->begin_date)) {
        if (strlen($date_range->end_date)) {
          $builder->whereBetween('a.activity_date', array($date_range->begin_date, $date_range->end_date));
        } else {
          $builder->where('a.activity_date', '>=', $date_range->begin_date);
        }

      } else if (strlen($date_range->end_date)) {
        $builder->where('a.activity_date', '<=', $date_range->end_date);
      }

      $builder->where('ac.balance_type', '=', $condition->balance_type)
      ->whereNull('a.delete_date')
      ->whereNull('ac.delete_date')
      ->whereNull('acg.delete_date')
      ->groupBy('ac.id')
      ->orderBy('category_amount', 'asc');

    $array = array();
    $total_amount = 0;

    foreach ($builder->get() as $data) {
      $array[] = array(
        'category_name' => $data->category_name,
        'category_amount' => $data->category_amount
      );
      $total_amount += $data->category_amount;
    }

    $result = array();

    foreach ($array as $data) {
      $label = sprintf('%s (%s)', $data['category_name'], number_format($data['category_amount']));
      $rate = ($data['category_amount'] / $total_amount) * 100;
      $result[$label] = round($rate, 1);
    }

    return $result;
  }

  /**
   * 科目カテゴリデータを更新する。
   *
   * @param int $user_id
   * @param array $fields
   * @param array &$errors
   * @return bool
   */
  public function update($user_id, $fields, array &$errors = array())
  {
    $result = false;

    if ($this->activity_category->validate($fields)) {
      $this->activity_category->where('id', '=', $fields['id'])
      ->where('user_id', '=', $user_id)
      ->update($fields);

      $result = true;

    } else {
      $errors = $this->activity_category->getErrors();
    }

    return $result;
  }

  /**
   * 科目カテゴリデータを削除する。
   *
   * @param int $user_id
   * @param int $activity_category_group_cateogyr_id
   */
  public function delete($user_id, $activity_category_id)
  {
    $activity_category = $this->activity_category->where('id', '=', $activity_category_id)
      ->where('user_id', '=', $user_id)
      ->get()->first();

    $activity_category->delete();
  }
}
