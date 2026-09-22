<?php
namespace App\Services;

use DB;

use App\Libraries\Condition;
use App\Models;

class ActivityCategoryService
{
    private $activity_category;

    /**
     * コンストラクタ。
     *
     * @param Models\ActivityCategory $activity_category
     */
    public function __construct(Models\ActivityCategory $activity_category)
    {
        $this->activity_category = $activity_category;
    }

    /**
     * ユーザに紐づく全ての大項目データを取得する。
     *
     * @param int $user_id
     * @param int $cost_type
     * @return Collection
     */
    public function findAll($user_id, $cost_type)
    {
        // 小項目の側も所有者で絞る。リレーションは activity_category_id しか
        // 見ないため、他人の大項目へぶら下げられた小項目がこの一覧に出ていた。
        $builder = $this->activity_category->with(['activityCategoryItems' => function($builder) use ($user_id) {
                $builder->where('user_id', '=', $user_id);
            }])
            ->where('user_id', '=', $user_id)
            ->where('cost_type', '=', $cost_type)
            ->orderBy('sort_order', 'asc');

        return $builder->get();
    }

    /**
     * 大項目の表示順序を更新する。
     *
     * @param int $user_id
     * @param int $id
     * @param int $sort_order
     */
    public function updateSortOrder($user_id, $id, $sort_order)
    {
        $this->activity_category->where('id', '=', $id)
            ->where('user_id', '=', $user_id)
            ->update(['sort_order' => $sort_order]);
    }

    /**
     * 大項目の最終表示順序を取得する。
     *
     * @param int $user_id
     * @return int
     */
    public function getLastSortOrder($user_id)
    {
        $builder = $this->activity_category->where('user_id', '=', $user_id)
            ->orderBy('sort_order', 'desc');

        $result = $builder->first();

        if ($result) {
            return $result->sort_order;
        }

        return 0;
    }

    /**
     * 大項目を登録する。
     *
     * @param int $user_id
     * @param array $fields
     * @param array &$errors
     * @return bool
     */
    public function create($user_id, array $fields, array &$errors = [])
    {
        $result = false;

        if ($this->activity_category->validate($fields)) {
            $fields['user_id'] = $user_id;
            $fields['sort_order'] = $this->getLastSortOrder($user_id) + 1;

            $this->activity_category->create($fields);

            $result = true;

        } else {
            $errors = $this->activity_category->getErrors();
        }

        return $result;
    }

    /**
     * ユーザに紐づく大項目のリストを取得する。
     *
     * @param int $user_id
     * @param bool $header:w
     * @return array
     */
    public function getCategoryList($user_id, $header = false)
    {
        $builder = $this->activity_category->where('user_id', '=', $user_id)
            ->orderBy('cost_type', 'asc')
            ->orderBy('sort_order', 'asc');
        $collection = $builder->get();
        $array = [];
        $group_names = [
            Models\ActivityCategory::COST_TYPE_VARIABLE => '変動収支',
            Models\ActivityCategory::COST_TYPE_CONSTANT => '固定収支'
        ];

        foreach ($collection as $data) {
            $array[$group_names[$data->cost_type]][$data->id] = $data->category_name;
        }

        if ($header) {
            $array = ['' => '大項目の指定'] + $array;
        }

        return $array;
    }

    /**
     * 小項目のリストを取得する。
     *
     * @param int $user_id
     * @param int $cost_type
     * @return array
     */
    public function getCategoryItemList($user_id, $cost_type = null, $header = false, $assoc = false)
    {
        $builder = $this->activity_category->where('user_id', '=', $user_id);

        if ($cost_type !== null) {
            $builder->where('cost_type', '=', $cost_type);
        }

        $activity_categories = $builder->orderBy('cost_type', 'asc')
            ->orderBy('sort_order', 'asc')
            ->get();
        $result = [];

        foreach ($activity_categories as $activity_category) {
            $builder = $activity_category->activityCategoryItems()
                ->where('user_id', '=', $user_id)
                ->orderBy('sort_order', 'asc');

            $activity_category_items = $builder->get();
            $array = [];

            foreach ($activity_category_items as $activity_category_item) {
                $array[$activity_category_item->id] = $activity_category_item->item_name;
            }

            if (sizeof($array)) {
                $result[$activity_category->category_name] = $array;
            }
        }

        if ($header) {
            $result = ['' => '小項目の指定'] + $result;
        }

        return $result;
    }

    /**
     * 大項目に紐づく小項目を連想配列形式で取得する。
     *
     * @param int $user_id
     * @return array
     */
    public function getCategoryItemData($user_id)
    {
        $activity_categories = $this->activity_category->where('user_id', '=', $user_id)
            ->orderBy('cost_type', 'asc')
            ->orderBy('sort_order', 'asc')
            ->get();
        $result = [];

        foreach ($activity_categories as $activity_category) {
            $builder = $activity_category->activityCategoryItems()
                ->where('user_id', '=', $user_id)
                ->orderBy('sort_order', 'asc');
            $activity_category_items = [];

            foreach ($builder->get() as $activity_category_item) {
                $activity_category_items[$activity_category_item->id] = $activity_category_item->item_name;
            }

            if (sizeof($activity_category_items)) {
                $result[$activity_category->cost_type][] = [
                    'activity_category_id' => $activity_category->id,
                    'activity_category_name' => $activity_category->category_name,
                    'activity_category_items' => $activity_category_items
                ];
            }
        }

        return $result;
    }

    /**
     * 大項目データを取得する。
     *
     * @param int $user_id
     * @param int $activity_category_item_id
     * @return ActivityCategoryItem
     */
    public function find($user_id, $activity_category_item_id)
    {
        $builder = $this->activity_category->where('id', '=', $activity_category_item_id)
            ->where('user_id', '=', $user_id);

        return $builder->first();
    }

    /**
     * 構成グラフ用に、大項目ごとの金額を取得する。
     *
     * 支出は DB 上マイナスで記録されている。符号を反転して正で返し、使った額が
     * 多いほど大きくなるようにする。推移グラフと向きを揃えるため
     * (@see ActivityService::getYearlyTrend)。反転しないと円グラフが負の値を
     * 受け取り、扇が描けない。
     *
     * 返金が上回って純額が逆を向いた大項目は落とす。同じ理由で扇にできない。
     *
     * 割合は返さない。金額から Highcharts が算出するので、両方を持つと表示と
     * 計算がずれる余地ができる。
     *
     * 名前をキーにした連想配列では返さない。大項目名に一意制約がなく、
     * 同名が 2 つあると片方が消えるため。
     *
     * @param int $user_id
     * @param Condition\PieChartCondition $condition
     * @return array [['name' => 大項目名, 'amount' => 金額], ...]
     */
    public function getAmountConstituents($user_id, Condition\PieChartCondition $condition)
    {
        $date_range = $condition->getDateRange();
        $builder = DB::table('activities AS a')
            ->select(DB::raw('ac.category_name, SUM(a.amount) AS category_amount'))
            ->join('activity_category_items AS acg', 'a.activity_category_item_id', '=', 'acg.id')
            ->join('activity_categories AS ac', 'acg.activity_category_id', '=', 'ac.id')
            ->where('a.user_id', '=', $user_id);

            if (strlen($date_range->begin_date)) {
                if (strlen($date_range->end_date)) {
                    $builder->whereBetween('a.activity_date', [$date_range->begin_date, $date_range->end_date]);
                } else {
                    $builder->where('a.activity_date', '>=', $date_range->begin_date);
                }

            } else if (strlen($date_range->end_date)) {
                $builder->where('a.activity_date', '<=', $date_range->end_date);
            }

            $builder->where('ac.balance_type', '=', $condition->balance_type)
            ->whereNull('a.delete_date')
            ->whereNull('ac.delete_date')
            ->whereNull('acg.delete_date')
            // ONLY_FULL_GROUP_BY 対策。category_name は主キー ac.id に
            // 関数従属するため、追加しても group は分割されない。
            ->groupBy('ac.id')
            ->groupBy('ac.category_name')
            ->orderBy('category_amount', 'asc');

        $sign = ($condition->balance_type == Models\ActivityCategory::BALANCE_TYPE_EXPENSE) ? -1 : 1;
        $result = [];

        foreach ($builder->get() as $data) {
            $amount = $sign * (int) $data->category_amount;

            if ($amount <= 0) {
                continue;
            }

            $result[] = ['name' => $data->category_name, 'amount' => $amount];
        }

        return $result;
    }

    /**
     * 大項目データを更新する。
     *
     * @param int $user_id
     * @param int @id
     * @param array $fields
     * @param array &$errors
     * @return bool
     */
    public function update($id, $user_id, $fields, array &$errors = [])
    {
        $result = false;

        if ($this->activity_category->validate($fields)) {
            $this->activity_category->where('id', '=', $id)
            ->where('user_id', '=', $user_id)
            ->update($fields);

            $result = true;

        } else {
            $errors = $this->activity_category->getErrors();
        }

        return $result;
    }

    /**
     * 大項目データを削除する。
     *
     * @param int $user_id
     * @param int $activity_category_item_cateogyr_id
     */
    public function delete($user_id, $activity_category_id)
    {
        $activity_category = $this->activity_category->where('id', '=', $activity_category_id)
            ->where('user_id', '=', $user_id)
            ->get()
            ->first();

        $activity_category->delete();
    }
}
