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
    private $activity_category_group;

    /**
     * コンストラクタ。
     *
     * @param Models\Activity $activity
     * @param Servicss\ActivityCategoryService $activity_category
     * @param Services\ActivityCategoryGroupService $activity_category_group
     */
    public function __construct(
        Models\Activity $activity,
        Services\ActivityCategoryService $activity_category,
        Services\ActivityCategoryGroupService $activity_category_group)
    {
        $this->activity = $activity;
        $this->activity_category = $activity_category;
        $this->activity_category_group = $activity_category_group;
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

                $activity_category_group = $this->activity_category_group->find($user_id, $value['activity_category_group_id']);
                $balance_type = $activity_category_group->activityCategory->balance_type;

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
     * 日別集計の結果を取得する。
     *
     * @param int $user_id
     * @param Condition\DailyPaginateCondition $condition
     * @return Illuminate\Pagination\Paginator
     */
    public function getDailyPaginate($user_id, Condition\DailyPaginateCondition $condition)
    {
        $builder = $this->activity->with('activityCategoryGroup')
            ->where('user_id', '=', $user_id);

        // 収支タイプ
        if ($condition->cost_type !== null) {
            $builder->whereHas('activityCategoryGroup', function($builder) use ($condition) {
                $builder->whereHas('activityCategory', function($builder) use ($condition) {
                    $builder->where('cost_type', '=', $condition->cost_type);
                });
            });
        }

        $builder->activityDate($condition->getDateRange());

     // 科目
        if (sizeof($condition->activity_category_group_id)) {
            $builder->whereIn('activity_category_group_id', $condition->activity_category_group_id);
        }

        // 場所・内容
        if (strlen($condition->keyword)) {
            $keyword = $condition->keyword;

            $builder->where(function($builder) use ($keyword) {
                $query_keyword = '%' .  addslashes($keyword) . '%';

                $builder->where('location', 'LIKE', $query_keyword);
                $builder->orWhere('content', 'LIKE', $query_keyword);
            });
        }

        if (strlen($condition->location)) {
            $builder->where('location', '=', addslashes($condition->location));
        }

        // クレジットカード
        if (strlen($condition->credit_flag)) {
            $builder->where('credit_flag', '=', $condition->credit_flag);
        }

        // 特別収支
        if (strlen($condition->special_flag)) {
            $builder->where('special_flag', '=', $condition->special_flag);
        }

        // 並び順
        $builder->orderBy($condition->sort_field, $condition->sort_type);
        $builder->orderBy('create_date', 'desc');

        $paginate = $builder->paginate($condition->limit);
        $paginate->appends($condition->toArray());
        $paginate->total_amount = $builder->sum('amount');

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
     * @param int $id
     * @param array $fields
     * @param array &$errors
     * @return bool
     */
    public function update($id, array $fields, &$errors = [])
    {
        $result = false;

        if ($this->activity->validate($fields)) {
            $activity = $this->activity->findOrFail($id);
            $activity->fill($fields);

            $balance_type = $activity->activityCategoryGroup->activityCategory->balance_type;

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
            ->whereHas('activityCategoryGroup', function($builder) {
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
            ->select(DB::raw('a.id AS activity_id, ac.id AS activity_category_id, ac.category_name, acg.id, DATE_FORMAT(a.activity_date, \'%Y/%m/%d\') AS activity_date, acg.group_name, a.content, acg.credit_flag as default_credit_flag, a.amount, a.credit_flag'))
            ->rightJoin('activity_category_groups AS acg', function($join) use($begin_date, $end_date)
            {
                // $join(JoinClause)はwhereBetween()をサポートしていないので日付はwhere()で検索
                $join->on('a.activity_category_group_id', '=', 'acg.id')
                    ->where('a.activity_date', '>=', $begin_date)
                    ->where('a.activity_date', '<=', $end_date)
                    ->whereNull('a.delete_date');
            })
            ->join('activity_categories AS ac', 'acg.activity_category_id', '=', 'ac.id')
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
                    'activity_category_groups' => []
                ];
            }

            $result[$data->activity_category_id]['activity_category_groups'][] = $data;
        }

        return $result;
    }

    /**
     * 対象年月の固定収支データを取得する。
     *
     * @param int $user_id
     * @param string $target_month
     * @param int $activity_category_group_id
     * @return Activity
     */
    public function findConstantCost($user_id, $target_month, $activity_category_group_id)
    {
        $begin_date = sprintf('%s-01', str_replace('/', '-', $target_month));
        $last_day = date('d', strtotime('last day of ' . $target_month));
        $end_date = sprintf('%s-%s', $target_month, $last_day);

        $builder = $this->activity->where('user_id', '=', $user_id)
            ->whereBetween('activity_date', [$begin_date, $end_date])
            ->where('activity_category_group_id', '=', $activity_category_group_id);

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
                $current_constant_cost = $this->findConstantCost($user_id, $target_month, $value['activity_category_group_id']);

                // 対象の固定収支レコードが登録済みの場合、登録済みデータと入力データを比較し、違いがあればレコードを更新する
                if ($current_constant_cost) {
                    $has_diff = false;

                    // データの整形
                    $activity_date = str_replace('/', '-', $value['activity_date']);
                    $balance_type = $current_constant_cost->activityCategoryGroup->activityCategory->balance_type;
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
                    $activity_category_group = $this->activity_category_group->find($user_id, $value['activity_category_group_id']);
                    $balance_type = $activity_category_group->activityCategory->balance_type;
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
            ->select(DB::raw('ac.cost_type, ac.id AS activity_category_id, ac.category_name, acg.id, acg.group_name, a.credit_flag, a.special_flag, IFNULL(SUM(a.amount), 0) AS amount'))
            ->rightJoin('activity_category_groups AS acg', function($join) use($date_range)
            {
                // @see Activity::getConstantCosts()
                $join->on('a.activity_category_group_id', '=', 'acg.id')
                    ->whereNull('a.delete_date');

                if (strlen($date_range->begin_date)) {
                    $join->where('a.activity_date', '>=', $date_range->begin_date);
                }

                if (strlen($date_range->end_date)) {
                    $join->where('a.activity_date', '<=', $date_range->end_date);
                }
            })
            ->join('activity_categories AS ac', 'acg.activity_category_id', '=', 'ac.id')
            ->where('ac.user_id', '=', $user_id)
            ->whereNull('acg.delete_date')
            ->groupBy('ac.cost_type')
            ->groupBy('acg.id')
            ->groupBy('a.credit_flag')
            ->groupBy('a.special_flag')
            ->orderBy('ac.cost_type', 'asc')
            ->orderBy('ac.sort_order', 'asc')
            ->orderBy('acg.sort_order', 'asc');

        return $this->calculateMonthlySummary($builder->get());
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
            'special_use_amount' => 0,
            'special_unuse_amount' => 0,
            'income_amount' => 0
        ];
        $expense_summary = [
            'cash_amount' => 0,
            'credit_amount' => 0,
            'special_use_amount' => 0,
            'special_unuse_amount' => 0,
            'expense_amount' => 0
        ];
        $cost_size = [
            Models\ActivityCategory::COST_TYPE_VARIABLE => 0,
            Models\ActivityCategory::COST_TYPE_CONSTANT => 0
        ];

        foreach ($data as $value) {
            // 科目配列の初期化
            if (!isset($category_summary[$value->cost_type][$value->activity_category_id][$value->id])) {
                $category_summary[$value->cost_type][$value->activity_category_id]['category_name'] = $value->category_name;
                $data = &$category_summary[$value->cost_type][$value->activity_category_id]['data'][$value->id];

                if (!isset($data['group_name'])) {
                    $cost_size[$value->cost_type]++;
                }

                $data['group_name'] = $value->group_name;

                if (!isset($data['cash_amount'])) {
                    $data['cash_amount'] = 0;
                }

                if (!isset($data['credit_amount'])) {
                    $data['credit_amount'] = 0;
                }

                if (!isset($data['special_use_amount'])) {
                    $data['special_use_amount'] = 0;
                }

                if (!isset($data['special_unuse_amount'])) {
                    $data['special_unuse_amount'] = 0;
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

            // 特別収支の加算
            if ($value->special_flag == Models\Activity::SPECIAL_FLAG_USE) {
                $data['special_use_amount'] += $value->amount;
            } else {
                $data['special_unuse_amount'] += $value->amount;
            }

            // 科目ごとの合計加算
            $data['group_amount'] += $value->amount;

            // 全科目の収入加算
            if ($value->amount > 0) {
                if ($value->credit_flag == Models\Activity::CREDIT_FLAG_UNUSE) {
                    $income_summary['cash_amount'] += $value->amount;
                } else {
                    $income_summary['credit_amount'] += $value->amount;
                }

                if ($value->special_flag == Models\Activity::SPECIAL_FLAG_USE) {
                    $income_summary['special_use_amount'] += $value->amount;
                } else {
                    $income_summary['special_unuse_amount'] += $value->amount;
                }

                $income_summary['income_amount'] += $value->amount;

            // 全科目の支出加算
            } else {
                if ($value->credit_flag == Models\Activity::CREDIT_FLAG_UNUSE) {
                    $expense_summary['cash_amount'] += $value->amount;
                } else {
                    $expense_summary['credit_amount'] += $value->amount;
                }

                if ($value->special_flag == Models\Activity::SPECIAL_FLAG_USE) {
                    $expense_summary['special_use_amount'] += $value->amount;
                } else {
                    $expense_summary['special_unuse_amount'] += $value->amount;
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
                ->join('activity_category_groups AS acg', 'a.activity_category_group_id', '=', 'acg.id')
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
            return null;
        }

        return $result;
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
        $begin_date = sprintf('%s-01-01 00:00:00', $condition->begin_year);
        $end_date = sprintf('%s-12-31 23:59:59', $condition->end_year);
        $date_group_format = ($condition->output_type == 1) ? '%Y/%m' : '%Y';

        $builder = DB::table('activities AS a')
            ->select(DB::raw('DATE_FORMAT(a.activity_date, \'' . $date_group_format . '\') AS date_group, ac.id AS activity_category_id, ac.category_name, ac.cost_type, ac.balance_type, acg.id as activity_category_group_id, a.special_flag, SUM(a.amount) AS group_amount'))
            ->join('activity_category_groups AS acg', 'a.activity_category_group_id', '=', 'acg.id')
            ->join('activity_categories AS ac', 'acg.activity_category_id', '=', 'ac.id')
            ->where('a.user_id', $user_id)
            ->whereBetween('a.activity_date', [$begin_date, $end_date])
            ->whereNull('a.delete_date')
            ->whereNull('acg.delete_date')
            ->whereNull('ac.delete_date')
            ->groupBy('date_group')
            ->groupBy('a.activity_category_group_id')
            ->groupBy('a.special_flag')
            ->orderBy('date_group', 'DESC')
            ->orderBy('ac.cost_type', 'ASC')
            ->orderBy('ac.balance_type', 'ASC')
            ->orderBy('acg.sort_order', 'ASC');
        $result = $builder->get();

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

                if (!isset($data[$value->date_group]['amount'][$value->cost_type][$value->activity_category_id][$value->activity_category_group_id])) {
                    $data[$value->date_group]['amount'][$value->cost_type][$value->activity_category_id][$value->activity_category_group_id] = 0;
                }

                $data[$value->date_group]['amount'][$value->cost_type][$value->activity_category_id][$value->activity_category_group_id] += $value->group_amount;

                if ($value->balance_type == Models\ActivityCategory::BALANCE_TYPE_EXPENSE) {
                    $data[$value->date_group]['total_expense_amount'] += $value->group_amount;
                    $footers['yearly_total_expense_amount'] += $value->group_amount;

                } else {
                    $data[$value->date_group]['total_income_amount'] += $value->group_amount;
                    $footers['yearly_total_income_amount'] += $value->group_amount;
                }

                $data[$value->date_group]['total_amount'] += $value->group_amount;
                $footers['yearly_total_result_amount'] += $value->group_amount;

                if (!isset($footers['yearly_total_activity_categories'][$value->activity_category_group_id])) {
                    $footers['yearly_total_activity_categories'][$value->activity_category_group_id] = 0;
                }

                $footers['yearly_total_activity_categories'][$value->activity_category_group_id] += $value->group_amount;
            }
        }

        $headers = $this->activity_category->getCategoryGroupData($user_id);

        // 科目数を取得
        $header_size = [
            'total' => 0,
            'cost_type' => [
                Models\ActivityCategory::COST_TYPE_VARIABLE => 0,
                Models\ActivityCategory::COST_TYPE_CONSTANT => 0
            ]
        ];

        foreach ($headers as $cost_type => $activity_category_groups) {
            foreach ($activity_category_groups as $activity_categories) {
                $count = sizeof($activity_categories['activity_category_groups']);

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
     * 今月の収支ステータスを取得する。
     *
     * @param int $user_id
     * @return array
     */
    public function getBalanceOfPaymentStatus($user_id)
    {
        $begin_date = date('Y-m-01');
        $end_date = date('Y-m-t');

        $builder = DB::table('activities AS a')
            ->select(DB::raw('ac.balance_type, SUM(a.amount) AS amount'))
            ->join('activity_category_groups AS acg', 'a.activity_category_group_id', '=', 'acg.id')
            ->join('activity_categories AS ac', 'acg.activity_category_id', '=', 'ac.id')
            ->where('a.user_id', '=', $user_id)
            ->whereBetween('a.activity_date', [$begin_date, $end_date])
            ->whereNull('a.delete_date')
            ->groupBy('ac.balance_type');

        $result = [
            0 => 0,
            Models\ActivityCategory::BALANCE_TYPE_EXPENSE => 0,
            Models\ActivityCategory::BALANCE_TYPE_INCOME => 0
        ];

        foreach ($builder->get() as $data) {
            $result[0] += $data->amount;
            $result[$data->balance_type] = $data->amount;
        }

        return $result;
    }

    /**
     * ヒートマップを取得する。
     *
     * @param int $user_id
     * @param int $week
     * @return Collection
     */
    public function getHeats($user_id, $week)
    {
        $interval = 'P' . $week . 'W';

        $calc_date = new DateTime();
        $calc_date->sub(new DateInterval($interval));

        // $week週前が日曜以外の場合、以前の月曜日を取得する (月曜を起点として集計を行なう)
        while ($calc_date->format('w') != 1) {
            $calc_date->sub(new DateInterval('P1D'));
        }

        $begin_date = $calc_date->format('Y-m-d');

        // 収支レコードを取得
        $builder = $this->activity->select(DB::raw('activity_date, SUM(amount) AS amount'))
            ->where('user_id', '=', $user_id)
            ->where('activity_date', '>=', $begin_date)
            ->groupBy('activity_date')
            ->orderBy('activity_date', 'desc');

        $result = $builder->pluck('amount', 'activity_date')->all();

        // $days日分の日付配列を生成
        $day_array = [];
        $current_date = new DateTIme();

        while ($calc_date->getTimestamp() <= $current_date->getTimestamp()) {
            $week = $calc_date->format('W');
            $search_date = $calc_date->format('Y-m-d');

            if (isset($result[$search_date])) {
                $array = [
                    'amount' => $result[$search_date],
                    'heat_level' => $this->calculateHeatLevel($result[$search_date])
                ];

                $day_array[$week][$search_date] = $array;

            } else {
                $array = [
                    'amount' => 0,
                    'heat_level' => $this->calculateHeatLevel(0)
                ];
                $day_array[$week][$search_date] = $array;
            }

            $calc_date->add(new DateInterval('P1D'));
        }

        return $day_array;
    }

    /**
     * ヒートマップのレベルを計算する。
     *
     * @param int $amount
     * @return int
     */
    private function calculateHeatLevel($amount)
    {
        $level = null;

        if ($amount == 0) {
            $level = 0;

        } else {
            $levels = [50000, 10000, 5000, 3000, 1000, 0, -1000, -3000, -5000, -10000, -50000];
            $j = sizeof($levels);

            for ($i = 0; $i < $j; $i++) {
                if ($amount > $levels[$i]) {
                    $level = $i + 1;
                    break;
                }
            }

            if ($level === null) {
                $level = $j + 1;
            }
        }

        return $level;
    }

    /**
     * 変動収支履歴を取得する。
     *
     * @param int $user_id
     * @param int $limit
     * @return Collection
     */
    public function getHistories($user_id, $limit)
    {
        $builder = $this->activity->where('user_id', '=', $user_id)
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
            ->select(DB::raw('acg.group_name, a.location, COUNT(a.location) AS count, SUM(a.amount) AS amount'))
            ->join('activity_category_groups AS acg', 'a.activity_category_group_id', '=', 'acg.id')
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
            ->whereHas('activityCategoryGroup', function($builder) {
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
