<?php
namespace Seeds\Test;

use Illuminate\Database\Seeder;

use DB;

use App\Models\Activity;
use App\Models\ActivityCategoryGroup;

class ActivityTableSeeder extends Seeder {
    public function run()
    {
        DB::table('activities')->truncate();

        $activity_category_groups = [];
        $activity_categoru_groups[ActivityCategoryGroup::CREDIT_FLAG_DISABLE] = [
            ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_DISABLE,
            ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_INCOME_CREDIT_DISABLE,
            ActivityCategoryGroupTableSeeder::TYPE_CONSTANT_EXPENSE_CREDIT_DISABLE,
            ActivityCategoryGroupTableSeeder::TYPE_CONSTANT_INCOME_CREDIT_DISABLE
        ];
        $activity_category_groups[ActivityCategoryGroup::CREDIT_FLAG_ENABLE] = [
            ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_ENABLE,
            ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_INCOME_CREDIT_ENABLE,
            ActivityCategoryGroupTableSeeder::TYPE_CONSTANT_EXPENSE_CREDIT_ENABLE,
            ActivityCategoryGroupTableSeeder::TYPE_CONSTANT_INCOME_CREDIT_ENABLE,
        ];

        foreach ($activity_category_groups as $credit_flag => $activity_category_group_ids) {
            foreach ($activity_category_group_ids as $activity_category_group_id) {
                $activity = [
                    'id' => $activity_category_group_id,
                    'user_id' => 1,
                    'activity_date' => date('Y-m-d'),
                    'activity_category_group_id' => $activity_category_group_id,
                    'amount' => -1000,
                    'credit_flag' => $credit_flag,
                    'special_flag' => Activity::SPECIAL_FLAG_UNUSE
                ];

                Activity::create($activity);
            }
        }
    }
}
