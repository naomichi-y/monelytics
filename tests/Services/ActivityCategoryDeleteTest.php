<?php
namespace Tests\Services;

use Hash;

use App\Models\ActivityCategory;
use App\Models\ActivityCategoryItem;
use App\Models\User;
use Seeds\Test\ActivityCategoryTableSeeder;
use Seeds\Test\ActivityCategoryItemTableSeeder;
use Tests\TestCase;

/**
 * 削除は first() の戻り値をそのまま使っており、他人の id や存在しない id を
 * 送ると null に delete() を呼んで 500 になっていた。消えはしないので実害は
 * 無いが、無い物を消そうとしただけでエラー画面が出ていた。
 */
class ActivityCategoryDeleteTest extends TestCase {
    private $other;

    protected function setUp(): void
    {
        parent::setUp();

        // id は $guarded なので指定しても入らない。採番された物をそのまま持つ。
        $this->other = User::create([
            'email' => 'other@monelytics.me',
            'password' => Hash::make('testtest'),
            'nickname' => 'other',
            'type' => User::TYPE_GENERAL
        ]);
    }

    public function testDeletingSomeoneElsesCategoryIsNotFound()
    {
        $this->be($this->other);
        $response = $this->call('DELETE', '/settings/activityCategory/' . ActivityCategoryTableSeeder::TYPE_VARIABLE_EXPENSE);
        $this->logout();

        $response->assertNotFound();
        $this->assertNull(
            ActivityCategory::withTrashed()->find(ActivityCategoryTableSeeder::TYPE_VARIABLE_EXPENSE)->delete_date,
            '他人の大項目が消えた'
        );
    }

    public function testDeletingMissingCategoryIsNotFound()
    {
        $this->login();
        $response = $this->call('DELETE', '/settings/activityCategory/999999');
        $this->logout();

        $response->assertNotFound();
    }

    public function testDeletingSomeoneElsesItemIsNotFound()
    {
        $id = ActivityCategoryItemTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_ENABLE;

        $this->be($this->other);
        $response = $this->call('DELETE', '/settings/activityCategoryItem/' . $id);
        $this->logout();

        $response->assertNotFound();
        $this->assertNull(ActivityCategoryItem::withTrashed()->find($id)->delete_date, '他人の小項目が消えた');
    }

    public function testDeletingMissingItemIsNotFound()
    {
        $this->login();
        $response = $this->call('DELETE', '/settings/activityCategoryItem/999999');
        $this->logout();

        $response->assertNotFound();
    }

    /**
     * 自分のものはこれまでどおり消え、ぶら下がる小項目も一緒に消える。
     * クエリビルダの一括削除へ置き換えるとモデルの deleting が発火せず、
     * 小項目が取り残される。
     */
    public function testDeletingOwnCategoryStillCascades()
    {
        $id = ActivityCategoryTableSeeder::TYPE_VARIABLE_EXPENSE;
        $item_ids = ActivityCategoryItem::where('activity_category_id', $id)->pluck('id');

        $this->assertNotEmpty($item_ids, '前提となる小項目が無い');

        $this->login();
        $this->call('DELETE', '/settings/activityCategory/' . $id)->assertRedirect();
        $this->logout();

        $this->assertNotNull(ActivityCategory::withTrashed()->find($id)->delete_date, '大項目が消えていない');

        foreach ($item_ids as $item_id) {
            $this->assertNotNull(
                ActivityCategoryItem::withTrashed()->find($item_id)->delete_date,
                '小項目が取り残された'
            );
        }
    }
}
