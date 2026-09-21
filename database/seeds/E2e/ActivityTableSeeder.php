<?php
namespace Seeds\E2e;

use DB;
use Illuminate\Database\Seeder;

use App\Models\Activity;

/**
 * 収支データ。
 *
 * 日付は実行日を基準に組み立てる。月初 (1 日) に寄せているのは、当月の前月比が
 * 「今日まで」で期間を切るため。月末に置くと、実行日によって集計に入ったり
 * 入らなかったりする。
 *
 * 金額は、前月比と年別グラフの目盛りがテストから見て一意に決まるよう選んである。
 */
class ActivityTableSeeder extends Seeder {
    public function run()
    {
        DB::table('activities')->truncate();

        $rows = [
            // 当月。食料品は前月 2,000 に対し 3,000 で +50%。
            [$this->monthsAgo(0), ActivityCategoryItemTableSeeder::FOOD, -3000, 'E2E スーパー', '当月の食料品'],
            [$this->monthsAgo(0), ActivityCategoryItemTableSeeder::DAILY_GOODS, -1000, 'E2E ドラッグストア', '当月の日用品'],
            [$this->monthsAgo(0), ActivityCategoryItemTableSeeder::SALARY, 250000, '', '当月の給与'],

            // 前月。前月比の基準になる。
            [$this->monthsAgo(1), ActivityCategoryItemTableSeeder::FOOD, -2000, 'E2E スーパー', '前月の食料品'],
            [$this->monthsAgo(1), ActivityCategoryItemTableSeeder::SALARY, 250000, '', '前月の給与'],

            // 前々月。月リストに複数の選択肢を出すために置く。
            [$this->monthsAgo(2), ActivityCategoryItemTableSeeder::FOOD, -1500, 'E2E スーパー', '前々月の食料品'],

            // 過去 2 年。年別集計の推移グラフに複数の目盛りを出すために置く。
            [$this->yearsAgo(1), ActivityCategoryItemTableSeeder::FOOD, -5000, 'E2E スーパー', '昨年の食料品'],
            [$this->yearsAgo(1), ActivityCategoryItemTableSeeder::BONUS, 100000, '', '昨年の臨時ボーナス'],
            [$this->yearsAgo(2), ActivityCategoryItemTableSeeder::FOOD, -4000, 'E2E スーパー', '一昨年の食料品'],

            // 固定収支の画面で既存レコードとして見える分。
            [$this->monthsAgo(1), ActivityCategoryItemTableSeeder::RENT, -80000, '', '前月の家賃'],
        ];

        foreach ($rows as list($activity_date, $activity_category_item_id, $amount, $location, $content)) {
            Activity::create([
                'user_id' => UserTableSeeder::USER_ID,
                'activity_date' => $activity_date,
                'activity_category_item_id' => $activity_category_item_id,
                'amount' => $amount,
                'location' => $location,
                'content' => $content,
                'credit_flag' => Activity::CREDIT_FLAG_UNUSE,
                'special_flag' => Activity::SPECIAL_FLAG_UNUSE,
            ]);
        }
    }

    /**
     * @param int $months
     * @return string
     */
    private function monthsAgo($months)
    {
        return date('Y-m-01', strtotime(date('Y-m-01') . ' -' . $months . ' months'));
    }

    /**
     * @param int $years
     * @return string
     */
    private function yearsAgo($years)
    {
        return date('Y-m-01', strtotime(date('Y-m-01') . ' -' . $years . ' years'));
    }
}
