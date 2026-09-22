<?php
namespace App\Models;

class ActivityCategory extends BaseModel {
    const COST_TYPE_VARIABLE = 1;
    const COST_TYPE_CONSTANT = 2;
    const BALANCE_TYPE_EXPENSE = 1;
    const BALANCE_TYPE_INCOME = 2;

    protected $guarded = ['id'];
    protected $rules = [
        'category_name' => 'required|max:32',
        'content' => 'max:255',
        'cost_type' => 'required',
        'balance_type' => 'required'
    ];

    public function user()
    {
        return $this->belongTo('App\Models\User');
    }

    public function activityCategoryItems()
    {
        return $this->hasMany('App\Models\ActivityCategoryItem');
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function($activity_category) {
            // 所有者で絞る。リレーションが見るのは activity_category_id だけで、
            // 大項目を消した人と小項目の持ち主が同じとは限らない。持ち主の
            // 違う行まで巻き込むと、他人のデータを消せることになる。
            // 付け替え先は検証で絞ってあるが (@see ActivityCategoryItem::
            // rulesForUser)、消す側も自分で確かめる。
            $activity_category_items = $activity_category->activityCategoryItems()
                ->where('user_id', '=', $activity_category->user_id)
                ->get();

            foreach ($activity_category_items as $activity_category_item) {
                $activity_category_item->delete();
            }
        });
    }
}
