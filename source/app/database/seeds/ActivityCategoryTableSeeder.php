<?php

class ActivityCategoryTableSeeder extends Seeder {
  public function run()
  {
    DB::table('activity_categories')->truncate();
    DB::table('activity_category_groups')->truncate();
    DB::table('activities')->truncate();

    $activity_category = array(
      'id' => 1,
      'user_id' => 1,
      'category_name' => 'test',
      'cost_type' => ActivityCategory::COST_TYPE_VARIABLE,
      'balance_type' => ActivityCategory::BALANCE_TYPE_EXPENSE,
      'sort_order' => 1
    );

    $activity_category = ActivityCategory::create($activity_category);

    $activity_category_group = array(
      'id' => 1,
      'activity_category_id' => $activity_category->id,
      'user_id' => 1,
      'group_name' => 'test',
      'credit_flag' => ActivityCategoryGroup::CREDIT_FLAG_DISABLE,
      'sort_order' => 1
    );

    $activity_category_group = ActivityCategoryGroup::create($activity_category_group);

    $activity = array(
      'id' => 1,
      'user_id' => 1,
      'activity_date' => date('Y-m-d'),
      'activity_category_group_id' => $activity_category_group->id,
      'amount' => -1000,
      'credit_flag' => Activity::CREDIT_FLAG_UNUSE,
      'special_flag' => Activity::SPECIAL_FLAG_UNUSE
    );

    Activity::create($activity);
  }
}
