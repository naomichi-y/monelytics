<?php
namespace Tests\Controllers\Settings;

use App\Models\ActivityCategory;
use Tests\TestCase;

class ActivityCategoryControllerTest extends TestCase {
    private $activity_category;

    public function setup()
    {
        parent::setup();

        $this->activity_category = new ActivityCategory;
    }

    public function testIndex()
    {
        $this->assertUserOnlyContent('GET', '/settings/activityCategory');
    }

    public function testSort()
    {
        $this->login();
        $request_id_orders = $this->activity_category
            ->where('balance_type', '=', ActivityCategory::BALANCE_TYPE_EXPENSE)
            ->pluck('id')
            ->all();
        rsort($request_id_orders);

        $params = [
            'ids' => $request_id_orders,
            '_token' => csrf_token()
        ];
        $this->call(
            'POST',
            '/settings/activityCategory/sort',
            $params
        );
        $this->assertRedirectedTo('/settings/activityCategory');

        $result_id_orders = $this->activity_category
            ->where('balance_type', '=', ActivityCategory::BALANCE_TYPE_EXPENSE)
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();

        $this->assertEquals($result_id_orders, $request_id_orders);
    }

    public function testCreate()
    {
        $this->assertUserOnlyContent('GET', '/settings/activityCategory/create');
    }

    public function testStore()
    {
        $this->login();
        $params = [
            'category_name' => 'test',
            'cost_type' => ActivityCategory::COST_TYPE_VARIABLE,
            'balance_type' => ActivityCategory::BALANCE_TYPE_EXPENSE,
            '_token' => csrf_token()
        ];

        $default_count = $this->activity_category->all()->count();
        $this->assertValidAjaxResponse('POST', '/settings/activityCategory', $params);
        $this->assertEquals($this->activity_category->all()->count(), $default_count + 1);
    }

    public function testEdit()
    {
        $this->assertUserOnlyContent('GET', '/settings/activityCategory/1/edit');
    }

    public function testUpdate()
    {
        $this->login();
        $params = $this->activity_category->find(1)->toArray();
        $params['category_name'] = 'update';
        $params['_token'] = csrf_token();

        $this->assertValidAjaxResponse(
            'PUT',
            '/settings/activityCategory/1',
            $params
        );
        $this->assertEquals($this->activity_category->find(1)->category_name, 'update');
    }

    public function testDestroy()
    {

        $this->login();
        $this->call(
            'DELETE',
            '/settings/activityCategory/1',
            ['_token' => csrf_token()],
            [],
            [],
            ['HTTP_REFERER' => 'http://localhost/settings/activityCategory']
        );
        $this->assertRedirectedTo('/settings/activityCategory');
        $this->assertEquals($this->activity_category->find(1), null);
    }
}
