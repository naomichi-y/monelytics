<?php
namespace Monelytics\Models;

class ActivityCategory extends BaseModel {
  const COST_TYPE_VARIABLE = 1;
  const COST_TYPE_CONSTANT = 2;
  const BALANCE_TYPE_EXPENSE = 1;
  const BALANCE_TYPE_INCOME = 2;

  protected $guarded = array('id');
  protected $rules = array(
    'category_name' => 'required|max:32',
    'content' => 'max:255',
    'cost_type' => 'required',
    'balance_type' => 'required'
  );

  public function user()
  {
    return $this->belongTo('Monelytics\Models\User');
  }

  public function activityCategoryGroups()
  {
    return $this->hasMany('Monelytics\Models\ActivityCategoryGroup');
  }

  public static function boot()
  {
    parent::boot();

    static::deleting(function($activity_category) {
      $activity_category_groups = $activity_category->activityCategoryGroups()->get();

      foreach ($activity_category_groups as $activity_category_group) {
        $activity_category_group->delete();
      }
    });
  }
}
