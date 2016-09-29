<?php
namespace Seeds\Test;

use DB;

use Illuminate\Database\Seeder;

use App\Models\ActivityCategory;

class ActivityCategoryTableSeeder extends Seeder {
    const TYPE_VARIABLE_EXPENSE = 1;
    const TYPE_VARIABLE_INCOME = 2;
    const TYPE_CONSTANT_EXPENSE = 3;
    const TYPE_CONSTANT_INCOME = 4;

    public function run()
    {
        DB::table('activity_categories')->truncate();

        $activity_categories = [ 
            [
                'id' => self::TYPE_VARIABLE_EXPENSE,
                'user_id' => 1,
                'category_name' => 'test',
                'cost_type' => ActivityCategory::COST_TYPE_VARIABLE,
                'balance_type' => ActivityCategory::BALANCE_TYPE_EXPENSE,
                'sort_order' => 1
            ],
            [
                'id' => self::TYPE_VARIABLE_INCOME,
                'user_id' => 1,
                'category_name' => 'test',
                'cost_type' => ActivityCategory::COST_TYPE_VARIABLE,
                'balance_type' => ActivityCategory::BALANCE_TYPE_INCOME,
                'sort_order' => 2
            ],
            [
                'id' => self::TYPE_CONSTANT_EXPENSE,
                'user_id' => 1,
                'category_name' => 'test',
                'cost_type' => ActivityCategory::COST_TYPE_CONSTANT,
                'balance_type' => ActivityCategory::BALANCE_TYPE_EXPENSE,
                'sort_order' => 3
            ],
            [
                'id' => self::TYPE_CONSTANT_INCOME,
                'user_id' => 1,
                'category_name' => 'test',
                'cost_type' => ActivityCategory::COST_TYPE_CONSTANT,
                'balance_type' => ActivityCategory::BALANCE_TYPE_INCOME,
                'sort_order' => 4
            ]
        ];

        foreach ($activity_categories as $activity_category) {
            ActivityCategory::create($activity_category);
        }
    }
}
