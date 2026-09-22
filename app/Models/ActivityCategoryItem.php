<?php
namespace App\Models;

use Illuminate\Validation\Rule;

class ActivityCategoryItem extends BaseModel {
    const CREDIT_FLAG_ENABLE = 1;
    const CREDIT_FLAG_DISABLE = 0;

    protected $guarded = ['id'];
    protected $rules = [
        'activity_category_id' => 'required',
        'item_name' => 'required|max:32',
        'content' => 'max:255'
    ];

    /**
     * 付け替え先の大項目を、送信者が持っているものだけに絞った規則を返す。
     *
     * $rules の 'required' は id が入っているかしか見ないため、他人の大項目の
     * id をそのまま送れば自分の小項目をそこへぶら下げられた。集計は大項目の
     * 所有者で引く (@see ActivityService::getMonthlySummary) ので、ぶら下げた
     * 側の小項目名と金額が相手の月別集計・固定収支の入力画面・大項目一覧に
     * 出てしまう。
     *
     * $rules を書き換えるのではなく組み立てて返すのは、同じインスタンスが
     * 使い回されると後続の検証まで前の user_id で走るため
     * (@see BaseModel::validate)。
     *
     * 論理削除済みの大項目も除く。付け替えられると、消したはずの大項目の下に
     * 小項目が現れる。
     *
     * @param int $user_id
     * @return array
     */
    public function rulesForUser($user_id)
    {
        return [
            'activity_category_id' => [
                'required',
                Rule::exists('activity_categories', 'id')
                    ->where('user_id', $user_id)
                    ->whereNull('delete_date'),
            ],
        ] + $this->rules;
    }

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

        static::deleting(function($activity_category_item) {
            // @see ActivityCategory::boot と同じ理由で所有者を確かめる。
            $activity_category_item->activity()
                ->where('user_id', '=', $activity_category_item->user_id)
                ->delete();
        });
    }
}
