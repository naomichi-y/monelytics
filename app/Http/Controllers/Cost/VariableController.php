<?php
namespace App\Http\Controllers\Cost;

use Auth;
use Request;
use Lang;
use Redirect;
use Session;
use View;

use App\Services;
use App\Models;

class VariableController extends \App\Http\Controllers\Controller {
    private $activity;
    private $activity_category;

    /**
     * @see BaseController::__construct()
     */
    public function __construct(Services\ActivityService $activity, Services\ActivityCategoryService $activity_category)
    {
        parent::__construct();

        $this->activity = $activity;
        $this->activity_category = $activity_category;
    }

    /**
     * 変動収支を入力する。
     */
    public function create()
    {
        $data = [];
        $data['input_size'] = 8;
        $data['activity_category_groups'] = $this->activity_category->getCategoryGroupList(Auth::id(), Models\ActivityCategory::COST_TYPE_VARIABLE, true);

        return View::make('cost/variable/create', $data);
    }

    /**
     * 変動収支を登録する。
     */
    public function store()
    {
        $fields = Request::only(
            'activity_date',
            'activity_category_group_id',
            'amount',
            'location',
            'content',
            'credit_flag',
            'special_flag'
        );
        $errors = [];

        if (!$this->activity->createVariableCosts(Auth::id(), $fields, $errors)) {
            return Redirect::back()
                ->withErrors($errors)
                ->withInput();
        }

        return Redirect::back()
            ->with('success', Lang::get('validation.custom.create_success'));
    }

    /**
     * 変動収支を編集する。
     */
    public function edit($id)
    {
        $user_id = Auth::id();

        $data = [];
        $data['id'] = $id;
        $data['activity'] = $this->activity->find($user_id, $id);
        $data['activity_category_groups'] = $this->activity_category->getCategoryGroupList($user_id);

        return View::make('cost/variable/edit', $data);
    }

    /**
     * 変動収支を更新する。
     */
    public function update($id)
    {
        $fields = Request::only(
            'activity_date',
            'activity_category_group_id',
            'amount',
            'location',
            'content',
            'credit_flag',
            'special_flag'
        );
        $data = [];
        $errors = [];

        if ($this->activity->update($id, $fields, $errors)) {
            $data['result'] = true;

            Session::flash('success', Lang::get('validation.custom.update_success'));

        } else {
            $data['result'] = false;
            $data['errors'] = $errors;
        }

        return json_encode($data);
    }

    /**
     * 変動収支を削除する。
     */
    public function destroy($id)
    {
        $this->activity->delete(Auth::id(), $id);

        return Redirect::back()
            ->with('success', Lang::get('validation.custom.delete_success'));
    }
}
