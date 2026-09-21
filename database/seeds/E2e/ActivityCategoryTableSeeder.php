<?php
namespace Seeds\E2e;

use DB;
use Illuminate\Database\Seeder;

use App\Models\ActivityCategory;

/**
 * 大項目。
 *
 * 名前はテストのセレクタになるため、画面の他の文言と重ならないものにする。
 */
class ActivityCategoryTableSeeder extends Seeder {
    const VARIABLE_EXPENSE = 1;
    const VARIABLE_INCOME = 2;
    const CONSTANT_EXPENSE = 3;
    const CONSTANT_INCOME = 4;

    public function run()
    {
        DB::table('activity_categories')->truncate();

        $rows = [
            [
                'id' => self::VARIABLE_EXPENSE,
                'category_name' => '生活費',
                'cost_type' => ActivityCategory::COST_TYPE_VARIABLE,
                'balance_type' => ActivityCategory::BALANCE_TYPE_EXPENSE,
                'sort_order' => 1,
            ],
            [
                'id' => self::VARIABLE_INCOME,
                'category_name' => '臨時収入',
                'cost_type' => ActivityCategory::COST_TYPE_VARIABLE,
                'balance_type' => ActivityCategory::BALANCE_TYPE_INCOME,
                'sort_order' => 2,
            ],
            [
                'id' => self::CONSTANT_EXPENSE,
                'category_name' => '固定支出',
                'cost_type' => ActivityCategory::COST_TYPE_CONSTANT,
                'balance_type' => ActivityCategory::BALANCE_TYPE_EXPENSE,
                'sort_order' => 3,
            ],
            [
                'id' => self::CONSTANT_INCOME,
                'category_name' => '固定収入',
                'cost_type' => ActivityCategory::COST_TYPE_CONSTANT,
                'balance_type' => ActivityCategory::BALANCE_TYPE_INCOME,
                'sort_order' => 4,
            ],
        ];

        foreach ($rows as $row) {
            $row['user_id'] = UserTableSeeder::USER_ID;

            ActivityCategory::create($row);
        }
    }
}
