<?php
namespace Tests\Models;

use Hash;

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityCategoryItem;
use App\Models\User;
use Seeds\Test\ActivityCategoryTableSeeder;
use Tests\TestCase;

/**
 * 削除のカスケードは activity_category_id / activity_category_item_id だけを
 * 見てぶら下がりを辿る。持ち主が同じとは限らないので、絞らないと他人の行まで
 * 消せる。付け替え先は検証で塞いだが、消す側も自分で確かめる。
 */
class CascadeDeleteTest extends TestCase {
    private $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->other = User::create([
            'email' => 'other@monelytics.me',
            'password' => Hash::make('testtest'),
            'nickname' => 'other',
            'type' => User::TYPE_GENERAL
        ]);
    }

    /**
     * 他人の小項目がぶら下がっていても、大項目を消した人の物だけが消える。
     */
    public function testDeletingCategoryLeavesForeignItems()
    {
        $category = ActivityCategory::find(ActivityCategoryTableSeeder::TYPE_VARIABLE_EXPENSE);

        $mine = ActivityCategoryItem::where('activity_category_id', $category->id)
            ->where('user_id', $category->user_id)
            ->firstOrFail();

        $foreign = ActivityCategoryItem::create([
            'user_id' => $this->other->id,
            'activity_category_id' => $category->id,
            'item_name' => 'foreign',
            'sort_order' => 99
        ]);

        $category->delete();

        $this->assertNotNull(ActivityCategoryItem::withTrashed()->find($mine->id)->delete_date,
            '自分の小項目が残っている');
        $this->assertNull(ActivityCategoryItem::withTrashed()->find($foreign->id)->delete_date,
            '他人の小項目まで消えた');
    }

    /**
     * 小項目を消したとき、そこにぶら下がる他人の収支は消さない。
     */
    public function testDeletingItemLeavesForeignActivities()
    {
        $item = ActivityCategoryItem::where('user_id', 1)->firstOrFail();

        $mine = Activity::create([
            'user_id' => $item->user_id,
            'activity_category_item_id' => $item->id,
            'activity_date' => date('Y-m-d'),
            'amount' => -100,
            'credit_flag' => Activity::CREDIT_FLAG_UNUSE,
            'special_flag' => Activity::SPECIAL_FLAG_UNUSE
        ]);
        $foreign = Activity::create([
            'user_id' => $this->other->id,
            'activity_category_item_id' => $item->id,
            'activity_date' => date('Y-m-d'),
            'amount' => -200,
            'credit_flag' => Activity::CREDIT_FLAG_UNUSE,
            'special_flag' => Activity::SPECIAL_FLAG_UNUSE
        ]);

        $item->delete();

        $this->assertNotNull(Activity::withTrashed()->find($mine->id)->delete_date,
            '自分の収支が残っている');
        $this->assertNull(Activity::withTrashed()->find($foreign->id)->delete_date,
            '他人の収支まで消えた');
    }
}
