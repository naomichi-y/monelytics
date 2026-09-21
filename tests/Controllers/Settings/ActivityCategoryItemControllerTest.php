<?php
namespace Tests\Controllers\Settings;

use App\Models\ActivityCategoryItem;
use Seeds\Test\ActivityCategoryTableSeeder;
use Tests\TestCase;

class ActivityCategoryItemControllerTest extends TestCase {
    private $activity_category_item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activity_category_item = new ActivityCategoryItem;
    }

    public function testIndex()
    {
        $this->assertUserOnlyContent('GET', '/settings/activityCategoryItem');
    }

    public function testSort()
    {
        $this->login();
        $request_id_orders = $this->activity_category_item
            ->where('activity_category_id', '=', ActivityCategoryTableSeeder::TYPE_VARIABLE_EXPENSE)
            ->pluck('id')
            ->all();
        rsort($request_id_orders);

        $params = [
            'ids' => $request_id_orders
        ];
        $this->call(
            'POST',
            '/settings/activityCategoryItem/sort',
            $params
        );
        $this->assertRedirectedTo('/settings/activityCategoryItem');

        $result_id_orders = $this->activity_category_item
            ->where('activity_category_id', '=', ActivityCategoryTableSeeder::TYPE_VARIABLE_EXPENSE)
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();

        $this->assertEquals($result_id_orders, $request_id_orders);
    }

    public function testCreate()
    {
        $this->assertUserOnlyContent('GET', '/settings/activityCategoryItem/create');
    }

    public function testStore()
    {
        $this->login();
        $params = [
            'activity_category_id' => 1,
            'item_name' => 'test',
            'credit_flag' => ActivityCategoryItem::CREDIT_FLAG_DISABLE
        ];

        $default_count = $this->activity_category_item->all()->count();
        $this->assertValidAjaxResponse(
            'POST',
            '/settings/activityCategoryItem',
            $params
        );
        $this->assertEquals($this->activity_category_item->all()->count(), $default_count + 1);
    }

    public function testEdit()
    {
        $this->assertUserOnlyContent('GET', '/settings/activityCategory/1/edit');
    }

    public function testUpdate()
    {
        $this->login();
        $params = $this->activity_category_item->find(1)->toArray();
        $params['item_name'] = 'update';

        $this->assertValidAjaxResponse(
            'PUT',
            '/settings/activityCategoryItem/1',
            $params
        );
        $this->assertEquals($this->activity_category_item->find(1)->item_name, 'update');
    }

    public function testDestroy()
    {

        $this->login();
        $this->call(
            'DELETE',
            '/settings/activityCategoryItem/1',
            [],
            [],
            [],
            ['HTTP_REFERER' => 'http://localhost/settings/activityCategoryItem']
        );
        $this->assertRedirectedTo('/settings/activityCategoryItem');
        $this->assertEquals($this->activity_category_item->find(1), null);
    }
}
