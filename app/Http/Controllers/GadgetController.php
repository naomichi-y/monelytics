<?php
namespace App\Http\Controllers;

use Auth;
use View;

use App\Services;

class GadgetController extends Controller {
    /**
     * 棒に並べる科目の数。科目は利用者が好きなだけ作れるため、
     * 全部並べるとダッシュボードが際限なく伸びる。
     */
    const VARIABLE_EXPENSE_GROUP_LIMIT = 5;

    private $activity;

    /**
     * @see BaseController::__construct()
     */
    public function __construct(Services\ActivityService $activity)
    {
        parent::__construct();

        $this->activity = $activity;
    }

    /**
     * 今月の変動支出を、科目ごとの棒と前月同時点との差額で表示する。
     */
    public function variableExpense()
    {
        $data = [];
        $data['expense'] = $this->activity->getVariableExpenseComparison(Auth::id(), self::VARIABLE_EXPENSE_GROUP_LIMIT);

        return View::make('gadget/variable_expense', $data);
    }

    /**
     * 最近の収支履歴を表示する。
     */
    public function activityHistory()
    {
        $data = [];
        $data['histories'] = $this->activity->getHistories(Auth::id(), 4);

        return View::make('gadget/activity_history', $data);
    }
}
