<?php
namespace App\Models;

class ActivityCategoryGroup extends BaseModel {
  const CREDIT_FLAG_ENABLE = 1;
  const CREDIT_FLAG_DISABLE = 0;

  protected $guarded = ['id'];
  protected $rules = [
    'activity_category_id' => 'required',
    'group_name' => 'required|max:32',
    'content' => 'max:255'
  ];

  public function user()
  {
    return $this->belongTo('App\Models\User');
  }

  public function activityCategory()
  {
    return $this->belongsTo('App\Models\ActivityCategory');
  }

  public function activity()
  {
    return $this->hasMany('App\Models\Activity');
  }

  public static function boot()
  {
    parent::boot();

    static::deleting(function($activity_category_group) {
      $activity_category_group->activity()->delete();
    });
  }
}
