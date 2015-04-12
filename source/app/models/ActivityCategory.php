<?php
class ActivityCategory extends BaseModel {
  const COST_TYPE_VARIABLE = 1;
  const COST_TYPE_CONSTANT = 2;
  const BALANCE_TYPE_EXPENSE = 1;
  const BALANCE_TYPE_INCOME = 2;

  protected $guarded = array('id');
  protected $validate_rules = array(
    'category_name' => 'required|max:32',
    'content' => 'max:255',
    'cost_type' => 'required',
    'balance_type' => 'required'
  );

  public function user()
  {
    return $this->belongTo('User');
  }

  public function activityCategoryGroup()
  {
    return $this->hasMany('ActivityCategoryGroup');
  }

  public static function boot()
  {
    parent::boot();

    static::deleting(function($activity_category) {
      $activity_category_groups = $activity_category->activityCategoryGroup()->get();

      foreach ($activity_category_groups as $activity_category_group) {
        $activity_category_group->delete();
      }
    });
  }
}
