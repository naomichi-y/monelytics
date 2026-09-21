<?php
namespace App\Http\Controllers;

use Auth;
use View;

use App\Models;
use App\Services;

class DashboardController extends Controller
{
    private $activity_category;

    /**
     * @see BaseController::__construct()
     */
    public function __construct(Services\ActivityCategoryService $activity_category)
    {
        parent::__construct();

        $this->activity_category = $activity_category;
    }

    public function index()
    {
        $data = [];
        // かんたん入力の送り先は cost/variable で、作られるのは変動収支。
        // 固定収支の科目まで選べると、選んだとおりに登録されないか、固定費を
        // 二重に積むことになる。入力欄の一覧を送り先と揃える
        // (@see Cost\VariableController::create)。
        $data['activity_category_groups'] = $this->activity_category->getCategoryGroupList(
            Auth::id(),
            Models\ActivityCategory::COST_TYPE_VARIABLE,
            true
        );

        return View::make('dashboard/index', $data);
    }
}
