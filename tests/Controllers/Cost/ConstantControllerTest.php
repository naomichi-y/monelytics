<?php
namespace Tests\Controllers\Cost;

use DateTime;

use App\Models\Activity;
use Tests\TestCase;

class ConstantControllerTest extends TestCase {
    private $default_count;

    protected function setUp(): void
    {
        parent::setUp();

        $activity = new Activity;
        $this->default_count = $activity->all()->count();
    }

    public function testCreate()
    {
        $this->assertUserOnlyContent('GET', '/cost/constant/create');
    }

    /**
     * 一覧・単体表示・編集の画面は持たない。@see VariableControllerTest
     *
     * どれも 404。一覧と単体表示は別の動詞で使う URI なので以前は 405 だったが、
     * routes/web.php の fallback が GET を拾うようになった。
     */
    public function testHasNoIndexOrShowRoute()
    {
        $this->login();

        $this->call('GET', '/cost/constant')->assertNotFound();
        $this->call('GET', '/cost/constant/1')->assertNotFound();
        $this->call('GET', '/cost/constant/1/edit')->assertNotFound();

        $this->logout();
    }

    public function testStore()
    {
        $this->login();
        $datetime = new DateTime('-1 months');
        $target_month = $datetime->format('Y-m');
        $params = [
            'activity_date' => [$target_month => [1 => $datetime->format('Y-m-d')]],
            'amount' => [$target_month => [1 => -1000]]
        ];

        $this->call(
            'POST',
            '/cost/constant',
            $params,
            [],
            [],
            ['HTTP_REFERER' => 'http://localhost/cost/constant/create']
        );
        $this->assertEquals($this->default_count + 1, Activity::all()->count());
        $this->assertRedirectedTo('/cost/constant/create');

        $this->call('POST', '/cost/constant', $params);
        $this->assertEquals($this->default_count + 1, Activity::all()->count());
    }

    public function testDestroy()
    {
        $this->login();
        $this->call(
            'DELETE',
            '/cost/constant/1',
            [],
            [],
            [],
            ['HTTP_REFERER' => 'http://localhost/cost/constant/create']
        );
        $this->assertRedirectedTo('/cost/constant/create');
        $this->assertEquals(Activity::find(1), null);
    }
}
