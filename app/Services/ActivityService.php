<?php
namespace App\Services;

use DateInterval;
use DateTime;

use DB;

use App\Libraries;
use App\Libraries\Condition;
use App\Services;
use App\Models;

class ActivityService
{
    private $activity;
    private $activity_category;
    private $activity_category_item;

    /**
     * コンストラクタ。
     *
     * @param Models\Activity $activity
     * @param Servicss\ActivityCategoryService $activity_category
     * @param Services\ActivityCategoryItemService $activity_category_item
     */
    public function __construct(
        Models\Activity $activity,
        Services\ActivityCategoryService $activity_category,
        Services\ActivityCategoryItemService $activity_category_item)
    {
        $this->activity = $activity;
        $this->activity_category = $activity_category;
        $this->activity_category_item = $activity_category_item;
    }

    /**
     * 変動収支を登録する。
     *
     * @param int $user_id
     * @param array $fields
     * @param array &$errors
     */
    public function createVariableCosts($user_id, array $fields, array &$errors = [])
    {
        $result = false;
        $valid_fields = [];

        if ($this->activity->validateVariableFields($fields, $valid_fields)) {
            foreach ($valid_fields as $name => $value) {
                $value['user_id'] = $user_id;

                $activity_category_item = $this->activity_category_item->find($user_id, $value['activity_category_item_id']);
                $balance_type = $activity_category_item->activityCategory->balance_type;

                $value['amount'] = $this->adjustSignAmount($value['amount'], $balance_type);

                $this->activity->create($value);
            }

            $result = true;

        } else {
            $errors = $this->activity->getErrors();
        }

        return $result;
    }

    /**
     * 金額を収支タイプに合わせてプラス表記、あるいはマイナス表記に変換する。
     *
     * @param int $amount
     * @param int $balance_type
     * @return int
     */
    private function adjustSignAmount($amount, $balance_type)
    {
        // 支出の入力を検知
        if ($balance_type == Models\ActivityCategory::BALANCE_TYPE_EXPENSE) {
            // 入力値がプラス値で登録された場合、マイナス額に変換
            if ($amount > 0) {
                $amount = -$amount;
            }

        // 収入の入力を検知
        } else {
            // 入力値がマイナス値で登録された場合、プラス額に変換
            if ($amount < 0) {
                $amount = -$amount;
            }
        }

        return $amount;
    }

    /**
     * LIKE のメタ文字を打ち消す。
     *
     * 検索語はそのまま LIKE のパターンになるため、'%' や '_' を含む語で
     * 検索すると意図しない行まで一致する ('%' だけで全件、'Q_PROBE' が
     * 'QAPROBE' に一致する)。値自体はクエリビルダが束縛するので、ここで
     * 必要なのはワイルドカードの無効化だけ。
     *
     * エスケープ文字そのものを先に処理しないと、後から付けた '\\' を
     * 二重に潰してしまう。
     *
     * @param string $keyword
     * @return string
     */
    private function escapeLikeWildcards($keyword)
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $keyword);
    }

    /**
     * 場所か用途にキーワードを含む行だけに絞る。
     *
     * 日別集計と年別集計で同じ当たり方をさせるため 1 箇所に置く。画面ごとに
     * 書き直すと、帯の検索で出た行が年別集計では出ない、といった食い違いが出る。
     *
     * 文字列以外は絞り込みなしとして扱う。?keyword[]=x のような指定で配列が
     * 来ると strlen が TypeError を投げ、画面ごと落ちるため。
     *
     * @param Builder $builder
     * @param mixed $keyword
     * @param string $prefix 結合した表の別名 ('a.' など)。単独の表なら空
     * @return void
     */
    private function applyKeyword($builder, $keyword, $prefix = '')
    {
        if (!is_string($keyword) || !strlen($keyword)) {
            return;
        }

        $query_keyword = '%' . $this->escapeLikeWildcards($keyword) . '%';

        $builder->where(function($builder) use ($query_keyword, $prefix) {
            $builder->where($prefix . 'location', 'LIKE', $query_keyword);
            $builder->orWhere($prefix . 'content', 'LIKE', $query_keyword);
        });
    }

    /**
     * 日別集計の結果を取得する。
     *
     * @param int $user_id
     * @param Condition\DailyPaginateCondition $condition
     * @return Illuminate\Pagination\Paginator
     */
    public function getDailyPaginate($user_id, Condition\DailyPaginateCondition $condition)
    {
        $builder = $this->activity->with('activityCategoryItem')
            ->where('user_id', '=', $user_id);

        // 収支タイプ
        if ($condition->cost_type !== null) {
            $builder->whereHas('activityCategoryItem', function($builder) use ($condition) {
                $builder->whereHas('activityCategory', function($builder) use ($condition) {
                    $builder->where('cost_type', '=', $condition->cost_type);
                });
            });
        }

        $builder->activityDate($condition->getDateRange());

        // 小項目
        if (sizeof($condition->activity_category_item_id)) {
            $builder->whereIn('activity_category_item_id', $condition->activity_category_item_id);
        }

        // 場所・内容
        $this->applyKeyword($builder, $condition->keyword);

        if (strlen($condition->location)) {
            $builder->where('location', '=', $condition->location);
        }

        // クレジットカード
        if (strlen($condition->credit_flag)) {
            $builder->where('credit_flag', '=', $condition->credit_flag);
        }

        // 並び順
        $builder->orderBy($condition->sort_field, $condition->sort_type);
        $builder->orderBy('create_date', 'desc');

        $total_amount = $builder->sum('amount');

        $paginate = $builder->paginate($condition->limit);
        $paginate->appends($condition->toArray());
        $paginate->total_amount = $total_amount;

        return $paginate;
    }

    /**
     * 収支が発生する年月のリストを取得する。
     *
     * @param int $user_id
     * @param bool $header
     * @return array
     */
    public function getMonthList($user_id, $header = false)
    {
        $array = $this->activity->select(DB::raw('DATE_FORMAT(activity_date, \'%Y-%m\') AS date'))
            ->where('user_id', '=', $user_id)
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->pluck('date', 'date')
            ->all();

        array_walk($array, function(&$value, $key) {
            $value = str_replace('-', '/', $value);
        });

        if ($header) {
            $array = ['all' => '未指定'] + $array;
        }

        return $array;
    }

    /**
     * 指定した月の前後にある、収支が発生した月を返す。
     *
     * 返すのは getMonthList() の並びにある月に限る。画面のセレクトはその並び
     * しか選択肢に持たないため、暦の上での前月をそのまま返すと、記録のない月
     * ではセレクトが「未指定」へ落ちたまま別の月の集計が出る。記録のない月を
     * 挟んでいるときは、その先にある最も近い月まで飛ぶ。
     *
     * 一覧は呼び出し側から受け取る。画面はセレクトを描くために既に取得して
     * いるため、ここで引き直すと同じ問い合わせを 2 度投げることになる。
     *
     * @param array $month_list getMonthList() の戻り値
     * @param string $date_month 'Y-m' 形式。'all' のように月を指さない値には
     *                           前後がないため、どちらも null を返す
     * @return array ['previous' => 'Y-m'|null, 'next' => 'Y-m'|null]
     */
    public function getAdjacentMonths(array $month_list, $date_month)
    {
        $adjacent = ['previous' => null, 'next' => null];

        if (!$this->isMonth($date_month)) {
            return $adjacent;
        }

        foreach (array_keys($month_list) as $month) {
            // 先頭に付く「未指定」は月を指さない。
            if (!$this->isMonth($month)) {
                continue;
            }

            if ($month > $date_month) {
                // 降順で並んでいるため、最後に残るものが最も近い月になる。
                $adjacent['next'] = $month;
            } else if ($month < $date_month) {
                // 同じく、最初に見つかったものが最も近い月になる。
                $adjacent['previous'] = $month;
                break;
            }
        }

        return $adjacent;
    }

    /**
     * 'Y-m' 形式の月かどうかを判定する。
     *
     * 文字列に倒せない値は月ではないものとして扱う。?date_month[]=x のように
     * 配列が来ると (string) の時点で「Array to string conversion」が例外になり、
     * 月別集計が開けなくなる。
     *
     * @param mixed $value
     * @return bool
     */
    private function isMonth($value)
    {
        if (!is_scalar($value)) {
            return false;
        }

        return preg_match('/\A\d{4}-\d{2}\z/', (string) $value) === 1;
    }

    /**
     * 変動収支データを取得する。
     *
     * @param $user_id
     * @param $activity_id
     * @return Activity
     */
    public function find($user_id, $activity_id)
    {
        return $this->activity
            ->where('user_id', '=', $user_id)
            ->findOrFail($activity_id);
    }

    /**
     * 変動収支データを更新する。
     *
     * 対象レコードも付け替え先の小項目も、必ず $user_id で絞り込んでから
     * 取得する。ID は利用者が自由に送れるため、絞り込まずに取得すると他人の
     * 収支を書き換えられる。
     *
     * @param int $user_id
     * @param int $id
     * @param array $fields
     * @param array &$errors
     * @return bool
     */
    public function update($user_id, $id, array $fields, &$errors = [])
    {
        $result = false;

        if ($this->activity->validate($fields)) {
            $activity = $this->find($user_id, $id);
            $activity->fill($fields);

            $activity_category_item = $this->activity_category_item->find($user_id, $activity->activity_category_item_id);
            $balance_type = $activity_category_item->activityCategory->balance_type;

            $activity->amount = $this->adjustSignAmount($activity->amount, $balance_type);
            $activity->save();

            $result = true;

        } else {
            $errors = $this->activity->getErrors();
        }

        return $result;
    }

    /**
     * 変動収支データを削除する。
     *
     * @param int $user_id
     * @param int $activity_id
     */
    public function delete($user_id, $activity_id)
    {
        $this->activity->where('id', '=', $activity_id)
            ->where('user_id', '=', $user_id)
            ->delete();
    }

    /**
     * 固定収支が発生している年月のリストを取得する。
     *
     * @param int $user_id
     * @return array
     */
    public function getConstantCostMonthlyList($user_id)
    {
        $builder = $this->activity->where('user_id', '=', $user_id)
            ->whereHas('activityCategoryItem', function($builder) {
                $builder->whereHas('activityCategory', function($builder) {
                    $builder->where('cost_type', '=', Models\ActivityCategory::COST_TYPE_CONSTANT);
                });
            });
        $min_date = $builder->min('activity_date');
        $max_date = $builder->max('activity_date');

        $array = [];

        if ($min_date === null) {
            $array[date('Y-m')] = date('Y/m');

        } else {
            $current_date = new DateTime();
            $min_datetime = new DateTime($min_date);
            $min_date = sprintf('%s/01', $min_datetime->format('Y/m'));

            if ($min_datetime->getTimestamp() > $current_date->getTimestamp()) {
                $min_date = $current_date->format('Y/m/01');
            }

            $min_datetime = new DateTime($min_date);
            $max_datetime = new DateTime($max_date);

            while ($max_datetime >= $min_datetime) {
                $array[$min_datetime->format('Y-m')] = $min_datetime->format('Y/m');
                $min_datetime->add(new DateInterval('P1M'));
            }

            arsort($array);
        }

        // 翌月分の要素を追加
        $next_date = new DateTime();
        $next_date->add(new DateInterval('P1M'));

        $first_array = [$next_date->format('Y-m') => $next_date->format('Y/m')];
        $array = $first_array + $array;

        return $array;
    }

    /**
     * 対象年月の固定収支データリストを取得する。
     *
     * @param int $user_id
     * @param string $target_month
     * @return array
     */
    public function getConstantCosts($user_id, $target_month)
    {
        $begin_date = sprintf('%s-01', $target_month);
        $last_day = date('d', strtotime('last day of ' . $target_month));
        $end_date = sprintf('%s-%s', $target_month, $last_day);

        $builder = DB::table('activities AS a')
            ->select(DB::raw('a.id AS activity_id, ac.id AS activity_category_id, ac.category_name, acg.id, DATE_FORMAT(a.activity_date, \'%Y/%m/%d\') AS activity_date, acg.item_name, a.content, acg.credit_flag as default_credit_flag, a.amount, a.credit_flag'))
            ->rightJoin('activity_category_items AS acg', function($join) use($user_id, $begin_date, $end_date)
            {
                // $join(JoinClause)はwhereBetween()をサポートしていないので日付はwhere()で検索
                $join->on('a.activity_category_item_id', '=', 'acg.id')
                    ->where('a.activity_date', '>=', $begin_date)
                    ->where('a.activity_date', '<=', $end_date)
                    // 所有者の条件は ON 側に置く。WHERE へ移すと収支のない行が
                    // 落ちて右外部結合が内部結合になり、まだ入力していない
                    // 小項目が画面から消える。
                    ->where('a.user_id', '=', $user_id)
                    ->whereNull('a.delete_date');
            })
            ->join('activity_categories AS ac', 'acg.activity_category_id', '=', 'ac.id')
            // 結合した表は全て所有者で絞る。大項目だけを絞っていたため、他人の
            // 大項目へぶら下げられた小項目と収支がこの画面に出ていた。
            ->where('acg.user_id', '=', $user_id)
            ->where('ac.user_id', '=', $user_id)
            ->where('ac.cost_type', '=', Models\ActivityCategory::COST_TYPE_CONSTANT)
            ->where('acg.delete_date')
            ->orderBy('ac.sort_order', 'asc')
            ->orderBy('acg.sort_order', 'asc');

        $result = [];
        $previous_id = null;

        foreach ($builder->get() as $data) {
            if (!isset($result[$data->activity_category_id])) {
                $result[$data->activity_category_id] = [
                    'category_name' => $data->category_name,
                    'activity_category_items' => []
                ];
            }

            $result[$data->activity_category_id]['activity_category_items'][] = $data;
        }

        return $result;
    }

    /**
     * 対象年月の固定収支データを取得する。
     *
     * @param int $user_id
     * @param string $target_month
     * @param int $activity_category_item_id
     * @return Activity
     */
    public function findConstantCost($user_id, $target_month, $activity_category_item_id)
    {
        $begin_date = sprintf('%s-01', str_replace('/', '-', $target_month));
        $last_day = date('d', strtotime('last day of ' . $target_month));
        $end_date = sprintf('%s-%s', $target_month, $last_day);

        $builder = $this->activity->where('user_id', '=', $user_id)
            ->whereBetween('activity_date', [$begin_date, $end_date])
            ->where('activity_category_item_id', '=', $activity_category_item_id);

        return $builder->first();
    }

    /**
     * 固定収支データを登録、または更新する。
     *
     * @param int $user_id
     * @param array $fields
     * @param array &$errors
     * @return bool
     */
    public function createOrUpdateConstantCosts($user_id, array $fields, array &$errors = [])
    {
        $result = false;
        $valid_fields = [];

        if ($this->activity->validateConstantFields($fields, $valid_fields)) {
            $target_month = key($fields['activity_date']);

            foreach ($valid_fields as $name => $value) {
                $current_constant_cost = $this->findConstantCost($user_id, $target_month, $value['activity_category_item_id']);

                // 対象の固定収支レコードが登録済みの場合、登録済みデータと入力データを比較し、違いがあればレコードを更新する
                if ($current_constant_cost) {
                    $has_diff = false;

                    // データの整形
                    $activity_date = str_replace('/', '-', $value['activity_date']);
                    $balance_type = $current_constant_cost->activityCategoryItem->activityCategory->balance_type;
                    $amount = $this->adjustSignAmount($value['amount'], $balance_type);
                    $value['amount'] = $amount;

                    // 発生日の比較
                    if (strcmp($activity_date, $current_constant_cost->activity_date) !== 0) {
                        $has_diff = true;

                    // 金額の比較
                    } else if (strcmp($amount, $current_constant_cost->amount) !== 0) {
                        $has_diff = true;

                    // 内容の比較
                    } else if (strcmp($value['content'], $current_constant_cost->content) !== 0) {
                        $has_diff = true;

                    // クレジットカード使用状況の比較
                    } else if (strcmp($value['credit_flag'], $current_constant_cost->credit_flag) !== 0) {
                        $has_diff = true;
                    }

                    if ($has_diff) {
                        $this->activity->where('id', '=', $current_constant_cost->id)->update($value);
                    }

                // 固定収支データの新規登録
                } else {
                    $activity_category_item = $this->activity_category_item->find($user_id, $value['activity_category_item_id']);
                    $balance_type = $activity_category_item->activityCategory->balance_type;
                    $amount = $this->adjustSignAmount($value['amount'], $balance_type);

                    $value['user_id'] = $user_id;
                    $value['amount'] = $amount;
                    $value['special_flag'] = Models\Activity::SPECIAL_FLAG_UNUSE;

                    $this->activity->create($value);
                }
            }

            $result = true;

        } else {
            $errors = $this->activity->getErrors();
        }

        return $result;
    }

    /**
     * 月別集計の結果を取得する。
     *
     * @param int $user_id
     * @param Condition\MonthlySummaryCondition $condition
     * @return stdClass
     */
    public function getMonthlySummary($user_id, Condition\MonthlySummaryCondition $condition)
    {
        $date_range = $condition->getDateRange();

        $builder = DB::table('activities AS a')
            ->select(DB::raw('ac.cost_type, ac.id AS activity_category_id, ac.category_name, acg.id, acg.item_name, a.credit_flag, IFNULL(SUM(a.amount), 0) AS amount'))
            ->rightJoin('activity_category_items AS acg', function($join) use($user_id, $date_range)
            {
                // @see Activity::getConstantCosts()
                $join->on('a.activity_category_item_id', '=', 'acg.id')
                    ->where('a.user_id', '=', $user_id)
                    ->whereNull('a.delete_date');

                if (strlen($date_range->begin_date)) {
                    $join->where('a.activity_date', '>=', $date_range->begin_date);
                }

                if (strlen($date_range->end_date)) {
                    $join->where('a.activity_date', '<=', $date_range->end_date);
                }
            })
            ->join('activity_categories AS ac', 'acg.activity_category_id', '=', 'ac.id')
            // @see Activity::getConstantCosts() と同じ理由で、結合した表を全て
            // 所有者で絞る。大項目だけでは、他人の大項目へぶら下げられた小項目の
            // 名前と金額がこの集計表に混ざる。
            ->where('acg.user_id', '=', $user_id)
            ->where('ac.user_id', '=', $user_id)
            ->whereNull('acg.delete_date')
            // ONLY_FULL_GROUP_BY 対策。acg.id は主キーで、以下の列はいずれも
            // acg -> ac の経路で関数従属するため、追加しても group は分割されない。
            ->groupBy('ac.cost_type')
            ->groupBy('acg.id')
            ->groupBy('a.credit_flag')
            ->groupBy('ac.id')
            ->groupBy('ac.category_name')
            ->groupBy('ac.sort_order')
            ->groupBy('acg.item_name')
            ->groupBy('acg.sort_order')
            ->orderBy('ac.cost_type', 'asc')
            ->orderBy('ac.sort_order', 'asc')
            ->orderBy('acg.sort_order', 'asc');

        return $this->calculateMonthlySummary($builder->get()->all());
    }

    /**
     * 月別集計の集計処理を行う。
     *
     * @param array &$data
     * @return array
     */
    private function calculateMonthlySummary(array $data)
    {
        $category_summary = [
            Models\ActivityCategory::COST_TYPE_VARIABLE => [],
            Models\ActivityCategory::COST_TYPE_CONSTANT => []
        ];
        $income_summary = [
            'cash_amount' => 0,
            'credit_amount' => 0,
            'income_amount' => 0
        ];
        $expense_summary = [
            'cash_amount' => 0,
            'credit_amount' => 0,
            'expense_amount' => 0
        ];
        $cost_size = [
            Models\ActivityCategory::COST_TYPE_VARIABLE => 0,
            Models\ActivityCategory::COST_TYPE_CONSTANT => 0
        ];

        foreach ($data as $value) {
            // 小項目配列の初期化
            if (!isset($category_summary[$value->cost_type][$value->activity_category_id][$value->id])) {
                $category_summary[$value->cost_type][$value->activity_category_id]['category_name'] = $value->category_name;
                $data = &$category_summary[$value->cost_type][$value->activity_category_id]['data'][$value->id];

                if (!isset($data['item_name'])) {
                    $cost_size[$value->cost_type]++;
                }

                $data['item_name'] = $value->item_name;

                if (!isset($data['cash_amount'])) {
                    $data['cash_amount'] = 0;
                }

                if (!isset($data['credit_amount'])) {
                    $data['credit_amount'] = 0;
                }

                if (!isset($data['group_amount'])) {
                    $data['group_amount'] = 0;
                }
            }

            // 現金収支の計算
            if ($value->credit_flag == Models\Activity::CREDIT_FLAG_UNUSE) {
                $data['cash_amount'] += $value->amount;

            } else {
                $data['credit_amount'] += $value->amount;
            }

            // 小項目ごとの合計加算
            $data['group_amount'] += $value->amount;

            // 全小項目の収入加算
            if ($value->amount > 0) {
                if ($value->credit_flag == Models\Activity::CREDIT_FLAG_UNUSE) {
                    $income_summary['cash_amount'] += $value->amount;
                } else {
                    $income_summary['credit_amount'] += $value->amount;
                }

                $income_summary['income_amount'] += $value->amount;

            // 全小項目の支出加算
            } else {
                if ($value->credit_flag == Models\Activity::CREDIT_FLAG_UNUSE) {
                    $expense_summary['cash_amount'] += $value->amount;
                } else {
                    $expense_summary['credit_amount'] += $value->amount;
                }

                $expense_summary['expense_amount'] += $value->amount;
            }
        }

        $summary = [];
        $summary['cost_size'] = $cost_size;
        $summary['category_summary'] = $category_summary;
        $summary['income_summary'] = $income_summary;
        $summary['expense_summary'] = $expense_summary;
        $summary['total_amount'] = $summary['income_summary']['income_amount'] + $summary['expense_summary']['expense_amount'];

        return $summary;
    }

    /**
     * カレンダーを取得する。
     *
     * @param int $user_id
     * @param Condition\BaseDateCondition $condition
     * @return array
     */
    public function getCalendar($user_id, Condition\BaseDateCondition $condition)
    {
        $date_range = $condition->getDateRange();

        if ($date_range->begin_date && $date_range->end_date) {
            $begin_date = sprintf('%s 00:00:00', $date_range->begin_date);
            $end_date = sprintf('%s 23:59:59', $date_range->end_date);

            $builder = DB::table('activities AS a')
                ->select(DB::raw('a.activity_date, SUM(a.amount) AS amount, ac.cost_type'))
                ->join('activity_category_items AS acg', 'a.activity_category_item_id', '=', 'acg.id')
                ->join('activity_categories AS ac', 'acg.activity_category_id', '=', 'ac.id')
                ->where('a.user_id', '=', $user_id)
                ->whereBetween('a.activity_date', [$begin_date, $end_date])
                ->whereNull('a.delete_date')
                ->groupBy('a.activity_date')
                ->groupBy('ac.cost_type')
                ->orderBy('a.activity_date', 'asc');

            $array = [];

            // 1日〜月末までの配列を生成
            $calc_date = new DateTime($date_range->begin_date);
            $calc_end_date = new DateTime($date_range->end_date);
            $current = date('Y-m-d');

            while ($calc_date->getTimestamp() <= $calc_end_date->getTimestamp()) {
                $date = $calc_date->format('Y-m-d');
                $array[$date] = [
                    'short_date' => $calc_date->format('j'),
                    'date' => $date,
                    'day' => $calc_date->format('w'),
                    'variable_amount' => 0,
                    'constant_amount' => 0,
                    'holiday' => false,
                    'holiday_name' => null,
                    'current_date' => ($date === $current) ? true : false
                ];

                $calc_date->add(new DateInterval('P1D'));
            }

            // 祝日情報を配列にセット
            $holidays = Libraries\Calendar::getHolidays($condition->date_month);

            foreach ($holidays as $current => $params) {
                $array[$current]['holiday'] = true;
                $array[$current]['holiday_name'] = $params;
            }

            // 収支データを配列にセット
            foreach ($builder->get() as $current) {
                if ($current->cost_type == Models\ActivityCategory::COST_TYPE_VARIABLE) {
                    $array[$current->activity_date]['variable_amount'] = $current->amount;
                } else {
                    $array[$current->activity_date]['constant_amount'] = $current->amount;
                }
            }

            // 週の配列に変換
            $result = [];
            $i = 1;
            $week = ceil($calc_end_date->format('d') / 7);

            foreach ($array as $date => $params) {
                $result[$i][$params['day']] = $params;

                if ($params['day'] == 6) {
                    $i++;
                }
            }
        } else {
            // ビューは sizeof() で件数を見る。PHP 8 では null を渡せないため
            // 空配列を返す (PHP 7 までは sizeof(null) が 0 と警告になっていた)。
            return [];
        }

        return $result;
    }

    /**
     * 月別集計の小項目合計を、ひとつ前の同じ長さの期間と比べた増減率を返す。
     *
     * 対象は変動収支と固定収支の全小項目。小項目ごとに加えて、収入合計・支出合計・
     * 合計も比較する。
     *
     * 次の場合は比較しない (その項目を返さない)。
     *  - 詳細検索で任意の日付が指定されている (前の期間を定義できない)
     *  - 未来の月が指定されている
     *  - 前の期間の金額が 0 (比率を出せない)
     *
     * 当月を見ているときは今日までで区切り、前月も同じ日数で切る。月末まで
     * 経っていない額を丸ごと前月と比べると必ず減ったように見えるため。
     *
     * @param int $user_id
     * @param Condition\MonthlySummaryCondition $condition
     * @return array ['groups' => [小項目 ID => 増減率], 'totals' => [income|expense|total => 増減率],
     *                'period' => [begin_date|end_date|previous_begin_date|previous_end_date]]
     *               増減率は整数で、正なら増加。period は実際に比べた 2 つの
     *               期間。呼び出し側で日付を組み直すと、ここでの月末の丸め方
     *               (3/31 に対する 2/28) とずれるため返す。
     */
    public function getMonthlyComparison($user_id, Condition\MonthlySummaryCondition $condition)
    {
        $empty = ['groups' => [], 'totals' => [], 'period' => []];

        if (strlen((string) $condition->begin_date) || strlen((string) $condition->end_date)) {
            return $empty;
        }

        $date_month = $condition->date_month ?: date('Y-m');

        if (!preg_match('/\A\d{4}-\d{2}\z/', $date_month)) {
            return $empty;
        }

        $current_month = date('Y-m');

        if ($date_month > $current_month) {
            return $empty;
        }

        $period = $this->buildComparisonPeriod($date_month);

        $begin_date = $period['begin_date'];
        $end_date = $period['end_date'];
        $previous_begin_date = $period['previous_begin_date'];
        $previous_end_date = $period['previous_end_date'];

        $current = $this->sumCostByGroup($user_id, $begin_date, $end_date);
        $previous = $this->sumCostByGroup($user_id, $previous_begin_date, $previous_end_date);

        $groups = [];

        foreach ($current['groups'] as $activity_category_item_id => $amount) {
            if (empty($previous['groups'][$activity_category_item_id])) {
                continue;
            }

            $groups[$activity_category_item_id] = $this->calculateComparisonRate($amount, $previous['groups'][$activity_category_item_id]);
        }

        $totals = [];

        foreach ($current['totals'] as $key => $amount) {
            if (empty($previous['totals'][$key])) {
                continue;
            }

            $totals[$key] = $this->calculateComparisonRate($amount, $previous['totals'][$key]);
        }

        return ['groups' => $groups, 'totals' => $totals, 'period' => $period];
    }

    /**
     * 前月と比べる 2 つの期間を組み立てる。
     *
     * 当月は今日までで区切り、前月も同じ日数で切る。月末まで経っていない額を
     * 丸ごと前月と比べると必ず減ったように見えるため。過ぎた月は両方とも
     * 月末まで。
     *
     * @param string $date_month 'YYYY-MM' 形式で、当月かそれ以前であること
     * @return array [begin_date|end_date|previous_begin_date|previous_end_date]
     */
    private function buildComparisonPeriod($date_month)
    {
        $begin_date = $date_month . '-01';
        $previous_begin_date = date('Y-m-01', strtotime($begin_date . ' -1 month'));

        if ($date_month === date('Y-m')) {
            $end_date = date('Y-m-d');

            // 前月に同じ日がない場合 (3/31 に対する 2 月) は前月の末日まで。
            $day = min((int) date('j'), (int) date('t', strtotime($previous_begin_date)));
            $previous_end_date = date('Y-m-', strtotime($previous_begin_date)) . sprintf('%02d', $day);

        } else {
            $end_date = date('Y-m-t', strtotime($begin_date));
            $previous_end_date = date('Y-m-t', strtotime($previous_begin_date));
        }

        return [
            'begin_date' => $begin_date,
            'end_date' => $end_date,
            'previous_begin_date' => $previous_begin_date,
            'previous_end_date' => $previous_end_date
        ];
    }

    /**
     * 今月の変動支出を小項目ごとに集計し、前月の同じ時点との差額を添える。
     *
     * 収入と固定支出は外す。どちらも月のうち決まった日にまとめて記録される
     * ため、月の途中で前月と比べても、給与日や家賃の登録日を過ぎたかどうかが
     * 出るだけで使いすぎの目安にならない。残高も収入を含む以上は同じ。
     *
     * 差額は率ではなく金額で返す。元が小さい小項目は率が跳ね上がり (100 円から
     * 300 円で +200%)、額の大きい小項目より目立ってしまうため。
     *
     * 今月の記録がない小項目は返さない。棒が描けないうえ、小項目は
     * 利用者が好きなだけ作れるので、使っていない分まで並べると画面が伸びる。
     * 落とした分は合計には含める。
     *
     * @param int $user_id
     * @param int $limit 返す小項目の数。今月の金額が多い順。
     * @return array ['groups' => [['activity_category_item_id', 'item_name', 'amount',
     *                             'previous_amount', 'difference'], ...],
     *                'group_count' => 今月の記録がある小項目の数,
     *                'largest_amount' => 返した小項目の、今月と先月を通した最大額,
     *                'total' => ['amount', 'previous_amount', 'difference'],
     *                'period' => [begin_date|end_date|previous_begin_date|previous_end_date]]
     *               金額は支出を正で返す。difference は正なら前月より使っている。
     *               group_count は $limit で切る前の数。棒が全部かどうかを
     *               画面が言えるように返す。
     */
    public function getVariableExpenseComparison($user_id, $limit)
    {
        $period = $this->buildComparisonPeriod(date('Y-m'));

        $current = $this->sumVariableExpenseByGroup($user_id, $period['begin_date'], $period['end_date']);
        $previous = $this->sumVariableExpenseByGroup($user_id, $period['previous_begin_date'], $period['previous_end_date']);

        $groups = [];

        foreach ($current as $activity_category_item_id => $row) {
            $previous_amount = isset($previous[$activity_category_item_id])
                ? $previous[$activity_category_item_id]['amount']
                : 0;

            $groups[] = [
                'activity_category_item_id' => $activity_category_item_id,
                'item_name' => $row['item_name'],
                'amount' => $row['amount'],
                'previous_amount' => $previous_amount,
                'difference' => $row['amount'] - $previous_amount
            ];
        }

        // 今月使った額の多い順。同額のときは小項目の並び順で落ち着かせる
        // (順序が実行ごとに変わると、読む人には理由のない入れ替わりに見える)。
        usort($groups, function($a, $b) {
            return [$b['amount'], $a['activity_category_item_id']] <=> [$a['amount'], $b['activity_category_item_id']];
        });

        $total_amount = array_sum(array_column($current, 'amount'));
        $total_previous_amount = array_sum(array_column($previous, 'amount'));

        $total = [
            'amount' => $total_amount,
            'previous_amount' => $total_previous_amount,
            'difference' => $total_amount - $total_previous_amount
        ];

        $visible_groups = array_slice($groups, 0, $limit);

        return [
            'groups' => $visible_groups,
            'group_count' => sizeof($groups),
            'largest_amount' => $this->largestVariableExpense($visible_groups),
            'total' => $total,
            'period' => $period
        ];
    }

    /**
     * 棒の長さを決める基準。並べる小項目の、今月と先月を通した最大額。
     *
     * 先月の分まで見るのは、今月の最大値だけを基準にすると、先月のほうが
     * 多かった小項目で先月の棒が枠からはみ出すため。
     *
     * 基準を画面側で組み立てない。並びが今月の額の降順であることに頼って
     * 先頭を取る書き方になり、並べ方を変えた瞬間に黙って狂う。
     *
     * @param array $groups
     * @return int 0 より大きい。$groups は今月の額が正の小項目だけを持つ
     *             (@see ActivityService::sumVariableExpenseByGroup)
     */
    private function largestVariableExpense(array $groups)
    {
        $amounts = [];

        foreach ($groups as $group) {
            $amounts[] = $group['amount'];
            $amounts[] = $group['previous_amount'];
        }

        return $amounts ? max($amounts) : 0;
    }

    /**
     * 変動支出を小項目ごとに合計する。支出は負で記録されているため
     * 符号を反転し、使った額が多いほど大きくなるようにする。
     *
     * 返金が上回って純額がプラスになった小項目は落とす。棒の長さが負に
     * なり、支出の並びに混ぜると読めないため。
     *
     * @param int $user_id
     * @param string $begin_date
     * @param string $end_date
     * @return array [小項目 ID => ['item_name', 'amount']]
     */
    private function sumVariableExpenseByGroup($user_id, $begin_date, $end_date)
    {
        $rows = DB::table('activities AS a')
            ->select(DB::raw('a.activity_category_item_id, acg.item_name, SUM(a.amount) AS amount'))
            ->join('activity_category_items AS acg', 'a.activity_category_item_id', '=', 'acg.id')
            ->join('activity_categories AS ac', 'acg.activity_category_id', '=', 'ac.id')
            ->where('a.user_id', '=', $user_id)
            ->where('ac.cost_type', '=', Models\ActivityCategory::COST_TYPE_VARIABLE)
            ->where('ac.balance_type', '=', Models\ActivityCategory::BALANCE_TYPE_EXPENSE)
            ->whereBetween('a.activity_date', [$begin_date . ' 00:00:00', $end_date . ' 23:59:59'])
            ->whereNull('a.delete_date')
            ->whereNull('acg.delete_date')
            ->whereNull('ac.delete_date')
            ->groupBy('a.activity_category_item_id')
            ->groupBy('acg.item_name')
            ->get();

        $groups = [];

        foreach ($rows as $row) {
            $amount = -1 * (int) $row->amount;

            if ($amount <= 0) {
                continue;
            }

            $groups[$row->activity_category_item_id] = [
                'item_name' => $row->item_name,
                'amount' => $amount
            ];
        }

        return $groups;
    }

    /**
     * 前の期間を基準とした増減率を百分率で返す。
     *
     * 整数に丸めない。合計のように元の額が大きいと、0.5% 未満の増減が 0 に
     * なって「増減なし」と見分けが付かなくなる (収入 1,127,668 円と
     * 1,130,364 円の比較が 0% になる)。表示の丸めは Html::comparisonRate
     * に任せる。
     *
     * @param int $current
     * @param int $previous 0 以外であること
     * @return float
     */
    private function calculateComparisonRate($current, $previous)
    {
        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    /**
     * 収支の金額を、小項目ごとと全体の合計で集計する。
     *
     * 小項目の金額は、支出が負で記録されているため符号を反転し、
     * 増えたら正になるよう揃える。
     *
     * 収入合計と支出合計は、集計表の表示と同じ振り分けにする。つまり小項目の
     * 収支タイプではなく、現金・クレジットごとの小計の符号で分ける
     * (@see ActivityService::calculateMonthlySummary)。支出合計も小項目と同じく
     * 正の値で返し、使った額が増えたら正になるようにする。
     *
     * @param int $user_id
     * @param string $begin_date
     * @param string $end_date
     * @return array ['groups' => [小項目 ID => 金額], 'totals' => [income|expense|total => 金額]]
     */
    private function sumCostByGroup($user_id, $begin_date, $end_date)
    {
        $rows = DB::table('activities AS a')
            ->select(DB::raw('a.activity_category_item_id, ac.balance_type, a.credit_flag, SUM(a.amount) AS amount'))
            ->join('activity_category_items AS acg', 'a.activity_category_item_id', '=', 'acg.id')
            ->join('activity_categories AS ac', 'acg.activity_category_id', '=', 'ac.id')
            ->where('a.user_id', '=', $user_id)
            ->whereBetween('a.activity_date', [$begin_date . ' 00:00:00', $end_date . ' 23:59:59'])
            ->whereNull('a.delete_date')
            ->whereNull('acg.delete_date')
            ->whereNull('ac.delete_date')
            ->groupBy('a.activity_category_item_id')
            ->groupBy('ac.balance_type')
            ->groupBy('a.credit_flag')
            ->get();

        $groups = [];
        $totals = ['income' => 0, 'expense' => 0, 'total' => 0];

        foreach ($rows as $row) {
            $amount = (int) $row->amount;

            if ($amount > 0) {
                $totals['income'] += $amount;
            } else {
                $totals['expense'] -= $amount;
            }

            $totals['total'] += $amount;

            $sign = ($row->balance_type == Models\ActivityCategory::BALANCE_TYPE_EXPENSE) ? -1 : 1;

            if (!isset($groups[$row->activity_category_item_id])) {
                $groups[$row->activity_category_item_id] = 0;
            }

            // 現金とクレジットで行が分かれるため、小項目ごとに足し合わせる。
            $groups[$row->activity_category_item_id] += $sign * $amount;
        }

        return ['groups' => $groups, 'totals' => $totals];
    }

    /**
     * 推移グラフ用に、小項目ごとの金額を期間順に取得する。
     *
     * 横軸の刻みは集計表と揃える (詳細検索の出力形式に従い年単位か月単位)。
     * 系列は小項目。小項目まで割ると系列が増えすぎて線が読めない。
     *
     * 支出は符号を反転して返し、使った額が多いほど線が上に来るようにする
     * (集計表は負のまま表示するので、そこだけ向きが異なる)。abs ではなく
     * 反転なのは、返金が上回って純額がプラスの小項目を下向きに出すため。
     *
     * ある期間に記録のない小項目は 0 ではなく null にする。0 を返すと
     * 「その期間は使っていない」と「記録がない」が区別できないため。
     *
     * @param int $user_id
     * @param Condition\YearlyTrendCondition $condition
     * @return array ['labels' => [...], 'series' => [['name' => 小項目, 'data' => [...]]]]
     */
    public function getYearlyTrend($user_id, Condition\YearlyTrendCondition $condition)
    {
        $empty = ['labels' => [], 'series' => []];

        $begin_year = (int) $condition->begin_year;
        $end_year = (int) $condition->end_year;

        if (!$begin_year || !$end_year || $begin_year > $end_year) {
            return $empty;
        }

        $date_group_format = ($condition->output_type == Condition\YearlySummaryCondition::OUTPUT_TYPE_YEARLY)
            ? '%Y'
            : '%Y/%m';

        $builder = DB::table('activities AS a')
            ->select(DB::raw(
                'DATE_FORMAT(a.activity_date, \'' . $date_group_format . '\') AS date_group,'
                .' ac.id AS activity_category_id,'
                .' ac.category_name,'
                .' ac.sort_order,'
                .' ac.balance_type,'
                .' SUM(a.amount) AS amount'
            ))
            ->join('activity_category_items AS acg', 'a.activity_category_item_id', '=', 'acg.id')
            ->join('activity_categories AS ac', 'acg.activity_category_id', '=', 'ac.id')
            ->where('a.user_id', '=', $user_id)
            ->whereBetween('a.activity_date', [
                sprintf('%04d-01-01 00:00:00', $begin_year),
                sprintf('%04d-12-31 23:59:59', $end_year),
            ])
            ->whereNull('a.delete_date')
            ->whereNull('acg.delete_date')
            ->whereNull('ac.delete_date');

        $this->applyKeyword($builder, $condition->keyword, 'a.');

        if ($condition->balance_type) {
            $builder->where('ac.balance_type', '=', $condition->balance_type);
        }

        // ONLY_FULL_GROUP_BY は列の別名を受け付けないため、実際の列名で指定する。
        $rows = $builder->groupBy('date_group')
            ->groupBy('ac.id')
            ->groupBy('ac.category_name')
            ->groupBy('ac.sort_order')
            ->groupBy('ac.balance_type')
            ->orderBy('date_group', 'asc')
            ->orderBy('ac.sort_order', 'asc')
            ->orderBy('ac.id', 'asc')
            ->get();

        if (!count($rows)) {
            return $empty;
        }

        // 横軸は集計表と同じく、記録のあった期間だけを並べる。
        $labels = [];
        $categories = [];
        $amounts = [];

        foreach ($rows as $row) {
            // 支出は負で記録されている。上向きに描くため向きを揃える。
            $sign = ($row->balance_type == Models\ActivityCategory::BALANCE_TYPE_EXPENSE) ? -1 : 1;

            $labels[$row->date_group] = true;
            $categories[$row->activity_category_id] = $row->category_name;
            $amounts[$row->activity_category_id][$row->date_group] = $sign * (int) $row->amount;
        }

        $labels = array_keys($labels);
        sort($labels);

        $series = [];

        foreach ($categories as $activity_category_id => $category_name) {
            $data = [];

            foreach ($labels as $label) {
                $data[] = $amounts[$activity_category_id][$label] ?? null;
            }

            $series[] = [
                'name' => $category_name,
                'data' => $data,
            ];
        }

        return ['labels' => $labels, 'series' => $series];
    }

    /**
     * 収支が発生している年のリストを取得する。
     *
     * @param int $user_id
     * @return array
     */
    public function getYearlyList($user_id)
    {
        $builder = $this->activity->where('user_id', '=', $user_id)
            ->select(DB::raw('DATE_FORMAT(activity_date, \'%Y\') AS yearly_group'))
            ->groupBy('yearly_group')
            ->orderBy('yearly_group', 'desc');
        $array = $builder->pluck('yearly_group', 'yearly_group')->all();

        return $array;
    }

    /**
     * 年別集計の結果を取得する。
     *
     * @param int $user_id
     * @param Condition\YearlySummaryCondition $condition
     * @return array
     */
    public function getYearlySummary($user_id, Condition\YearlySummaryCondition $condition)
    {
        // 年は利用者入力から来る。%s のままだと 'abc-01-01 00:00:00' のような
        // 日付にならない文字列を組み立ててしまうため、整数に寄せてから埋める。
        $begin_year = (int) $condition->begin_year;
        $end_year = (int) $condition->end_year;

        // 推移グラフ (@see ActivityService::getYearlyTrend) と同じ判定にする。
        // 片方だけ緩いと、同じ検索条件で表とグラフの対象期間がずれる。
        $is_valid_range = $begin_year && $end_year && $begin_year <= $end_year;

        $begin_date = sprintf('%04d-01-01 00:00:00', $begin_year);
        $end_date = sprintf('%04d-12-31 23:59:59', $end_year);

        // 既定も推移グラフと揃える。未指定なら月単位。
        $date_group_format = ($condition->output_type == Condition\YearlySummaryCondition::OUTPUT_TYPE_YEARLY)
            ? '%Y'
            : '%Y/%m';

        $builder = DB::table('activities AS a')
            ->select(DB::raw('DATE_FORMAT(a.activity_date, \'' . $date_group_format . '\') AS date_group, ac.id AS activity_category_id, ac.category_name, ac.cost_type, ac.balance_type, acg.id as activity_category_item_id, SUM(a.amount) AS group_amount'))
            ->join('activity_category_items AS acg', 'a.activity_category_item_id', '=', 'acg.id')
            ->join('activity_categories AS ac', 'acg.activity_category_id', '=', 'ac.id')
            ->where('a.user_id', $user_id)
            ->whereBetween('a.activity_date', [$begin_date, $end_date])
            ->whereNull('a.delete_date')
            ->whereNull('acg.delete_date')
            ->whereNull('ac.delete_date')
            // ONLY_FULL_GROUP_BY 対策。a.activity_category_item_id は内部結合で
            // acg.id と一致するため、以下の列は関数従属し group は分割されない。
            ->groupBy('date_group')
            ->groupBy('a.activity_category_item_id')
            ->groupBy('acg.id')
            ->groupBy('acg.sort_order')
            ->groupBy('ac.id')
            ->groupBy('ac.category_name')
            ->groupBy('ac.cost_type')
            ->groupBy('ac.balance_type')
            ->orderBy('date_group', 'DESC')
            ->orderBy('ac.cost_type', 'ASC')
            ->orderBy('ac.balance_type', 'ASC')
            ->orderBy('acg.sort_order', 'ASC');

        // 推移グラフ (@see ActivityService::getYearlyTrend) と同じ当たり方をさせる。
        $this->applyKeyword($builder, $condition->keyword, 'a.');

        $result = $is_valid_range ? $builder->get() : collect();

        $data = [];
        $footers = [
            'yearly_total_activity_categories' => [],
            'yearly_total_expense_amount' => 0,
            'yearly_total_income_amount' => 0,
            'yearly_total_result_amount' => 0
        ];

        if (count($result)) {
            foreach ($result as $name => $value) {
                if (!isset($data[$value->date_group])) {
                    $data[$value->date_group]['total_expense_amount'] = 0;
                    $data[$value->date_group]['total_income_amount'] = 0;
                    $data[$value->date_group]['total_amount'] = 0;
                }

                if (!isset($data[$value->date_group]['amount'][$value->cost_type][$value->activity_category_id][$value->activity_category_item_id])) {
                    $data[$value->date_group]['amount'][$value->cost_type][$value->activity_category_id][$value->activity_category_item_id] = 0;
                }

                $data[$value->date_group]['amount'][$value->cost_type][$value->activity_category_id][$value->activity_category_item_id] += $value->group_amount;

                if ($value->balance_type == Models\ActivityCategory::BALANCE_TYPE_EXPENSE) {
                    $data[$value->date_group]['total_expense_amount'] += $value->group_amount;
                    $footers['yearly_total_expense_amount'] += $value->group_amount;

                } else {
                    $data[$value->date_group]['total_income_amount'] += $value->group_amount;
                    $footers['yearly_total_income_amount'] += $value->group_amount;
                }

                $data[$value->date_group]['total_amount'] += $value->group_amount;
                $footers['yearly_total_result_amount'] += $value->group_amount;

                if (!isset($footers['yearly_total_activity_categories'][$value->activity_category_item_id])) {
                    $footers['yearly_total_activity_categories'][$value->activity_category_item_id] = 0;
                }

                $footers['yearly_total_activity_categories'][$value->activity_category_item_id] += $value->group_amount;
            }
        }

        $headers = $this->activity_category->getCategoryItemData($user_id);

        // 小項目数を取得
        $header_size = [
            'total' => 0,
            'cost_type' => [
                Models\ActivityCategory::COST_TYPE_VARIABLE => 0,
                Models\ActivityCategory::COST_TYPE_CONSTANT => 0
            ]
        ];

        foreach ($headers as $cost_type => $activity_category_items) {
            foreach ($activity_category_items as $activity_categories) {
                $count = sizeof($activity_categories['activity_category_items']);

                $header_size['total'] += $count;
                $header_size['cost_type'][$cost_type] += $count;
            }
        }

        $summary = [];
        $summary['headers'] = $headers;
        $summary['header_size'] = $header_size;
        $summary['data'] = $data;
        $summary['footers'] = $footers;

        return $summary;
    }

    /**
     * 変動収支履歴を取得する。
     *
     * 発生日が今日より後のものは返さない。家賃や給与を先の日付で登録すると、
     * それが常に一覧の先頭を占め、「最近の」履歴として直近に記録したものが
     * 押し出されていた。今月の変動支出 (@see ActivityService::buildComparisonPeriod)
     * も今日までで区切っており、ダッシュボードの 2 つで基準が食い違わないようにする。
     *
     * @param int $user_id
     * @param int $limit
     * @return Collection
     */
    public function getHistories($user_id, $limit)
    {
        $builder = $this->activity->where('user_id', '=', $user_id)
            ->where('activity_date', '<=', date('Y-m-d'))
            ->orderBy('activity_date', 'desc')
            ->limit($limit);

        return $builder->get();
    }

    /**
     * 場所別の収支ランキングを取得する。
     *
     * @param int $user_id
     * @param RankingCondition $condition
     * @return Collection
     */
    public function getRankingByLocation($user_id, $condition)
    {
        $builder = DB::table('activities AS a')
            // 場所ごとの集計だが item_name は場所に関数従属しない (同じ場所を
            // 複数の小項目で使える)。従来はどれか 1 件が任意に選ばれていたので、
            // MIN で明示的に 1 件へ畳む (MariaDB に ANY_VALUE はない)。
            ->select(DB::raw('MIN(acg.item_name) AS item_name, a.location, COUNT(a.location) AS count, SUM(a.amount) AS amount'))
            ->join('activity_category_items AS acg', 'a.activity_category_item_id', '=', 'acg.id')
            ->where('a.user_id', '=', $user_id)
            ->where('a.location', '!=', '');

        $date_range = $condition->getDateRange();

        if (strlen($date_range->begin_date)) {
            if (strlen($date_range->end_date)) {
                $builder->whereBetween('a.activity_date', [$date_range->begin_date, $date_range->end_date]);
            } else {
                $builder->where('a.activity_date', '>=', $date_range->begin_date);
            }

        } else if (strlen($date_range->end_date)) {
            $builder->where('a.activity_date', '<=', $date_range->end_date);
        }

        $builder->whereNull('a.delete_date')
            ->whereNull('acg.delete_date')
            ->groupBy('a.location')
            ->orderBy('count', 'desc')
            ->limit($condition->limit);

        return $builder->get();
    }


    /**
     * 利用額別の支出ランキングを取得する。
     *
     * @param int $user_id
     * @param RankingCondition $condition
     * @return Collection
     */
    public function getRankingByExpense($user_id, $condition)
    {
        $builder = $this->activity->where('user_id', '=', $user_id)
            ->whereHas('activityCategoryItem', function($builder) {
                $builder->whereHas('activityCategory', function($builder) {
                    $builder->where('cost_type', '=', Models\ActivityCategory::COST_TYPE_VARIABLE);
                    $builder->where('balance_type', '=', Models\ActivityCategory::BALANCE_TYPE_EXPENSE);
                });
            })
            ->activityDate($condition->getDateRange())
            ->orderBy('amount', 'asc')
            ->limit($condition->limit);

        return $builder->get();
    }
}
