<?php
namespace Seeds\E2e;

use DB;
use Illuminate\Database\Seeder;

use App\Models\ActivityCategoryItem;

/**
 * 小項目。入力フォームのセレクトに並ぶ名前になる。
 */
class ActivityCategoryItemTableSeeder extends Seeder {
    const FOOD = 1;
    const DAILY_GOODS = 2;
    const BONUS = 3;
    const RENT = 4;
    const SALARY = 5;

    public function run()
    {
        DB::table('activity_category_items')->truncate();

        $rows = [
            [
                'id' => self::FOOD,
                'activity_category_id' => ActivityCategoryTableSeeder::VARIABLE_EXPENSE,
                'item_name' => '食料品',
                'sort_order' => 1,
            ],
            [
                'id' => self::DAILY_GOODS,
                'activity_category_id' => ActivityCategoryTableSeeder::VARIABLE_EXPENSE,
                'item_name' => '日用品',
                'sort_order' => 2,
            ],
            [
                'id' => self::BONUS,
                'activity_category_id' => ActivityCategoryTableSeeder::VARIABLE_INCOME,
                'item_name' => '臨時ボーナス',
                'sort_order' => 3,
            ],
            [
                'id' => self::RENT,
                'activity_category_id' => ActivityCategoryTableSeeder::CONSTANT_EXPENSE,
                'item_name' => '家賃',
                'sort_order' => 4,
            ],
            [
                'id' => self::SALARY,
                'activity_category_id' => ActivityCategoryTableSeeder::CONSTANT_INCOME,
                'item_name' => '給与',
                'sort_order' => 5,
            ],
        ];

        foreach ($rows as $row) {
            $row['user_id'] = UserTableSeeder::USER_ID;
            $row['credit_flag'] = ActivityCategoryItem::CREDIT_FLAG_DISABLE;

            ActivityCategoryItem::create($row);
        }
    }
}
