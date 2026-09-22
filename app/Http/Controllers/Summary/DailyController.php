<?php
namespace App\Http\Controllers\Summary;

use Auth;
use Request;
use View;

use App\Libraries\Condition;
use App\Services;

class DailyController extends \App\Http\Controllers\Controller {
    private $activity;
    private $activity_category;

    /**
     * @see BaseController::__construct()
     */
    public function __construct(Services\ActivityService $activity, Services\ActivityCategoryService $activity_category)
    {
        $this->activity = $activity;
        $this->activity_category = $activity_category;

        parent::__construct();
    }

    /**
     * 日別集計を表示する。
     */
    public function index()
    {
        $fields = Request::only(
            'date_year',
            'date_month',
            'begin_date',
            'end_date',
            'activity_category_item_id',
            'keyword',
            'location',
            'credit_flag',
            'cost_type',
            'sort_field',
            'sort_type'
        );

        $user_id = Auth::id();
        $condition = new Condition\DailyPaginateCondition($fields);

        $data = [];
        $data['month_list'] = $this->activity->getMonthList($user_id, true);
        $data['activities'] = $this->activity->getDailyPaginate($user_id, $condition);

        // 一覧の場所リンクが、今表示している絞り込みをそのまま引き継ぐために渡す。
        // View で Request から組み直すと、Condition が既定値を埋めている
        // sort_field や limit が抜け、リンクを踏んだ先で並び順が変わる。
        $data['condition'] = $condition;

        // 対象期間はリンクに実日付で載せる。date_month だけを渡すと、
        // 期間の解釈 (月末が 28 日か 31 日か、date_month が無いときは全期間)
        // を踏んだ先で組み直すことになる。月別集計の利用頻度ランキングも
        // 同じ形で日別集計へ送っている。
        $data['date_range'] = $condition->getDateRange();

        return View::make('summary/daily/index', $data);
    }

    /**
     * 検索条件を指定する。
     */
    public function condition()
    {
        $user_id = Auth::id();
        $data = [];

        // 月リスト
        $data['month_list'] = $this->activity->getMonthList($user_id, true);

        // クレジットカードの規定値
        $credit_flag = Request::input('credit_flag');

        $data['credit_flag_all'] = true;
        $data['credit_flag_on'] = false;
        $data['credit_flag_off'] = false;

        if ($credit_flag == '1') {
            $data['credit_flag_on'] = true;
            $data['credit_flag_all'] = false;

        } else if ($credit_flag === '0') {
            $data['credit_flag_off'] = true;
            $data['credit_flag_all'] = false;
        }

        // 小項目リスト
        $data['activity_category_items'] = $this->activity_category->getCategoryItemList($user_id);

        // 日付範囲が入っているかの判定は Condition に持たせてある。範囲と月指定は
        // 同時に効かない (getDateRange が範囲を優先する) ので、範囲があるときは
        // 月のセレクトを最初から操作不可にして開く。JS だけで無効化すると、
        // 範囲を入れた状態で開き直した 1 瞬だけ操作できてしまう。
        $data['condition'] = new Condition\BaseDateCondition(
            Request::only('date_month', 'begin_date', 'end_date')
        );

        return View::make('summary/daily/condition', $data);
    }
}
