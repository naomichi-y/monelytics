<?php
namespace Seeds\Test;

use Illuminate\Database\Seeder;

use DB;

use App\Models\Activity;
use App\Models\ActivityCategoryItem;

class ActivityTableSeeder extends Seeder {
    public function run()
    {
        DB::table('activities')->truncate();

        $activity_category_items = [];
        $activity_category_items[ActivityCategoryItem::CREDIT_FLAG_DISABLE] = [
            ActivityCategoryItemTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE,
            ActivityCategoryItemTableSeeder::TYPE_VARIABLE_INCOME_CREDIT_DISABLE,
            ActivityCategoryItemTableSeeder::TYPE_CONSTANT_EXPENSE_CREDIT_DISABLE,
            ActivityCategoryItemTableSeeder::TYPE_CONSTANT_INCOME_CREDIT_DISABLE
        ];
        $activity_category_items[ActivityCategoryItem::CREDIT_FLAG_ENABLE] = [
            ActivityCategoryItemTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_ENABLE,
            ActivityCategoryItemTableSeeder::TYPE_VARIABLE_INCOME_CREDIT_ENABLE,
            ActivityCategoryItemTableSeeder::TYPE_CONSTANT_EXPENSE_CREDIT_ENABLE,
            ActivityCategoryItemTableSeeder::TYPE_CONSTANT_INCOME_CREDIT_ENABLE,
        ];

        foreach ($activity_category_items as $credit_flag => $activity_category_item_ids) {
            foreach ($activity_category_item_ids as $activity_category_item_id) {
                $activity = [
                    'id' => $activity_category_item_id,
                    'user_id' => 1,
                    'activity_date' => date('Y-m-d'),
                    'activity_category_item_id' => $activity_category_item_id,
                    'amount' => -1000,
                    'credit_flag' => $credit_flag,
                    'special_flag' => Activity::SPECIAL_FLAG_UNUSE
                ];

                Activity::create($activity);
            }
        }
    }
}
