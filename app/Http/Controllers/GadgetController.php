<?php
namespace App\Http\Controllers;

use Auth;
use Request;
use Validator;
use View;

use App\Services;

class GadgetController extends Controller {
    /**
     * 棒に並べる小項目の数。小項目は利用者が好きなだけ作れるため、
     * 全部並べるとダッシュボードが際限なく伸びる。
     */
    const VARIABLE_EXPENSE_GROUP_LIMIT = 5;

    /**
     * 並べる履歴の件数。隣に置いた今月の変動支出と同じ数にして、
     * 2 つの欄の下端を揃える。
     */
    const ACTIVITY_HISTORY_LIMIT = 5;

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
     * 今月の変動支出を、小項目ごとの棒と前月同時点との差額で表示する。
     *
     * ?day= で今月を区切る日を指定できる。省略すると今日。
     *
     * 日は今月の 1 から末日までに閉じる。範囲の外は日付に組み立てると
     * 翌月や前月へずれた期間で黙って集計される (10/32 は 11/1 になる)。
     * 小数や指数表記も受け付けない。
     */
    public function variableExpense()
    {
        $validator = Validator::make(Request::only('day'), [
            'day' => 'nullable|integer|between:1,' . date('t')
        ]);

        if ($validator->fails()) {
            abort(422);
        }

        $day = Request::input('day');

        $data = [];
        $data['expense'] = $this->activity->getVariableExpenseComparison(
            Auth::id(),
            self::VARIABLE_EXPENSE_GROUP_LIMIT,
            $day === null ? null : (int) $day
        );

        return View::make('gadget/variable_expense', $data);
    }

    /**
     * 最近の収支履歴を表示する。
     */
    public function activityHistory()
    {
        $data = [];
        $data['histories'] = $this->activity->getHistories(Auth::id(), self::ACTIVITY_HISTORY_LIMIT);

        return View::make('gadget/activity_history', $data);
    }
}
