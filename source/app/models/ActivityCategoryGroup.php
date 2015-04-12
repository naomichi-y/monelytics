<?php
class ActivityCategoryGroup extends BaseModel {
  protected $table = 'activity_category_groups';
  protected $guarded = array('id');
  protected $validate_rules = array(
    'activity_category_id' => 'required',
    'group_name' => 'required|max:32',
    'content' => 'max:255'
  );

  public function activityCategory()
  {
    return $this->belongsTo('ActivityCategory', 'activity_category_id', 'id');
  }

  public static function boot()
  {
    parent::boot();

    static::deleting(function($activity_category_group) {
      App::make('Activity')
        ->where('activity_category_group_id', '=', $activity_category_group->id)
        ->delete();
    });
  }
}
