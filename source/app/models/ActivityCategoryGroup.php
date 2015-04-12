<?php
class ActivityCategoryGroup extends BaseModel {
  protected $guarded = array('id');
  protected $validate_rules = array(
    'activity_category_id' => 'required',
    'group_name' => 'required|max:32',
    'content' => 'max:255'
  );

  public function user()
  {
    return $this->belongTo('User');
  }

  public function activityCategory()
  {
    return $this->belongsTo('ActivityCategory');
  }

  public function activity()
  {
    return $this->hasMany('Activity');
  }

  public static function boot()
  {
    parent::boot();

    static::deleting(function($activity_category_group) {
      $activity_category_group->activity()->delete();
    });
  }
}
