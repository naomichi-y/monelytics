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
            'activity_category_group_id',
            'keyword',
            'location',
            'credit_flag',
            'special_flag',
            'cost_type',
            'sort_field',
            'sort_type'
        );

        $user_id = Auth::id();
        $condition = new Condition\DailyPaginateCondition($fields);

        $data = [];
        $data['month_list'] = $this->activity->getMonthList($user_id, true);
        $data['activities'] = $this->activity->getDailyPaginate($user_id, $condition);

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

        //特別収支の規定値
        $special_flag = Request::input('special_flag');

        $data['special_flag_all'] = true;
        $data['special_flag_on'] = false;
        $data['special_flag_off'] = false;

        if ($special_flag === '1') {
            $data['special_flag_on'] = true;
            $data['special_flag_all'] = false;

        } else if ($special_flag === '0') {
            $data['special_flag_off'] = true;
            $data['special_flag_all'] = false;
        }

        // 科目リスト
        $data['activity_category_groups'] = $this->activity_category->getCategoryGroupList($user_id);

        return View::make('summary/daily/condition', $data);
    }
}
