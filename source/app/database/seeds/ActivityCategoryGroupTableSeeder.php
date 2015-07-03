<?php
use Monelytics\Models;

class ActivityCategoryGroupTableSeeder extends Seeder {
  const TYPE_VARIABLE_EXPENSE_CREDIT_ENABLE = 1;
  const TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE = 2;
  const TYPE_VARIABLE_INCOME_CREDIT_ENABLE = 3;
  const TYPE_VARIABLE_INCOME_CREDIT_DISABLE = 4;
  const TYPE_CONSTANT_EXPENSE_CREDIT_ENABLE = 5;
  const TYPE_CONSTANT_EXPENSE_CREDIT_DISABLE = 6;
  const TYPE_CONSTANT_INCOME_CREDIT_ENABLE = 7;
  const TYPE_CONSTANT_INCOME_CREDIT_DISABLE = 8;

  public function run()
  {
    DB::table('activity_category_groups')->truncate();

    $ids = array(
      self::TYPE_VARIABLE_EXPENSE_CREDIT_ENABLE,
      self::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE,
      self::TYPE_VARIABLE_INCOME_CREDIT_ENABLE,
      self::TYPE_VARIABLE_INCOME_CREDIT_DISABLE,
      self::TYPE_CONSTANT_EXPENSE_CREDIT_ENABLE,
      self::TYPE_CONSTANT_EXPENSE_CREDIT_DISABLE,
      self::TYPE_CONSTANT_INCOME_CREDIT_ENABLE,
      self::TYPE_CONSTANT_INCOME_CREDIT_DISABLE
   );

    $types = array(
      ActivityCategoryTableSeeder::TYPE_VARIABLE_EXPENSE,
      ActivityCategoryTableSeeder::TYPE_VARIABLE_INCOME,
      ActivityCategoryTableSeeder::TYPE_CONSTANT_EXPENSE,
      ActivityCategoryTableSeeder::TYPE_CONSTANT_INCOME,
    );

    $index = 1;
    foreach ($types as $type) {
      $activity_category_groups = array(
        array(
          'id' => $index++,
          'activity_category_id' => $type,
          'user_id' => 1,
          'group_name' => 'test',
          'credit_flag' => Models\ActivityCategoryGroup::CREDIT_FLAG_DISABLE,
          'sort_order' => 1
        ),
         array(
          'id' => $index++,
          'activity_category_id' => $type,
          'user_id' => 1,
          'group_name' => 'test',
          'credit_flag' => Models\ActivityCategoryGroup::CREDIT_FLAG_ENABLE,
          'sort_order' => 2
        )
      );

      foreach ($activity_category_groups as $activity_category_group) {
        Models\ActivityCategoryGroup::create($activity_category_group);
      }
    }
  }
}
