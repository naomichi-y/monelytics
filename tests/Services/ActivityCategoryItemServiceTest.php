<?php
namespace Tests\Services;

use Hash;

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityCategoryItem;
use App\Models\User;
use Seeds\Test\ActivityCategoryTableSeeder;
use Tests\TestCase;

/**
 * 小項目の付け替え先 (activity_category_id) は利用者がそのまま送れる。
 * ここを所有者で絞り忘れていたため、他人の大項目へ自分の小項目をぶら下げ、
 * 相手の月別集計・固定収支の入力画面・大項目一覧に自分の小項目名と金額を
 * 出せる状態だった。本体を絞っても移す先を絞らなければ同じことが起きる。
 */
class ActivityCategoryItemServiceTest extends TestCase {
    private const OTHER_USER_ID = 99;

    private $activity_category_item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activity_category_item = app('App\Services\ActivityCategoryItemService');

        User::create([
            'id' => self::OTHER_USER_ID,
            'email' => 'other@monelytics.me',
            'password' => Hash::make('testtest'),
            'nickname' => 'other',
            'type' => User::TYPE_GENERAL
        ]);
    }

    /**
     * 登録時。他人の大項目の id を送っても作られない。
     */
    public function testCreateRejectsForeignActivityCategory()
    {
        $errors = [];
        $result = $this->activity_category_item->create(self::OTHER_USER_ID, [
            'activity_category_id' => ActivityCategoryTableSeeder::TYPE_VARIABLE_EXPENSE,
            'item_name' => 'foreign',
            'content' => ''
        ], $errors);

        $this->assertFalse($result, '他人の大項目にぶら下げられた');
        $this->assertArrayHasKey('activity_category_id', $errors);
        $this->assertSame(0, ActivityCategoryItem::where('item_name', 'foreign')->count());
    }

    /**
     * 自分の大項目へは今までどおり登録できる。絞り込みで塞ぎすぎていないこと。
     */
    public function testCreateAcceptsOwnActivityCategory()
    {
        $own = $this->createOwnCategory();
        $errors = [];

        $result = $this->activity_category_item->create(self::OTHER_USER_ID, [
            'activity_category_id' => $own->id,
            'item_name' => 'own',
            'content' => ''
        ], $errors);

        $this->assertTrue($result, var_export($errors, true));
        $this->assertSame(1, ActivityCategoryItem::where('item_name', 'own')->count());
    }

    /**
     * 更新時。自分の小項目でも、他人の大項目へは移せない。
     */
    public function testUpdateRejectsForeignActivityCategory()
    {
        $own = $this->createOwnCategory();
        $item = ActivityCategoryItem::create([
            'user_id' => self::OTHER_USER_ID,
            'activity_category_id' => $own->id,
            'item_name' => 'moved',
            'sort_order' => 1
        ]);
        $errors = [];

        $result = $this->activity_category_item->update($item->id, [
            'user_id' => self::OTHER_USER_ID,
            'activity_category_id' => ActivityCategoryTableSeeder::TYPE_CONSTANT_EXPENSE,
            'item_name' => 'moved',
            'content' => ''
        ], $errors);

        $this->assertFalse($result, '他人の大項目へ移せた');
        $this->assertSame($own->id, $item->fresh()->activity_category_id);
    }

    /**
     * 論理削除済みの大項目へも移せない。移せると、消したはずの大項目の下に
     * 小項目が現れる。
     */
    public function testCreateRejectsDeletedActivityCategory()
    {
        $own = $this->createOwnCategory();
        $own->delete();

        $errors = [];
        $result = $this->activity_category_item->create(self::OTHER_USER_ID, [
            'activity_category_id' => $own->id,
            'item_name' => 'deleted',
            'content' => ''
        ], $errors);

        $this->assertFalse($result, '削除済みの大項目にぶら下げられた');
    }

    /**
     * 集計側の歯止め。仮に所有者の違う行が入っても、被害者の集計には出ない。
     * 以前は大項目の所有者だけで引いていたため、混ざった行がそのまま出ていた。
     */
    public function testForeignRowsStayOutOfTheOwnersSummary()
    {
        // 検証を通さず、直接ねじ込む
        $item = ActivityCategoryItem::create([
            'user_id' => self::OTHER_USER_ID,
            'activity_category_id' => ActivityCategoryTableSeeder::TYPE_VARIABLE_EXPENSE,
            'item_name' => 'injected',
            'sort_order' => 99
        ]);
        Activity::create([
            'user_id' => self::OTHER_USER_ID,
            'activity_category_item_id' => $item->id,
            'activity_date' => date('Y-m-d'),
            'amount' => -54321,
            'credit_flag' => Activity::CREDIT_FLAG_UNUSE,
            'special_flag' => Activity::SPECIAL_FLAG_UNUSE
        ]);

        $this->login();
        $report = $this->call('GET', '/summary/monthly/report', ['date_month' => date('Y-m')]);
        $constant = $this->call('GET', '/cost/constant/create');
        $categories = $this->call('GET', '/settings/activityCategory');
        $this->logout();

        foreach (['月別集計' => $report, '固定収支の入力' => $constant, '大項目一覧' => $categories] as $name => $response) {
            $response->assertOk();
            $this->assertStringNotContainsString('injected', $response->getContent(), $name . 'に他人の小項目が出ている');
        }

        $this->assertStringNotContainsString('54,321', $report->getContent(), '月別集計に他人の金額が混ざっている');
    }

    private function createOwnCategory()
    {
        return ActivityCategory::create([
            'user_id' => self::OTHER_USER_ID,
            'category_name' => 'own',
            'cost_type' => ActivityCategory::COST_TYPE_VARIABLE,
            'balance_type' => ActivityCategory::BALANCE_TYPE_EXPENSE,
            'sort_order' => 1
        ]);
    }
}
