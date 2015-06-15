<?php
class DashboardController extends BaseController
{
  private $activity_category;

  /**
   * @see BaseController::__construct()
   */
  public function __construct(ActivityCategoryService $activity_category)
  {
    parent::__construct();

    $this->activity_category = $activity_category;
  }

  public function index()
  {
    $data = array();
    $data['activity_category_groups'] = $this->activity_category->getCategoryGroupList(Auth::id(), null, true);

    return View::make('dashboard/index', $data);
  }
}
