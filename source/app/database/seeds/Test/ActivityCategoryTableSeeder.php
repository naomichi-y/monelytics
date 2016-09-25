<?php
namespace Monelytics\Seeds\Test;

use DB;

use Illuminate\Database\Seeder;

use Monelytics\Models;

class ActivityCategoryTableSeeder extends Seeder {
  const TYPE_VARIABLE_EXPENSE = 1;
  const TYPE_VARIABLE_INCOME = 2;
  const TYPE_CONSTANT_EXPENSE = 3;
  const TYPE_CONSTANT_INCOME = 4;

  public function run()
  {
    DB::table('activity_categories')->truncate();

    $activity_categories = array(
      array(
        'id' => self::TYPE_VARIABLE_EXPENSE,
        'user_id' => 1,
        'category_name' => 'test',
        'cost_type' => Models\ActivityCategory::COST_TYPE_VARIABLE,
        'balance_type' => Models\ActivityCategory::BALANCE_TYPE_EXPENSE,
        'sort_order' => 1
      ),
      array(
        'id' => self::TYPE_VARIABLE_INCOME,
        'user_id' => 1,
        'category_name' => 'test',
        'cost_type' => Models\ActivityCategory::COST_TYPE_VARIABLE,
        'balance_type' => Models\ActivityCategory::BALANCE_TYPE_INCOME,
        'sort_order' => 2
      ),
      array(
        'id' => self::TYPE_CONSTANT_EXPENSE,
        'user_id' => 1,
        'category_name' => 'test',
        'cost_type' => Models\ActivityCategory::COST_TYPE_CONSTANT,
        'balance_type' => Models\ActivityCategory::BALANCE_TYPE_EXPENSE,
        'sort_order' => 3
      ),
      array(
        'id' => self::TYPE_CONSTANT_INCOME,
        'user_id' => 1,
        'category_name' => 'test',
        'cost_type' => Models\ActivityCategory::COST_TYPE_CONSTANT,
        'balance_type' => Models\ActivityCategory::BALANCE_TYPE_INCOME,
        'sort_order' => 4
      )
    );

    foreach ($activity_categories as $activity_category) {
      Models\ActivityCategory::create($activity_category);
    }
  }
}
