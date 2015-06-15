<?php

class ActivityCategoryTableSeeder extends Seeder {
  public function run()
  {
    DB::table('activity_categories')->truncate();
    DB::table('activity_category_groups')->truncate();
    DB::table('activities')->truncate();

    $activity_categories = array(
      array(
        'id' => 1,
        'user_id' => 1,
        'category_name' => 'test_category',
        'content' => 'test',
        'cost_type' => ActivityCategory::COST_TYPE_VARIABLE,
        'balance_type' => ActivityCategory::BALANCE_TYPE_EXPENSE,
        'sort_order' => 1
      )
    );

    foreach ($activity_categories as $activity_category) {
      $activity_category = ActivityCategory::create($activity_category);

      $activity_category_groups = array(
        'id' => 1,
        'activity_category_id' => $activity_category->id,
        'user_id' => 1,
        'group_name' => 'test',
        'credit_flag' => ActivityCategoryGroup::CREDIT_FLAG_DISABLE,
        'sort_order' => 1
      );

      ActivityCategoryGroup::create($activity_category_groups);
    }
  }
}
