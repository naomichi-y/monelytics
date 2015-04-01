<?php
class ActivityCategory extends BaseModel {
  const COST_TYPE_VARIABLE = 1;
  const COST_TYPE_CONSTANT = 2;
  const BALANCE_TYPE_EXPENSE = 1;
  const BALANCE_TYPE_INCOME = 2;

  protected $table = 'activity_categories';
  protected $guarded = array('id');
  protected $validate_rules = array(
    'category_name' => 'required|max:32',
    'content' => 'max:255',
    'cost_type' => 'required',
    'balance_type' => 'required'
  );

  public function activityCategoryGroup()
  {
    return $this->hasMany('ActivityCategoryGroup', 'activity_category_id', 'id');
  }
}
